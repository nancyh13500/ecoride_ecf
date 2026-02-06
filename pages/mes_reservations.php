<?php
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Ecoride\Ecf\Service\MailerService;

requireLogin();

// Données utilisateur
$currentUserId = (int)$_SESSION['user']['user_id'];

// 1) Trajets publiés par l'utilisateur (ex: terminés)
$reservationSupport = null; // 'reservations' | 'reservation' | null
$reservationTables = ['reservations', 'reservation'];

foreach ($reservationTables as $tableName) {
    try {
        $pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");
        $reservationSupport = $tableName;
        break;
    } catch (Throwable $ignored) {
        continue;
    }
}

$validation_error = '';
$cancel_error = '';
$cancel_success = false;

// Traitement de l'annulation de réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    if (!$reservationSupport) {
        $cancel_error = "Impossible d'annuler la réservation : module indisponible.";
    } else {
        $reservationId = isset($_POST['reservation_id']) ? (int) $_POST['reservation_id'] : 0;

        if ($reservationId <= 0) {
            $cancel_error = "Réservation invalide.";
        } else {
            try {
                // Démarrer une transaction
                if (!$pdo->inTransaction()) {
                    $pdo->beginTransaction();
                }

                // Récupérer les détails de la réservation avec verrouillage
                $reservationDetailStmt = $pdo->prepare("
                    SELECT 
                        r.*,
                        c.covoiturage_id,
                        c.user_id AS chauffeur_id,
                        c.nb_place,
                        c.lieu_depart,
                        c.lieu_arrivee,
                        c.date_depart,
                        c.heure_depart
                    FROM {$reservationSupport} r
                    JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
                    WHERE r.reservation_id = :id AND r.user_id = :user_id
                    FOR UPDATE
                ");
                $reservationDetailStmt->execute([
                    ':id' => $reservationId,
                    ':user_id' => $currentUserId
                ]);
                $reservationRow = $reservationDetailStmt->fetch(PDO::FETCH_ASSOC);

                if (!$reservationRow) {
                    throw new Exception("Réservation introuvable ou vous n'êtes pas autorisé à l'annuler.");
                }

                // Vérifier que la réservation n'est pas déjà confirmée (optionnel, selon les règles métier)
                $statutReservation = mb_strtolower($reservationRow['statut'] ?? '', 'UTF-8');
                if ($statutReservation === 'confirmée') {
                    throw new Exception("Impossible d'annuler une réservation déjà confirmée. Contactez le chauffeur.");
                }

                // Vérifier que la réservation n'est pas déjà annulée
                if ($statutReservation === 'annulée') {
                    throw new Exception("Cette réservation a déjà été annulée.");
                }

                $nbPlacesReservees = (int)($reservationRow['nb_places_reservees'] ?? 1);
                $covoiturageId = (int)$reservationRow['covoiturage_id'];

                // Mettre le statut à "annulée" au lieu de supprimer (pour que le chauffeur puisse voir l'annulation)
                $updateStmt = $pdo->prepare("
                    UPDATE {$reservationSupport}
                    SET statut = 'annulée'
                    WHERE reservation_id = :id AND user_id = :user_id
                ");
                $updateStmt->execute([
                    ':id' => $reservationId,
                    ':user_id' => $currentUserId
                ]);

                if ($updateStmt->rowCount() === 0) {
                    throw new Exception("La réservation n'a pas pu être annulée.");
                }

                // Remettre les places dans le covoiturage
                $updateCovoiturage = $pdo->prepare("
                    UPDATE covoiturage
                    SET nb_place = nb_place + :nb_places
                    WHERE covoiturage_id = :covoiturage_id
                ");
                $updateCovoiturage->execute([
                    ':nb_places' => $nbPlacesReservees,
                    ':covoiturage_id' => $covoiturageId
                ]);

                // Valider la transaction
                $pdo->commit();

                header("Location: mes_reservations.php?cancel_success=1");
                exit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $cancel_error = "Erreur lors de l'annulation : " . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_reservation'])) {
    // DEBUT DEBUG
    error_log("=== VALIDATION RESERVATION ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("reservationSupport: " . ($reservationSupport ?? 'NULL'));
    // FIN DEBUG

    if (!$reservationSupport) {
        $validation_error = "Impossible de valider la réservation : module indisponible.";
    } else {
        $reservationId = isset($_POST['reservation_id']) ? (int) $_POST['reservation_id'] : 0;

        if ($reservationId <= 0) {
            $validation_error = "Réservation invalide.";
        } else {
            try {
                // Récupérer toutes les infos nécessaires pour les emails
                $reservationDetailStmt = $pdo->prepare("
                    SELECT 
                        r.*,
                        c.covoiturage_id,
                        c.user_id AS chauffeur_id,
                        c.lieu_depart,
                        c.lieu_arrivee,
                        c.date_depart,
                        c.heure_depart,
                        c.prix_personne,
                        passager.email AS passager_email,
                        passager.nom AS passager_nom,
                        passager.prenom AS passager_prenom,
                        passager.pseudo AS passager_pseudo,
                        chauffeur.email AS chauffeur_email,
                        chauffeur.nom AS chauffeur_nom,
                        chauffeur.prenom AS chauffeur_prenom,
                        v.modele AS vehicle_modele,
                        v.immatriculation AS vehicle_immat
                    FROM {$reservationSupport} r
                    JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
                    JOIN user passager ON passager.user_id = r.user_id
                    JOIN user chauffeur ON chauffeur.user_id = c.user_id
                    LEFT JOIN voiture v ON v.voiture_id = c.voiture_id
                    WHERE r.reservation_id = :id
                    LIMIT 1
                ");
                $reservationDetailStmt->execute([':id' => $reservationId]);
                $reservationRow = $reservationDetailStmt->fetch(PDO::FETCH_ASSOC);

                if (!$reservationRow || (int) $reservationRow['chauffeur_id'] !== $currentUserId) {
                    $validation_error = "Réservation introuvable ou non autorisée.";
                } else {
                    // Mettre à jour le statut
                    $updateStmt = $pdo->prepare("
                        UPDATE {$reservationSupport}
                        SET statut = 'confirmée'
                        WHERE reservation_id = :id
                    ");
                    $updateStmt->execute([':id' => $reservationId]);

                    // Préparer les données pour les emails
                    $passagerNomComplet = trim(($reservationRow['passager_prenom'] ?? '') . ' ' . ($reservationRow['passager_nom'] ?? ''));
                    if ($passagerNomComplet === '') {
                        $passagerNomComplet = $reservationRow['passager_pseudo'] ?? 'Passager';
                    }

                    $chauffeurNomComplet = trim(($reservationRow['chauffeur_prenom'] ?? '') . ' ' . ($reservationRow['chauffeur_nom'] ?? ''));
                    if ($chauffeurNomComplet === '') {
                        $chauffeurNomComplet = 'Chauffeur';
                    }

                    $dateDepart = '';
                    if (!empty($reservationRow['date_depart']) && $reservationRow['date_depart'] !== '0000-00-00') {
                        $dateDepart = date('d/m/Y', strtotime($reservationRow['date_depart']));
                    }

                    $heureDepart = '';
                    if (!empty($reservationRow['heure_depart']) && $reservationRow['heure_depart'] !== '00:00:00') {
                        $heureDepart = date('H:i', strtotime($reservationRow['heure_depart']));
                    }

                    $vehicleInfo = 'Non renseigné';
                    if (!empty($reservationRow['vehicle_modele'])) {
                        $vehicleInfo = $reservationRow['vehicle_modele'];
                        if (!empty($reservationRow['vehicle_immat'])) {
                            $vehicleInfo .= ' (' . $reservationRow['vehicle_immat'] . ')';
                        }
                    }

                    $nbPlacesReservees = isset($reservationRow['nb_places_reservees']) ? (int)$reservationRow['nb_places_reservees'] : 1;
                    $prixTotal = isset($reservationRow['prix_personne']) ? ((float)$reservationRow['prix_personne'] * $nbPlacesReservees) : 0;

                    // Envoyer l'email au passager
                    $emailPassagerEnvoye = false;
                    if (!empty($reservationRow['passager_email'])) {
                        $mailer = new MailerService();
                        $emailPassagerEnvoye = $mailer->sendReservationConfirmation([
                            'passenger_name' => $passagerNomComplet,
                            'passenger_email' => $reservationRow['passager_email'],
                            'date_depart' => $dateDepart,
                            'heure_depart' => $heureDepart,
                            'lieu_depart' => $reservationRow['lieu_depart'] ?? '',
                            'lieu_arrivee' => $reservationRow['lieu_arrivee'] ?? '',
                            'driver_name' => $chauffeurNomComplet,
                            'vehicle_info' => $vehicleInfo,
                            'prix' => number_format($prixTotal, 0),
                            'reservation_id' => $reservationId
                        ]);
                    }

                    // Envoyer l'email au chauffeur (confirmation pour toi)
                    $emailChauffeurEnvoye = false;
                    if (!empty($reservationRow['chauffeur_email'])) {
                        $mailer = new MailerService();
                        $emailChauffeurEnvoye = $mailer->sendDriverNotification([
                            'driver_name' => $chauffeurNomComplet,
                            'driver_email' => $reservationRow['chauffeur_email'],
                            'passenger_name' => $passagerNomComplet,
                            'nb_places' => $nbPlacesReservees,
                            'date_depart' => $dateDepart,
                            'heure_depart' => $heureDepart,
                            'lieu_depart' => $reservationRow['lieu_depart'] ?? '',
                            'lieu_arrivee' => $reservationRow['lieu_arrivee'] ?? ''
                        ]);
                    }

                    // Message de succès adapté selon l'envoi des emails
                    $messageSucces = 'validation_success=1';
                    if (!$emailPassagerEnvoye && !empty($reservationRow['passager_email'])) {
                        $messageSucces .= '&email_warning=1';
                    }

                    header("Location: mes_reservations.php?{$messageSucces}");
                    exit();
                }
            } catch (Throwable $e) {
                $validation_error = "Erreur lors de la validation : " . $e->getMessage();
            }
        }
    }
}

$trajetsPublies = [];
$reservationsParTrajet = [];
$reservationsChauffeur = [];
try {
    $stmt = $pdo->prepare(
        "SELECT c.*
         FROM covoiturage c
         WHERE c.user_id = :uid
         ORDER BY c.date_depart DESC, c.covoiturage_id DESC"
    );
    $stmt->execute([':uid' => $currentUserId]);
    $trajetsPublies = $stmt->fetchAll();
} catch (Throwable $e) {
    $trajetsPublies = [];
}

// Liste des IDs de trajets publiés pour récupérer les réservations associées
$trajetPublieIds = array_values(array_filter(array_map(
    static function ($trajet) {
        return isset($trajet['covoiturage_id']) ? (int) $trajet['covoiturage_id'] : null;
    },
    $trajetsPublies
)));

// 2) Trajets réservés par l'utilisateur (en tant que passager)

$trajetsReserves = [];
try {
    if ($reservationSupport) {
        $sql = "
            SELECT 
                c.*,
                r.reservation_id,
                r.date_reservation,
                r.nb_places_reservees,
                r.prix_total,
                r.statut AS reservation_statut
            FROM {$reservationSupport} r
            JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
            WHERE r.user_id = :uid
            ORDER BY r.date_reservation DESC, c.date_depart DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $currentUserId]);
        $trajetsReserves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupération des réservations pour les trajets publiés par l'utilisateur (en tant que chauffeur)
    if ($reservationSupport && !empty($trajetPublieIds)) {
        $placeholders = implode(',', array_fill(0, count($trajetPublieIds), '?'));
        $reservationSql = "
            SELECT 
                r.*,
                u.nom AS passager_nom,
                u.prenom AS passager_prenom,
                u.pseudo AS passager_pseudo,
                u.email AS passager_email,
                u.telephone AS passager_telephone
            FROM {$reservationSupport} r
            JOIN user u ON u.user_id = r.user_id
            WHERE r.covoiturage_id IN ($placeholders)
            ORDER BY r.date_reservation DESC, r.reservation_id DESC
        ";
        try {
            $reservationStmt = $pdo->prepare($reservationSql);
            $reservationStmt->execute($trajetPublieIds);

            while ($row = $reservationStmt->fetch(PDO::FETCH_ASSOC)) {
                $trajetId = isset($row['covoiturage_id']) ? (int) $row['covoiturage_id'] : 0;
                if ($trajetId > 0) {
                    if (!isset($reservationsParTrajet[$trajetId])) {
                        $reservationsParTrajet[$trajetId] = [];
                    }
                    $reservationsParTrajet[$trajetId][] = $row;
                }
            }

            foreach ($trajetsPublies as $trajet) {
                $trajetId = isset($trajet['covoiturage_id']) ? (int) $trajet['covoiturage_id'] : 0;
                if ($trajetId > 0 && !empty($reservationsParTrajet[$trajetId])) {
                    foreach ($reservationsParTrajet[$trajetId] as $reservation) {
                        $reservationsChauffeur[] = [
                            'trajet' => $trajet,
                            'reservation' => $reservation,
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            // En cas d'erreur, on continue sans bloquer l'affichage
            error_log("Erreur lors de la récupération des réservations pour le chauffeur: " . $e->getMessage());
        }
    }
} catch (Throwable $e) {
    $trajetsReserves = [];
}

require_once __DIR__ . "/../templates/header.php";
?>

<section class="hero count-section py-5">
    <div class="container">

        <nav aria-label="breadcrumb" class="ps-3 pt-3 mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item "><a href="/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mes réservations</li>
            </ol>
        </nav>

        <div class="row">
            <!-- Menu latéral -->
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Mon compte</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="/pages/user_count.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-person-circle me-2"></i>Mes informations
                        </a>
                        <a href="/pages/mes_trajets.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-signpost-2 me-2"></i>Mes trajets
                        </a>
                        <a href="/pages/mes_reservations.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-calendar-check me-2"></i>Mes réservations
                        </a>
                        <a href="/pages/mes_voitures.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-car-front me-2"></i>Mes voitures
                        </a>
                        <?php if (($_SESSION['user']['role_id'] ?? 3) == 2): ?>
                            <a href="/pages/employe.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-person-badge me-2"></i>Espace Employé
                            </a>
                        <?php endif; ?>
                        <?php if (($_SESSION['user']['role_id'] ?? 3) == 1): ?>
                            <a href="/pages/admin.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-gear me-2"></i>Administration
                            </a>
                            <a href="/pages/user_count.php?create_employee=1" class="list-group-item list-group-item-action">
                                <i class="bi bi-person-plus me-2"></i>Créer un employé
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Réservation enregistrée avec succès.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['validation_success'])): ?>
                    <div class="alert alert-success">
                        La réservation a été confirmée avec succès et une notification a été envoyée au passager.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['cancel_success'])): ?>
                    <div class="alert alert-success">
                        La réservation a été annulée avec succès. Les places ont été libérées.
                    </div>
                <?php endif; ?>
                <?php if (!empty($validation_error)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($validation_error) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($cancel_error)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($cancel_error) ?>
                    </div>
                <?php endif; ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0">Réservations (passager)</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($reservationSupport === null): ?>
                            <div class="alert alert-warning">
                                La fonctionnalité de réservations n'est pas encore disponible (table de réservations absente).
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($trajetsReserves)): ?>
                            <div class="row g-3">
                                <?php foreach ($trajetsReserves as $trajet): ?>
                                    <?php
                                    $dateDepartRaw = $trajet['date_depart'] ?? '';
                                    $dateDepart = ($dateDepartRaw && $dateDepartRaw !== '0000-00-00') ? date('d/m/Y', strtotime($dateDepartRaw)) : 'À venir';
                                    $heureDepartRaw = $trajet['heure_depart'] ?? '';
                                    $heureDepart = ($heureDepartRaw && $heureDepartRaw !== '00:00:00') ? date('H:i', strtotime($heureDepartRaw)) : '--:--';
                                    $prixPersonne = isset($trajet['prix_personne']) ? number_format((float) $trajet['prix_personne'], 0) : '0';
                                    $dateReservationRaw = $trajet['date_reservation'] ?? '';
                                    $dateReservation = ($dateReservationRaw && $dateReservationRaw !== '0000-00-00 00:00:00') ? date('d/m/Y H:i', strtotime($dateReservationRaw)) : '';
                                    $nbPlacesReservees = isset($trajet['nb_places_reservees']) ? (int) $trajet['nb_places_reservees'] : 1;
                                    $prixTotal = isset($trajet['prix_total']) ? number_format((float) $trajet['prix_total'], 0) : $prixPersonne;
                                    $reservationStatut = $trajet['reservation_statut'] ?? 'En attente';
                                    ?>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header bg-secondary text-white">
                                                Trajet réservé
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-1"><strong>Départ :</strong> <?= htmlspecialchars($trajet['lieu_depart'] ?? '') ?></p>
                                                <p class="mb-1"><strong>Arrivée :</strong> <?= htmlspecialchars($trajet['lieu_arrivee'] ?? '') ?></p>
                                                <p class="mb-1"><strong>Date :</strong> <?= htmlspecialchars($dateDepart) ?> à <?= htmlspecialchars($heureDepart) ?></p>
                                                <p class="mb-1"><strong>Places réservées :</strong> <?= htmlspecialchars((string) $nbPlacesReservees) ?></p>
                                                <p class="mb-1"><strong>Prix / personne :</strong> <?= htmlspecialchars($prixPersonne) ?> crédits</p>
                                                <p class="mb-1"><strong>Prix total :</strong> <?= htmlspecialchars($prixTotal) ?> crédits</p>
                                                <?php if (!empty($dateReservation)): ?>
                                                    <p class="mb-1"><strong>Réservé le :</strong> <?= htmlspecialchars($dateReservation) ?></p>
                                                <?php endif; ?>
                                                <p class="mb-1"><strong>Statut :</strong> <?= htmlspecialchars($reservationStatut) ?></p>
                                                <div class="d-flex gap-2 mt-2">
                                                    <a href="detail_covoiturage.php?id=<?= htmlspecialchars((string)($trajet['covoiturage_id'] ?? '')) ?>" class="btn btn-sm btn-primary">
                                                        Voir les détails
                                                    </a>
                                                    <?php
                                                    $reservationId = isset($trajet['reservation_id']) ? (int)$trajet['reservation_id'] : 0;
                                                    $statutReservationMin = mb_strtolower($reservationStatut, 'UTF-8');
                                                    $peutAnnuler = ($statutReservationMin !== 'confirmée' && $statutReservationMin !== 'annulée' && $reservationId > 0);
                                                    ?>
                                                    <?php if ($peutAnnuler): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="reservation_id" value="<?= htmlspecialchars((string)$reservationId) ?>">
                                                            <input type="hidden" name="action" value="1">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                Annuler
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($reservationSupport !== null): ?>
                            <p>Aucune réservation trouvée en tant que passager.</p>
                        <?php endif; ?>

                        <?php if (!empty($reservationsChauffeur)): ?>
                            <hr class="my-4">
                            <h5 class="mb-3">Réservations sur mes trajets (en tant que chauffeur)</h5>
                            <div class="row g-3">
                                <?php foreach ($reservationsChauffeur as $entry): ?>
                                    <?php
                                    $trajet = $entry['trajet'];
                                    $reservation = $entry['reservation'];
                                    $dateDepartRaw = $trajet['date_depart'] ?? '';
                                    $dateDepart = ($dateDepartRaw && $dateDepartRaw !== '0000-00-00') ? date('d/m/Y', strtotime($dateDepartRaw)) : 'À venir';
                                    $heureDepartRaw = $trajet['heure_depart'] ?? '';
                                    $heureDepart = ($heureDepartRaw && $heureDepartRaw !== '00:00:00') ? date('H:i', strtotime($heureDepartRaw)) : '--:--';
                                    $reservationDateRaw = $reservation['date_reservation'] ?? '';
                                    $reservationDate = ($reservationDateRaw && $reservationDateRaw !== '0000-00-00 00:00:00') ? date('d/m/Y H:i', strtotime($reservationDateRaw)) : '';
                                    $passagerNom = trim(($reservation['passager_prenom'] ?? '') . ' ' . ($reservation['passager_nom'] ?? ''));
                                    if ($passagerNom === '') {
                                        $passagerNom = $reservation['passager_pseudo'] ?? 'Passager inconnu';
                                    }
                                    $statutReservation = $reservation['statut'] ?? '';
                                    $statutReservationMin = $statutReservation !== '' ? mb_strtolower($statutReservation, 'UTF-8') : '';
                                    $reservationConfirmee = $statutReservationMin === 'confirmée';
                                    $reservationAnnulee = $statutReservationMin === 'annulée';
                                    // Déterminer le style de la carte selon le statut
                                    $cardBorderClass = $reservationAnnulee ? 'border-danger' : ($reservationConfirmee ? 'border-primary' : 'border-success');
                                    $cardHeaderClass = $reservationAnnulee ? 'bg-danger' : ($reservationConfirmee ? 'bg-primary' : 'bg-success');
                                    $cardHeaderText = $reservationAnnulee ? 'Réservation annulée' : ($reservationConfirmee ? 'Réservation confirmée' : 'Réservation reçue');
                                    ?>
                                    <div class="col-md-6">
                                        <div class="card h-100 <?= htmlspecialchars($cardBorderClass) ?>">
                                            <div class="card-header <?= htmlspecialchars($cardHeaderClass) ?> text-white">
                                                <?= htmlspecialchars($cardHeaderText) ?>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-1"><strong>Trajet :</strong> <?= htmlspecialchars(($trajet['lieu_depart'] ?? '') . ' → ' . ($trajet['lieu_arrivee'] ?? '')) ?></p>
                                                <p class="mb-1"><strong>Date :</strong> <?= htmlspecialchars($dateDepart) ?> à <?= htmlspecialchars($heureDepart) ?></p>
                                                <p class="mb-1"><strong>Passager :</strong> <?= htmlspecialchars($passagerNom) ?></p>
                                                <?php if (!empty($reservation['passager_email'])): ?>
                                                    <p class="mb-1"><strong>Email :</strong> <?= htmlspecialchars($reservation['passager_email']) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($reservation['passager_telephone'])): ?>
                                                    <p class="mb-1"><strong>Téléphone :</strong> <?= htmlspecialchars($reservation['passager_telephone']) ?></p>
                                                <?php endif; ?>
                                                <p class="mb-1"><strong>Places réservées :</strong> <?= htmlspecialchars((string)($reservation['nb_places_reservees'] ?? '')) ?></p>
                                                <p class="mb-1"><strong>Statut :</strong> <?= htmlspecialchars($reservation['statut'] ?? '') ?></p>
                                                <?php if (!empty($reservationDate)): ?>
                                                    <p class="mb-1"><strong>Réservé le :</strong> <?= htmlspecialchars($reservationDate) ?></p>
                                                <?php endif; ?>
                                                <?php if ($reservationAnnulee): ?>
                                                    <span class="badge bg-danger mt-2">Réservation annulée par le passager</span>
                                                <?php elseif ($reservationConfirmee): ?>
                                                    <span class="badge bg-primary mt-2">Réservation confirmée</span>
                                                <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="reservation_id" value="<?= htmlspecialchars((string)($reservation['reservation_id'] ?? '')) ?>">
                                                        <input type="hidden" name="action" value="1">
                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-primary mt-2">
                                                            Valider la réservation
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($reservationSupport !== null): ?>
                            <hr class="my-4">
                            <h5 class="mb-3">Réservations sur mes trajets (en tant que chauffeur)</h5>
                            <p class="text-muted">Aucune réservation reçue sur vos trajets.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <h4 class="mb-0">Mes trajets publiés</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($trajetsPublies)): ?>
                            <p>Aucun trajet publié.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($trajetsPublies as $trajet): ?>
                                    <?php
                                    $trajetId = isset($trajet['covoiturage_id']) ? (int) $trajet['covoiturage_id'] : 0;
                                    $reservationsAssociees = $trajetId > 0 && isset($reservationsParTrajet[$trajetId]) ? $reservationsParTrajet[$trajetId] : [];
                                    $reservationsCount = count($reservationsAssociees);
                                    $dateDepartAffiche = '';
                                    if (!empty($trajet['date_depart']) && $trajet['date_depart'] !== '0000-00-00') {
                                        $dateDepartAffiche = date('d/m/Y', strtotime($trajet['date_depart']));
                                    }
                                    $heureDepartAffiche = '';
                                    if (!empty($trajet['heure_depart']) && $trajet['heure_depart'] !== '00:00:00') {
                                        $heureDepartAffiche = date('H:i', strtotime($trajet['heure_depart']));
                                    }
                                    ?>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header bg-success-subtle text-dark">
                                                Trajet publié
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-1"><strong>Départ:</strong> <?= htmlspecialchars($trajet['lieu_depart'] ?? '') ?></p>
                                                <p class="mb-1"><strong>Arrivée:</strong> <?= htmlspecialchars($trajet['lieu_arrivee'] ?? '') ?></p>
                                                <p class="mb-1">
                                                    <strong>Date:</strong>
                                                    <?= htmlspecialchars($dateDepartAffiche ?: ($trajet['date_depart'] ?? '')) ?>
                                                    <?php if ($heureDepartAffiche): ?>
                                                        à <?= htmlspecialchars($heureDepartAffiche) ?>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="mb-1"><strong>Prix/personne:</strong> <?= htmlspecialchars((string)($trajet['prix_personne'] ?? '')) ?> €</p>
                                                <p class="mb-1"><strong>Places:</strong> <?= htmlspecialchars((string)($trajet['nb_place'] ?? '')) ?></p>
                                                <hr>
                                                <p class="fw-semibold mb-2">
                                                    Réservations (<?= htmlspecialchars((string) $reservationsCount) ?>)
                                                </p>
                                                <?php if (empty($reservationsAssociees)): ?>
                                                    <p class="text-muted mb-0">Aucune réservation pour ce trajet pour le moment.</p>
                                                <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Passager</th>
                                                                    <th>Places</th>
                                                                    <th>Statut</th>
                                                                    <th>Date</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($reservationsAssociees as $reservation): ?>
                                                                    <?php
                                                                    $passagerNom = trim(($reservation['passager_prenom'] ?? '') . ' ' . ($reservation['passager_nom'] ?? ''));
                                                                    if ($passagerNom === '') {
                                                                        $passagerNom = $reservation['passager_pseudo'] ?? 'Passager inconnu';
                                                                    }
                                                                    $reservationDate = '';
                                                                    if (!empty($reservation['date_reservation']) && $reservation['date_reservation'] !== '0000-00-00 00:00:00') {
                                                                        $reservationDate = date('d/m/Y H:i', strtotime($reservation['date_reservation']));
                                                                    }
                                                                    $statutReservation = $reservation['statut'] ?? '';
                                                                    $statutReservationMin = mb_strtolower($statutReservation, 'UTF-8');
                                                                    $reservationAnnulee = $statutReservationMin === 'annulée';
                                                                    $rowClass = $reservationAnnulee ? 'table-secondary opacity-75' : '';
                                                                    $badgeClass = $reservationAnnulee ? 'bg-danger' : ($statutReservationMin === 'confirmée' ? 'bg-success' : 'bg-warning text-dark');
                                                                    ?>
                                                                    <tr class="<?= htmlspecialchars($rowClass) ?>">
                                                                        <td>
                                                                            <?= htmlspecialchars($passagerNom) ?>
                                                                            <?php if (!empty($reservation['passager_email'])): ?>
                                                                                <br><small><?= htmlspecialchars($reservation['passager_email']) ?></small>
                                                                            <?php endif; ?>
                                                                            <?php if (!empty($reservation['passager_telephone'])): ?>
                                                                                <br><small><?= htmlspecialchars($reservation['passager_telephone']) ?></small>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td><?= htmlspecialchars((string)($reservation['nb_places_reservees'] ?? '')) ?></td>
                                                                        <td>
                                                                            <span class="badge <?= htmlspecialchars($badgeClass) ?>">
                                                                                <?= htmlspecialchars($statutReservation) ?>
                                                                            </span>
                                                                        </td>
                                                                        <td><?= htmlspecialchars($reservationDate) ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

<script src="/assets/js/ajax-reservations.js"></script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>