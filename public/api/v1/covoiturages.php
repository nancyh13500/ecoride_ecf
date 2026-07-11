<?php

/**
 * API REST - Covoiturages
 *
 * GET /api/v1/covoiturages           → Liste (filtres : depart, arrivee, date, statut)
 * GET /api/v1/covoiturages/{id}      → Détail d'un covoiturage
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/pdo.php';
require_once __DIR__ . '/helpers.php';

apiSetupCors();

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = explode('/', trim($uri, '/'));

if (
    !isset($uriParts[0], $uriParts[1], $uriParts[2])
    || $uriParts[0] !== 'api'
    || $uriParts[1] !== 'v1'
    || $uriParts[2] !== 'covoiturages'
) {
    apiSendError('Endpoint non trouvé', 404);
}

$covoiturageId = isset($uriParts[3]) && $uriParts[3] !== '' ? (int) $uriParts[3] : null;

try {
    if ($method === 'GET' && $covoiturageId === null) {
        $depart = trim((string) ($_GET['depart'] ?? ''));
        $arrivee = trim((string) ($_GET['arrivee'] ?? ''));
        $date = trim((string) ($_GET['date'] ?? ''));
        $statut = isset($_GET['statut']) ? (int) $_GET['statut'] : 1;
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $sql = "
            SELECT c.covoiturage_id, c.date_depart, c.heure_depart, c.date_arrivee, c.heure_arrivee,
                   c.lieu_depart, c.lieu_arrivee, c.nb_place, c.prix_personne, c.statut,
                   c.distance_km, c.co2_economise_kg, c.duree,
                   u.prenom, u.nom, u.pseudo,
                   v.modele, m.libelle AS marque, e.libelle AS energie
            FROM covoiturage c
            LEFT JOIN user u ON u.user_id = c.user_id
            LEFT JOIN voiture v ON v.voiture_id = c.voiture_id
            LEFT JOIN marque m ON m.marque_id = v.marque_id
            LEFT JOIN energie e ON e.energie_id = v.energie_id
            WHERE c.statut = :statut
              AND c.date_depart >= CURDATE()
        ";
        $params = ['statut' => $statut];

        if ($depart !== '') {
            $sql .= " AND LOWER(c.lieu_depart) LIKE LOWER(:depart)";
            $params['depart'] = '%' . $depart . '%';
        }
        if ($arrivee !== '') {
            $sql .= " AND LOWER(c.lieu_arrivee) LIKE LOWER(:arrivee)";
            $params['arrivee'] = '%' . $arrivee . '%';
        }
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $sql .= " AND c.date_depart = :date";
            $params['date'] = $date;
        }

        $sql .= " ORDER BY c.date_depart ASC, c.heure_depart ASC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = formatCovoiturageApiRow($row);
        }

        apiSendResponse(true, [
            'covoiturages' => $items,
            'total' => count($items),
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    if ($method === 'GET' && $covoiturageId !== null && $covoiturageId > 0) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.prenom, u.nom, u.pseudo, u.email,
                   v.modele, v.immatriculation, v.couleur,
                   m.libelle AS marque, e.libelle AS energie
            FROM covoiturage c
            LEFT JOIN user u ON u.user_id = c.user_id
            LEFT JOIN voiture v ON v.voiture_id = c.voiture_id
            LEFT JOIN marque m ON m.marque_id = v.marque_id
            LEFT JOIN energie e ON e.energie_id = v.energie_id
            WHERE c.covoiturage_id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $covoiturageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            apiSendError('Covoiturage non trouvé', 404);
        }

        $etapesStmt = $pdo->prepare("
            SELECT v.nom, e.ordre, e.heure_prevue
            FROM etape e
            JOIN ville v ON v.ville_id = e.ville_id
            WHERE e.covoiturage_id = :id
            ORDER BY e.ordre ASC
        ");
        $etapesStmt->execute(['id' => $covoiturageId]);
        $etapes = $etapesStmt->fetchAll(PDO::FETCH_ASSOC);

        $data = formatCovoiturageApiRow($row);
        $data['conducteur'] = [
            'nom' => trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? '')),
            'pseudo' => $row['pseudo'] ?? null,
        ];
        $data['vehicule'] = [
            'marque' => $row['marque'] ?? null,
            'modele' => $row['modele'] ?? null,
            'immatriculation' => $row['immatriculation'] ?? null,
            'energie' => $row['energie'] ?? null,
        ];
        $data['etapes'] = $etapes;

        apiSendResponse(true, $data);
    }

    apiSendError('Méthode non supportée', 405);
} catch (Throwable $e) {
    apiSendError('Erreur serveur : ' . $e->getMessage(), 500);
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function formatCovoiturageApiRow(array $row): array
{
    return [
        'id' => (int) ($row['covoiturage_id'] ?? 0),
        'date_depart' => $row['date_depart'] ?? null,
        'heure_depart' => $row['heure_depart'] ?? null,
        'date_arrivee' => $row['date_arrivee'] ?? null,
        'heure_arrivee' => $row['heure_arrivee'] ?? null,
        'lieu_depart' => $row['lieu_depart'] ?? null,
        'lieu_arrivee' => $row['lieu_arrivee'] ?? null,
        'places_disponibles' => isset($row['nb_place']) ? (int) $row['nb_place'] : null,
        'prix_personne' => isset($row['prix_personne']) ? (float) $row['prix_personne'] : null,
        'statut' => isset($row['statut']) ? (int) $row['statut'] : null,
        'distance_km' => isset($row['distance_km']) ? (float) $row['distance_km'] : null,
        'co2_economise_kg' => isset($row['co2_economise_kg']) ? (float) $row['co2_economise_kg'] : null,
        'duree_minutes' => isset($row['duree']) ? (int) $row['duree'] : null,
    ];
}
