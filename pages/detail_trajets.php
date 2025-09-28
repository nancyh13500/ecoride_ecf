<?php
require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/session.php";

// Récupérer l'ID du trajet depuis l'URL
$trajet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($trajet_id <= 0) {
    header("Location: trajets.php");
    exit();
}

// Récupérer les détails du trajet
$trajet = null;
try {
    $query = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, u.email, u.telephone, v.modele, v.couleur, v.annee, m.libelle AS marque_libelle
        FROM covoiturage c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
        LEFT JOIN marque m ON v.marque_id = m.marque_id
        WHERE c.covoiturage_id = :id AND c.date_depart >= CURDATE()
    ");
    $query->execute(['id' => $trajet_id]);
    $trajet = $query->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $trajet = null;
}

// Si le trajet n'existe pas, rediriger
if (!$trajet) {
    header("Location: trajets.php");
    exit();
}

?>

<!-- Hero Section -->
<section class="hero">
    <div class="background-img"></div>
    <div class="content px-4 py-5 my-5 text-center">
        <h1 class="fw-bold">Détails du trajet</h1>
        <p class="lead mb-4">Informations complètes sur ce covoiturage.</p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container mt-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="trajets.php">Trajets</a></li>
            <li class="breadcrumb-item active" aria-current="page">Détails</li>
        </ol>
    </nav>
</div>

<section class="results bg-light py-5">
    <div class="container">
        <div class="row">
            <!-- Carte du trajet -->
            <div class="col-lg-8 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-dark text-white text-center border-light">
                        <h5 class="mb-0"><i class="bi bi-map me-2"></i>Itinéraire du trajet</h5>
                    </div>
                    <div class="card-body">
                        <!-- Carte Google Maps (placeholder) -->
                        <div class="map-container" style="height: 400px; background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                            <div class="text-center text-muted">
                                <i class="bi bi-geo-alt" style="font-size: 3rem; color: #1976d2;"></i>
                                <h5 class="mt-3">Carte du trajet</h5>
                                <p class="mb-0"><?= htmlspecialchars($trajet['lieu_depart']) ?> → <?= htmlspecialchars($trajet['lieu_arrivee']) ?></p>
                                <small>Intégration Google Maps à venir</small>
                            </div>
                        </div>

                        <!-- Informations du trajet -->
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-2"><i class="bi bi-geo-alt-fill me-1"></i>Départ</h6>
                                <p class="mb-3"><strong><?= htmlspecialchars($trajet['lieu_depart']) ?></strong></p>
                                <p class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?= date('d/m/Y', strtotime($trajet['date_depart'])) ?> à <?= date('H:i', strtotime($trajet['heure_depart'])) ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary mb-2"><i class="bi bi-geo-alt me-1"></i>Arrivée</h6>
                                <p class="mb-3"><strong><?= htmlspecialchars($trajet['lieu_arrivee']) ?></strong></p>
                                <p class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?= date('d/m/Y', strtotime($trajet['date_arrivee'])) ?> à <?= date('H:i', strtotime($trajet['heure_arrivee'])) ?>
                                </p>
                            </div>
                        </div>

                        <?php if (!empty($trajet['duree'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-stopwatch me-2"></i>
                                <strong>Durée estimée :</strong> <?= $trajet['duree'] ?> minutes
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Informations et réservation -->
            <div class="col-lg-4 mb-4">
                <!-- Carte des informations -->
                <div class="card h-100 mb-4">
                    <div class="card-header bg-dark text-white text-center border-light">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informations</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="text-primary mb-2"><i class="bi bi-person-circle me-1"></i>Conducteur</h6>
                        <p class="mb-3"><strong><?= htmlspecialchars($trajet['prenom'] . ' ' . $trajet['nom']) ?></strong></p>

                        <?php if (!empty($trajet['email'])): ?>
                            <h6 class="text-primary mb-2"><i class="bi bi-envelope me-1"></i>Contact</h6>
                            <p class="mb-3"><?= htmlspecialchars($trajet['email']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($trajet['telephone'])): ?>
                            <h6 class="text-primary mb-2"><i class="bi bi-telephone me-1"></i>Téléphone</h6>
                            <p class="mb-3"><?= htmlspecialchars($trajet['telephone']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($trajet['modele']) && !empty($trajet['marque_libelle'])): ?>
                            <h6 class="text-primary mb-2"><i class="bi bi-car-front me-1"></i>Véhicule</h6>
                            <p class="mb-2"><?= htmlspecialchars($trajet['marque_libelle'] . ' ' . $trajet['modele']) ?></p>
                            <?php if (!empty($trajet['couleur'])): ?>
                                <p class="mb-2"><small class="text-muted">Couleur : <?= htmlspecialchars($trajet['couleur']) ?></small></p>
                            <?php endif; ?>
                            <?php if (!empty($trajet['annee'])): ?>
                                <p class="mb-3"><small class="text-muted">Année : <?= htmlspecialchars($trajet['annee']) ?></small></p>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="row mt-3 text-center">
                            <div class="col-6">
                                <?php
                                $nb_places = $trajet['nb_place'];
                                $badge_class = '';
                                if ($nb_places >= 3) {
                                    $badge_class = 'bg-success';
                                } elseif ($nb_places == 2) {
                                    $badge_class = 'bg-warning text-dark';
                                } else {
                                    $badge_class = 'bg-danger';
                                }
                                ?>
                                <span class="badge <?= $badge_class ?>"><i class="bi bi-people me-1"></i><?= $nb_places ?> place<?= $nb_places > 1 ? 's' : '' ?></span>
                                <?php if ($nb_places == 1): ?>
                                    <div class="text-center mb-1">
                                        <small class="text-danger fw-bold">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Dernière place !!!
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-6">
                                <span class="badge bg-warning text-dark"><i class="bi bi-coin me-1"></i><?= number_format($trajet['prix_personne'], 2) ?>€</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte de réservation -->
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Réserver</h5>
                    </div>
                    <div class="card-body text-center">
                        <h6 class="mb-3">Prix par personne</h6>
                        <h4 class="text-primary mb-4"><?= number_format($trajet['prix_personne'], 2) ?>€</h4>

                        <?php if (isUserConnected()): ?>
                            <?php if ($trajet['nb_place'] > 0): ?>
                                <button class="btn btn-primary btn-lg w-100 mb-3">
                                    <i class="bi bi-check-circle me-2"></i>Réserver ce trajet
                                </button>
                                <small class="text-muted">Vous serez redirigé vers le processus de réservation</small>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg w-100 mb-3" disabled>
                                    <i class="bi bi-x-circle me-2"></i>Complet
                                </button>
                                <small class="text-muted">Ce trajet n'a plus de places disponibles</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="../login.php" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter pour réserver
                            </a>
                            <small class="text-muted">Vous devez être connecté pour réserver</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Boutons de navigation -->
        <div class="text-center mt-4">
            <a href="trajets.php" class="btn btn-secondary me-3">
                <i class="bi bi-arrow-left me-2"></i>Retour aux trajets
            </a>
            <a href="suggestions.php" class="btn btn-outline-primary">
                <i class="bi bi-lightbulb me-2"></i>Voir les suggestions
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php";
?>