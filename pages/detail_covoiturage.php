<?php
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../templates/header.php";

// Récupérer l'ID du covoiturage depuis l'URL
$covoiturage_id = isset($_GET['covoiturage_id']) ? intval($_GET['covoiturage_id']) : 0;

$covoiturage = null;
$error_message = null;

if ($covoiturage_id > 0) {
    try {
        // Récupérer tous les détails du covoiturage avec les informations du conducteur et de la voiture
        $query = $pdo->prepare("
            SELECT 
                c.*,
                u.user_id as conducteur_id,
                u.nom as conducteur_nom,
                u.prenom as conducteur_prenom,
                u.pseudo as conducteur_pseudo,
                u.email as conducteur_email,
                u.telephone as conducteur_telephone,
                u.adresse as conducteur_adresse,
                u.credits as conducteur_credits,
                v.voiture_id,
                v.modele as voiture_modele,
                v.immatriculation as voiture_immatriculation,
                v.couleur as voiture_couleur,
                v.date_premire_immatriculation as voiture_date_immatriculation,
                m.libelle as marque_libelle,
                e.libelle as energie_libelle
            FROM covoiturage c
            LEFT JOIN user u ON c.user_id = u.user_id
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            LEFT JOIN marque m ON v.marque_id = m.marque_id
            LEFT JOIN energie e ON v.energie_id = e.energie_id
            WHERE c.covoiturage_id = :covoiturage_id
        ");
        $query->execute(['covoiturage_id' => $covoiturage_id]);
        $covoiturage = $query->fetch(PDO::FETCH_ASSOC);

        if (!$covoiturage) {
            $error_message = "Ce covoiturage n'existe pas ou n'est plus disponible.";
        }
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la récupération des détails : " . $e->getMessage();
    }
} else {
    $error_message = "Aucun covoiturage spécifié.";
}

// Récupérer les réservations pour ce covoiturage (passagers)
$passagers = [];
if ($covoiturage) {
    try {
        $query_passagers = $pdo->prepare("
            SELECT r.*, u.nom, u.prenom, u.pseudo, u.email, u.telephone
            FROM reservations r
            LEFT JOIN user u ON r.user_id = u.user_id
            WHERE r.covoiturage_id = :covoiturage_id
            ORDER BY r.date_reservation ASC
        ");
        $query_passagers->execute(['covoiturage_id' => $covoiturage_id]);
        $passagers = $query_passagers->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Si la table reservations n'existe pas, on continue sans erreur
        $passagers = [];
    }
}
?>

<?php if ($error_message): ?>
    <section class="py-5">
        <div class="container">
            <div class="alert alert-danger text-center" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong><?= htmlspecialchars($error_message) ?></strong>
            </div>
            <div class="text-center mt-4">
                <a href="/pages/trajets.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-2"></i>Retour aux trajets
                </a>
            </div>
        </div>
    </section>
<?php elseif ($covoiturage): ?>
    <!-- Section Détails du Covoiturage -->
    <section class="py-5 detail-covoiturage-section">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="pt-4 fw-bold">Détails du covoiturage</h3>
                </div>
            </div>

            <div class="row">
                <!-- Colonne principale - Informations du trajet -->
                <div class="col-lg-8 mb-4">
                    <!-- Carte Trajet -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-light text-dark">
                            <h4 class="mb-0">
                                <i class="bi bi-route me-2"></i>Informations du trajet
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-dark">
                                        <i class="bi bi-geo-alt-fill me-2"></i>Départ
                                    </h6>
                                    <p class="fs-5 fw-bold mb-1"><?= htmlspecialchars(ucfirst($covoiturage['lieu_depart'])) ?></p>
                                    <?php if (!empty($covoiturage['date_depart']) && $covoiturage['date_depart'] != '0000-00-00'): ?>
                                        <p class="text-muted mb-0">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?= date('d/m/Y', strtotime($covoiturage['date_depart'])) ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($covoiturage['heure_depart']) && $covoiturage['heure_depart'] != '0000-00-00'): ?>
                                        <p class="text-muted mb-0">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-dark">
                                        <i class="bi bi-geo-alt me-2"></i>Arrivée
                                    </h6>
                                    <p class="fs-5 fw-bold mb-1"><?= htmlspecialchars(ucfirst($covoiturage['lieu_arrivee'])) ?></p>
                                    <?php if (!empty($covoiturage['date_arrivee']) && $covoiturage['date_arrivee'] != '0000-00-00'): ?>
                                        <p class="text-muted mb-0">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?= date('d/m/Y', strtotime($covoiturage['date_arrivee'])) ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($covoiturage['heure_arrivee']) && $covoiturage['heure_arrivee'] != '0000-00-00'): ?>
                                        <p class="text-muted mb-0">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('H:i', strtotime($covoiturage['heure_arrivee'])) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($covoiturage['duree'])): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <span class="badge bg-info fs-6">
                                            <i class="bi bi-stopwatch me-1"></i>
                                            Durée estimée : <?= $covoiturage['duree'] ?> minutes
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Carte Conducteur -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-light text-dark">
                            <h4 class="mb-0">
                                <i class="bi bi-person-circle me-2"></i>Conducteur
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="/assets/img/profil.jpg" class="rounded-circle me-3" alt="Photo de profil" width="80" height="80">
                                <div>
                                    <h5 class="mb-1">
                                        <?= htmlspecialchars($covoiturage['conducteur_prenom'] . ' ' . $covoiturage['conducteur_nom']) ?>
                                    </h5>
                                    <?php if (!empty($covoiturage['conducteur_pseudo'])): ?>
                                        <p class="text-muted mb-0">@<?= htmlspecialchars($covoiturage['conducteur_pseudo']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="row">
                                <?php if (!empty($covoiturage['conducteur_email'])): ?>
                                    <div class="col-md-6 mb-2">
                                        <i class="bi bi-envelope me-2"></i>
                                        <strong>Email :</strong> <?= htmlspecialchars($covoiturage['conducteur_email']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($covoiturage['conducteur_telephone'])): ?>
                                    <div class="col-md-6 mb-2">
                                        <i class="bi bi-telephone me-2"></i>
                                        <strong>Téléphone :</strong> <?= htmlspecialchars($covoiturage['conducteur_telephone']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($covoiturage['conducteur_adresse'])): ?>
                                    <div class="col-md-12 mb-2">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        <strong>Adresse :</strong> <?= htmlspecialchars($covoiturage['conducteur_adresse']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Véhicule -->
                    <?php if (!empty($covoiturage['voiture_modele'])): ?>
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-light text-dark">
                                <h4 class="mb-0">
                                    <i class="bi bi-car-front me-2"></i>Véhicule
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <strong>Marque :</strong> <?= htmlspecialchars($covoiturage['marque_libelle'] ?? 'N/A') ?>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Modèle :</strong> <?= htmlspecialchars($covoiturage['voiture_modele']) ?>
                                    </div>
                                    <?php if (!empty($covoiturage['energie_libelle'])): ?>
                                        <div class="col-md-6 mb-2">
                                            <strong>Énergie :</strong>
                                            <span class="badge bg-success"><?= htmlspecialchars($covoiturage['energie_libelle']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($covoiturage['voiture_couleur'])): ?>
                                        <div class="col-md-6 mb-2">
                                            <strong>Couleur :</strong> <?= htmlspecialchars($covoiturage['voiture_couleur']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($covoiturage['voiture_immatriculation'])): ?>
                                        <div class="col-md-6 mb-2">
                                            <strong>Immatriculation :</strong> <?= htmlspecialchars($covoiturage['voiture_immatriculation']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-center mb-3">
                                <a href="/pages/trajets.php#suggestions" class="btn btn-primary">
                                    <i class="bi bi-lightbulb me-2"></i>Retour aux suggestions
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Passagers -->
                    <?php if (!empty($passagers)): ?>
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-light text-dark">
                                <h4 class="mb-0">
                                    <i class="bi bi-people me-2"></i>Passagers (<?= count($passagers) ?>)
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($passagers as $passager): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <img src="/assets/img/profil.jpg" class="rounded-circle me-2" alt="Photo" width="40" height="40">
                                                <div>
                                                    <strong><?= htmlspecialchars($passager['prenom'] . ' ' . $passager['nom']) ?></strong>
                                                    <?php if (!empty($passager['pseudo'])): ?>
                                                        <br><small class="text-muted">@<?= htmlspecialchars($passager['pseudo']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Colonne latérale - Résumé et actions -->
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 20px;">
                        <div class="card-header bg-light text-dark">
                            <h5 class="mb-0">
                                <i class="bi bi-info-circle me-2"></i>Résumé
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-dark">Prix par personne</h6>
                                <p class="fs-3 fw-bold text-success">
                                    <?= number_format($covoiturage['prix_personne'], 0) ?> crédits
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-dark">Places disponibles</h6>
                                <?php
                                $nb_places = $covoiturage['nb_place'];
                                $badge_class = '';
                                if ($nb_places >= 3) {
                                    $badge_class = 'bg-success';
                                } elseif ($nb_places == 2) {
                                    $badge_class = 'bg-warning text-dark';
                                } else {
                                    $badge_class = 'bg-danger';
                                }
                                ?>
                                <span class="badge <?= $badge_class ?> fs-6">
                                    <i class="bi bi-people me-1"></i>
                                    <?= $nb_places ?> place<?= $nb_places > 1 ? 's' : '' ?>
                                </span>
                                <?php if ($nb_places == 1): ?>
                                    <p class="text-danger fw-bold mt-2 mb-0">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Dernière place !
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-dark">Statut</h6>
                                <?php
                                $statut_labels = [
                                    0 => ['label' => 'Indisponible', 'class' => 'bg-secondary'],
                                    1 => ['label' => 'Disponible', 'class' => 'bg-success'],
                                    2 => ['label' => 'En attente', 'class' => 'bg-warning text-dark'],
                                    3 => ['label' => 'Terminé', 'class' => 'bg-dark']
                                ];
                                $statut = $covoiturage['statut'] ?? 0;
                                $statut_info = $statut_labels[$statut] ?? $statut_labels[0];
                                ?>
                                <span class="badge <?= $statut_info['class'] ?> fs-6">
                                    <?= $statut_info['label'] ?>
                                </span>
                            </div>

                            <hr>

                            <?php if (isUserConnected()): ?>
                                <?php if ($covoiturage['statut'] == 1 && $covoiturage['nb_place'] > 0): ?>
                                    <button class="btn btn-primary w-100 mb-2" onclick="alert('Fonctionnalité de réservation à venir')">
                                        <i class="bi bi-check-circle me-2"></i>Réserver une place
                                    </button>
                                <?php elseif ($covoiturage['statut'] == 2): ?>
                                    <button class="btn btn-warning w-100 mb-2" disabled>
                                        <i class="bi bi-clock-history me-2"></i>Trajet en attente
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100 mb-2" disabled>
                                        <i class="bi bi-x-circle me-2"></i>Non disponible
                                    </button>
                                <?php endif; ?>

                                <button class="btn btn-secondary w-100" onclick="alert('Fonctionnalité de contact à venir')">
                                    <i class="bi bi-envelope me-2"></i>Contacter le conducteur
                                </button>
                            <?php else: ?>
                                <a href="/login.php" class="btn btn-primary w-100 mb-2">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter pour réserver
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>



<?php require_once __DIR__ . "/../templates/footer.php";
?>