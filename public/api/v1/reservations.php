<?php

/**
 * API REST - Réservations
 *
 * GET  /api/v1/reservations          → Réservations de l'utilisateur connecté
 * GET  /api/v1/reservations/{id}     → Détail d'une réservation
 * POST /api/v1/reservations          → Créer une réservation
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/session.php';
require_once __DIR__ . '/../../../lib/pdo.php';
require_once __DIR__ . '/helpers.php';

use Ecoride\Ecf\Service\CreditService;

apiSetupCors();

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = explode('/', trim($uri, '/'));

if (
    !isset($uriParts[0], $uriParts[1], $uriParts[2])
    || $uriParts[0] !== 'api'
    || $uriParts[1] !== 'v1'
    || $uriParts[2] !== 'reservations'
) {
    apiSendError('Endpoint non trouvé', 404);
}

$reservationId = isset($uriParts[3]) && $uriParts[3] !== '' ? (int) $uriParts[3] : null;

function resolveReservationsTableApi(PDO $pdo): ?string
{
    foreach (['reservations', 'reservation'] as $tableName) {
        try {
            $pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");

            return $tableName;
        } catch (Throwable) {
            continue;
        }
    }

    return null;
}

try {
    $table = resolveReservationsTableApi($pdo);
    if ($table === null) {
        apiSendError('Module réservations indisponible', 503);
    }

    if ($method === 'GET' && $reservationId === null) {
        requireLogin();
        $userId = (int) $_SESSION['user']['user_id'];
        $statut = trim((string) ($_GET['statut'] ?? ''));

        $sql = "
            SELECT r.reservation_id, r.user_id, r.covoiturage_id, r.nb_places_reservees,
                   r.prix_total, r.statut, r.date_reservation,
                   c.lieu_depart, c.lieu_arrivee, c.date_depart, c.heure_depart, c.prix_personne
            FROM {$table} r
            JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
            WHERE r.user_id = :user_id
        ";
        $params = ['user_id' => $userId];

        if ($statut !== '') {
            $sql .= " AND r.statut = :statut";
            $params['statut'] = $statut;
        }

        $sql .= " ORDER BY r.date_reservation DESC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $items = array_map('formatReservationApiRow', $stmt->fetchAll(PDO::FETCH_ASSOC));

        apiSendResponse(true, [
            'reservations' => $items,
            'total' => count($items),
        ]);
    }

    if ($method === 'GET' && $reservationId !== null && $reservationId > 0) {
        requireLogin();
        $userId = (int) $_SESSION['user']['user_id'];

        $stmt = $pdo->prepare("
            SELECT r.*, c.lieu_depart, c.lieu_arrivee, c.date_depart, c.heure_depart,
                   c.prix_personne, c.user_id AS chauffeur_id
            FROM {$table} r
            JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
            WHERE r.reservation_id = :id
              AND (r.user_id = :user_id OR c.user_id = :user_id_chauffeur)
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $reservationId,
            'user_id' => $userId,
            'user_id_chauffeur' => $userId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            apiSendError('Réservation non trouvée', 404);
        }

        apiSendResponse(true, formatReservationApiRow($row));
    }

    if ($method === 'POST' && $reservationId === null) {
        requireLogin();

        $input = apiParseJsonBody();
        if ($input === [] && !empty($_POST)) {
            $input = $_POST;
        }

        $covoiturageId = isset($input['covoiturage_id']) ? (int) $input['covoiturage_id'] : 0;
        $userId = (int) $_SESSION['user']['user_id'];

        if ($covoiturageId <= 0) {
            apiSendError('covoiturage_id requis', 400);
        }

        $pdo->beginTransaction();

        $covStmt = $pdo->prepare("
            SELECT covoiturage_id, nb_place, prix_personne, user_id, statut
            FROM covoiturage
            WHERE covoiturage_id = :id
            FOR UPDATE
        ");
        $covStmt->execute(['id' => $covoiturageId]);
        $covoiturage = $covStmt->fetch(PDO::FETCH_ASSOC);

        if (!$covoiturage) {
            throw new RuntimeException('Covoiturage introuvable.');
        }
        if ((int) $covoiturage['user_id'] === $userId) {
            throw new RuntimeException('Vous ne pouvez pas réserver votre propre covoiturage.');
        }
        if ((int) $covoiturage['statut'] !== 1) {
            throw new RuntimeException('Ce covoiturage n\'est plus disponible.');
        }
        if ((int) $covoiturage['nb_place'] <= 0) {
            throw new RuntimeException('Plus de places disponibles.');
        }

        $prixRequis = (int) round((float) $covoiturage['prix_personne']);
        $creditService = new CreditService($pdo);
        if (!$creditService->hasSufficientCredits($userId, $prixRequis)) {
            throw new RuntimeException('Crédits insuffisants pour réserver ce trajet.');
        }

        $checkStmt = $pdo->prepare("
            SELECT reservation_id FROM {$table}
            WHERE user_id = :user_id AND covoiturage_id = :covoiturage_id AND statut != 'annulée'
            LIMIT 1
        ");
        $checkStmt->execute(['user_id' => $userId, 'covoiturage_id' => $covoiturageId]);
        if ($checkStmt->fetch()) {
            throw new RuntimeException('Vous avez déjà réservé ce covoiturage.');
        }

        $prixTotal = (float) $covoiturage['prix_personne'];
        $insertStmt = $pdo->prepare("
            INSERT INTO {$table} (user_id, covoiturage_id, nb_places_reservees, prix_total, statut)
            VALUES (:user_id, :covoiturage_id, 1, :prix_total, 'En attente')
        ");
        $insertStmt->execute([
            'user_id' => $userId,
            'covoiturage_id' => $covoiturageId,
            'prix_total' => $prixTotal,
        ]);

        $newId = (int) $pdo->lastInsertId();

        $updateStmt = $pdo->prepare("
            UPDATE covoiturage SET nb_place = nb_place - 1
            WHERE covoiturage_id = :id AND nb_place >= 1
        ");
        $updateStmt->execute(['id' => $covoiturageId]);

        if ($updateStmt->rowCount() === 0) {
            throw new RuntimeException('La réservation n\'a pas pu être confirmée.');
        }

        $pdo->commit();

        apiSendResponse(true, [
            'reservation_id' => $newId,
            'message' => 'Réservation créée (En attente de confirmation chauffeur)',
        ], null, 201);
    }

    apiSendError('Méthode non supportée', 405);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    apiSendError($e->getMessage(), 400);
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function formatReservationApiRow(array $row): array
{
    return [
        'id' => (int) ($row['reservation_id'] ?? 0),
        'covoiturage_id' => (int) ($row['covoiturage_id'] ?? 0),
        'nb_places' => (int) ($row['nb_places_reservees'] ?? 1),
        'prix_total' => isset($row['prix_total']) ? (float) $row['prix_total'] : null,
        'statut' => $row['statut'] ?? null,
        'date_reservation' => $row['date_reservation'] ?? null,
        'trajet' => [
            'lieu_depart' => $row['lieu_depart'] ?? null,
            'lieu_arrivee' => $row['lieu_arrivee'] ?? null,
            'date_depart' => $row['date_depart'] ?? null,
            'heure_depart' => $row['heure_depart'] ?? null,
        ],
    ];
}
