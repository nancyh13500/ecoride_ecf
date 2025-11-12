<?php
require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/session.php";

// Vérifier si l'utilisateur est connecté
if (!isUserConnected()) {
    header("Location: /login.php");
    exit();
}

$user = $_SESSION['user'];
$success_message = '';
$error_message = '';

// Déterminer l'ID du covoiturage (GET par défaut, POST lors de la réservation)
$covoiturage_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    $covoiturage_id = isset($_POST['covoiturage_id']) ? intval($_POST['covoiturage_id']) : $covoiturage_id;
}

if ($covoiturage_id <= 0) {
    header("Location: trajets.php");
    exit();
}

// Traitement de la réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver']) && empty($error_message)) {
    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }

        $queryReservation = $pdo->prepare("
            SELECT covoiturage_id, nb_place, prix_personne, user_id, statut
            FROM covoiturage
            WHERE covoiturage_id = :id
            FOR UPDATE
        ");
        $queryReservation->execute(['id' => $covoiturage_id]);
        $covoiturageRow = $queryReservation->fetch(PDO::FETCH_ASSOC);

        if (!$covoiturageRow) {
            throw new Exception("Covoiturage introuvable.");
        }

        if ((int)$covoiturageRow['user_id'] === (int)$user['user_id']) {
            throw new Exception("Vous ne pouvez pas réserver votre propre covoiturage.");
        }

        if ((int)$covoiturageRow['statut'] !== 1) {
            throw new Exception("Ce covoiturage n'est plus disponible.");
        }

        if ((int)$covoiturageRow['nb_place'] <= 0) {
            throw new Exception("Plus de places disponibles pour ce covoiturage.");
        }

        $checkReservation = $pdo->prepare("
            SELECT reservation_id
            FROM reservations
            WHERE user_id = :user_id AND covoiturage_id = :covoiturage_id
            LIMIT 1
        ");
        $checkReservation->execute([
            'user_id' => $user['user_id'],
            'covoiturage_id' => $covoiturage_id,
        ]);

        if ($checkReservation->fetch()) {
            throw new Exception("Vous avez déjà réservé une place pour ce covoiturage.");
        }

        $nb_places_reservees = 1;
        $prix_total = (float)$covoiturageRow['prix_personne'] * $nb_places_reservees;

        $insertReservation = $pdo->prepare("
            INSERT INTO reservations (user_id, covoiturage_id, nb_places_reservees, prix_total, statut)
            VALUES (:user_id, :covoiturage_id, :nb_places_reservees, :prix_total, 'En attente')
        ");
        $insertReservation->execute([
            'user_id' => $user['user_id'],
            'covoiturage_id' => $covoiturage_id,
            'nb_places_reservees' => $nb_places_reservees,
            'prix_total' => $prix_total,
        ]);

        $updateCovoiturage = $pdo->prepare("
            UPDATE covoiturage
            SET nb_place = nb_place - :nb_places
            WHERE covoiturage_id = :covoiturage_id AND nb_place >= :nb_places_check
        ");
        $updateCovoiturage->execute([
            'nb_places' => $nb_places_reservees,
            'nb_places_check' => $nb_places_reservees,
            'covoiturage_id' => $covoiturage_id,
        ]);

        if ($updateCovoiturage->rowCount() === 0) {
            throw new Exception("La réservation n'a pas pu être confirmée. Veuillez réessayer.");
        }

        $pdo->commit();

        header("Location: mes_reservations.php?success=1");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}

// Récupérer les détails du covoiturage
$covoiturage = null;

try {
    $query = $pdo->prepare("
        SELECT c.*, 
               u.nom, u.prenom, u.email, u.telephone, u.pseudo,
               v.modele, v.immatriculation, v.couleur, v.energie,
               m.libelle AS marque_libelle,
               e.libelle AS energie_libelle
        FROM covoiturage c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
        LEFT JOIN marque m ON v.marque_id = m.marque_id
        LEFT JOIN energie e ON v.energie_id = e.energie_id
        WHERE c.covoiturage_id = :id
    ");
    $query->execute(['id' => $covoiturage_id]);
    $covoiturage = $query->fetch(PDO::FETCH_ASSOC);

    if (!$covoiturage) {
        $error_message = "Covoiturage introuvable.";
    }
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des détails : " . $e->getMessage();
}

// Si covoiturage introuvable, rediriger
if (!$covoiturage) {
    header("Location: trajets.php?error=notfound");
    exit();
}

// Calculer les badges pour les places
$nb_places = (int)$covoiturage['nb_place'];
$badge_class = 'badge-places badge-places--red';
if ($nb_places >= 3) {
    $badge_class = 'badge-places badge-places--green';
} elseif ($nb_places == 2) {
    $badge_class = 'badge-places badge-places--orange';
}

$estMonCovoiturage = (int)$covoiturage['user_id'] === (int)$user['user_id'];
$dejaReserve = false;
try {
    $checkReservationDisplay = $pdo->prepare("
        SELECT reservation_id
        FROM reservations
        WHERE user_id = :user_id AND covoiturage_id = :covoiturage_id
        LIMIT 1
    ");
    $checkReservationDisplay->execute([
        'user_id' => $user['user_id'],
        'covoiturage_id' => $covoiturage_id,
    ]);
    $dejaReserve = (bool)$checkReservationDisplay->fetchColumn();
} catch (PDOException $e) {
    // Si la table n'existe pas encore, on ignore l'erreur pour l'affichage
    $dejaReserve = false;
}
$peutReserver = !$estMonCovoiturage && !$dejaReserve && (int)$covoiturage['statut'] === 1 && $nb_places > 0;
?>

<section class="hero w-100 px-4 py-5">
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <!-- Bouton retour -->
                <div class="mb-4">
                    <a href="trajets.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Retour aux trajets
                    </a>
                </div>

                <!-- Carte principale -->
                <div class="card rounded-3 shadow-lg">
                    <div class="card-header bg-dark text-white text-center py-4">
                        <h2 class="mb-0">
                            <i class="bi bi-car-front me-2"></i>Détails du covoiturage
                        </h2>
                    </div>
                    <div class="card-body p-4 p-md-5">

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Section Trajet -->
                        <div class="mb-5">
                            <h3 class="text-primary mb-4">
                                <i class="bi bi-geo-alt-fill me-2"></i>Informations du trajet
                            </h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2"><i class="bi bi-geo-alt me-1"></i>Ville de départ</h6>
                                    <p class="fs-5 fw-bold"><?= htmlspecialchars($covoiturage['lieu_depart']) ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2"><i class="bi bi-geo-alt me-1"></i>Ville d'arrivée</h6>
                                    <p class="fs-5 fw-bold"><?= htmlspecialchars($covoiturage['lieu_arrivee']) ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2"><i class="bi bi-calendar-event me-1"></i>Date et heure de départ</h6>
                                    <p class="fs-5">
                                        <?= date('d/m/Y', strtotime($covoiturage['date_depart'])) ?>
                                        à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2"><i class="bi bi-calendar-check me-1"></i>Date et heure d'arrivée</h6>
                                    <p class="fs-5">
                                        <?= date('d/m/Y', strtotime($covoiturage['date_arrivee'])) ?>
                                        à <?= date('H:i', strtotime($covoiturage['heure_arrivee'])) ?>
                                    </p>
                                </div>
                            </div>
                            <!-- CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ -->
                            <!--
                            <?php if (!empty($covoiturage['duree'])): ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted mb-2"><i class="bi bi-stopwatch me-1"></i>Durée estimée</h6>
                                        <p class="fs-5"><?= $covoiturage['duree'] ?> minutes</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            -->
                        </div>

                        <hr class="my-4">

                        <!-- Section Conducteur -->
                        <div class="mb-5">
                            <h3 class="text-primary mb-4">
                                <i class="bi bi-person-circle me-2"></i>Informations du conducteur
                            </h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2">Nom complet</h6>
                                    <p class="fs-5"><?= htmlspecialchars(trim($covoiturage['prenom'] . ' ' . $covoiturage['nom'])) ?></p>
                                </div>
                                <?php if (!empty($covoiturage['pseudo'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted mb-2">Pseudo</h6>
                                        <p class="fs-5"><?= htmlspecialchars($covoiturage['pseudo']) ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($covoiturage['email'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted mb-2"><i class="bi bi-envelope me-1"></i>Email</h6>
                                        <p class="fs-5"><?= htmlspecialchars($covoiturage['email']) ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($covoiturage['telephone'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted mb-2"><i class="bi bi-telephone me-1"></i>Téléphone</h6>
                                        <p class="fs-5"><?= htmlspecialchars($covoiturage['telephone']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Section Véhicule -->
                        <?php if (!empty($covoiturage['modele']) || !empty($covoiturage['marque_libelle'])): ?>
                            <div class="mb-5">
                                <h3 class="text-primary mb-4">
                                    <i class="bi bi-car-front me-2"></i>Informations du véhicule
                                </h3>
                                <div class="row">
                                    <?php if (!empty($covoiturage['marque_libelle']) && !empty($covoiturage['modele'])): ?>
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-muted mb-2">Véhicule</h6>
                                            <p class="fs-5"><?= htmlspecialchars($covoiturage['marque_libelle'] . ' ' . $covoiturage['modele']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($covoiturage['immatriculation'])): ?>
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-muted mb-2">Immatriculation</h6>
                                            <p class="fs-5"><?= htmlspecialchars($covoiturage['immatriculation']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($covoiturage['couleur'])): ?>
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-muted mb-2">Couleur</h6>
                                            <p class="fs-5"><?= htmlspecialchars($covoiturage['couleur']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($covoiturage['energie_libelle'])): ?>
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-muted mb-2">Énergie</h6>
                                            <p class="fs-5"><?= htmlspecialchars($covoiturage['energie_libelle']) ?></p>
                                        </div>
                                    <?php elseif (!empty($covoiturage['energie'])): ?>
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-muted mb-2">Énergie</h6>
                                            <p class="fs-5"><?= htmlspecialchars($covoiturage['energie']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <hr class="my-4">
                        <?php endif; ?>

                        <!-- Section Places et Prix -->
                        <div class="mb-5">
                            <h3 class="text-primary mb-4">
                                <i class="bi bi-info-circle me-2"></i>Informations pratiques
                            </h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2"><i class="bi bi-people me-1"></i>Places disponibles</h6>
                                    <p class="fs-5">
                                        <span class="badge <?= $badge_class ?> fs-6">
                                            <?= $nb_places ?> place<?= $nb_places > 1 ? 's' : '' ?>
                                        </span>
                                        <?php if ($nb_places == 1): ?>
                                            <small class="text-danger fw-bold d-block mt-2">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Dernière place disponible !
                                            </small>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2"><i class="bi bi-coin me-1"></i>Crédits par personne</h6>
                                    <p class="fs-5 fw-bold text-success"><?= number_format($covoiturage['prix_personne'], 0) ?> crédits</p>
                                </div>
                            </div>
                            <?php
                            $statut_libelle = '';
                            $statut_class = '';
                            switch ($covoiturage['statut']) {
                                case 1:
                                    $statut_libelle = 'Disponible';
                                    $statut_class = 'bg-success';
                                    break;
                                case 2:
                                    $statut_libelle = 'En attente';
                                    $statut_class = 'bg-warning text-dark';
                                    break;
                                case 3:
                                    $statut_libelle = 'Terminé';
                                    $statut_class = 'bg-secondary';
                                    break;
                                default:
                                    $statut_libelle = 'Inconnu';
                                    $statut_class = 'bg-dark';
                            }
                            ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2">Statut</h6>
                                    <p class="fs-5">
                                        <span class="badge <?= $statut_class ?>"><?= $statut_libelle ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="text-center mt-5">
                            <a href="trajets.php" class="btn btn-secondary btn-lg me-3">
                                <i class="bi bi-arrow-left me-2"></i>Retour
                            </a>
                            <?php if ($peutReserver): ?>
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="covoiturage_id" value="<?= htmlspecialchars((string) $covoiturage_id) ?>">
                                    <button type="submit" name="reserver" class="btn btn-primary btn-lg" onclick="return confirm('Êtes-vous sûr de vouloir réserver une place pour ce covoiturage ?');"></i>Réserver une place
                                    </button>
                                </form>
                            <?php elseif ($dejaReserve): ?>
                                <button class="btn btn-success btn-lg" disabled>
                                    <i class="bi bi-check-circle me-2"></i>Déjà réservé
                                </button>
                            <?php elseif ($estMonCovoiturage): ?>
                                <button class="btn btn-outline-secondary btn-lg" disabled>
                                    <i class="bi bi-info-circle me-2"></i>Votre covoiturage
                                </button>
                            <?php elseif ((int)$covoiturage['statut'] !== 1): ?>
                                <button class="btn btn-outline-secondary btn-lg" disabled>
                                    <i class="bi bi-slash-circle me-2"></i>Non disponible
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary btn-lg" disabled>
                                    <i class="bi bi-dash-circle me-2"></i>Complet
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php";
?>