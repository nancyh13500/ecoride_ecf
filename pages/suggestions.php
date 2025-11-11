<?php
require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/session.php";

// Récupérer les villes disponibles depuis la base de données
$villes_depart = [];
$villes_arrivee = [];

try {
    // Récupérer les villes de départ
    $query_depart = $pdo->prepare("SELECT DISTINCT lieu_depart FROM covoiturage WHERE statut = 1 ORDER BY lieu_depart ASC");
    $query_depart->execute();
    $villes_depart = $query_depart->fetchAll(PDO::FETCH_COLUMN);

    // Récupérer les villes d'arrivée
    $query_arrivee = $pdo->prepare("SELECT DISTINCT lieu_arrivee FROM covoiturage WHERE statut = 1 ORDER BY lieu_arrivee ASC");
    $query_arrivee->execute();
    $villes_arrivee = $query_arrivee->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // En cas d'erreur, on continue avec des listes vides
    $villes_depart = [];
    $villes_arrivee = [];
}

// Récupérer les paramètres de recherche depuis l'URL
$search_depart = $_GET['depart'] ?? '';
$search_arrivee = $_GET['arrivee'] ?? '';
$search_date = $_GET['date'] ?? '';

// Récupérer tous les trajets disponibles
$covoiturages_suggestion = [];
$total_suggestions = 0;

try {
    // Compter le total des trajets disponibles
    $count_query = $pdo->prepare("SELECT COUNT(*) as total FROM covoiturage WHERE statut = 1 AND date_depart >= CURDATE()");
    $count_query->execute();
    $total_suggestions = $count_query->fetch(PDO::FETCH_ASSOC)['total'];

    // Récupérer tous les trajets disponibles
    $query_suggestion = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, v.modele, m.libelle AS marque_libelle
        FROM covoiturage c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
        LEFT JOIN marque m ON v.marque_id = m.marque_id
        WHERE c.statut = 1 AND c.date_depart >= CURDATE()
        ORDER BY c.date_depart ASC, c.heure_depart ASC
    ");
    $query_suggestion->execute();
    $covoiturages_suggestion = $query_suggestion->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En cas d'erreur, on continue avec une liste vide
    $covoiturages_suggestion = [];
    $total_suggestions = 0;
}

?>

<!-- Hero Section -->
<section class="hero">
    <div class="background-img"></div>
    <div class="content px-4 py-5 my-5 text-center">
        <h1 class="fw-bold">Trouvez un covoiturage</h1>
        <p class="lead mb-4">La solution accessible et durable pour tous.</p>
        <div class="col-lg-6 mx-auto">
            <form method="POST" action="">
                <div class="search-bar row">
                    <div class="search-field col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                            <input type="text" name="depart" class="form-control border-start-0 text-center" placeholder="Ville de départ" list="villes-depart" value="<?= htmlspecialchars($search_depart) ?>" required>
                            <datalist id="villes-depart">
                                <?php foreach ($villes_depart as $ville): ?>
                                    <option value="<?= htmlspecialchars($ville) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="search-field col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt text-primary"></i></span>
                            <input type="text" name="arrivee" class="form-control border-start-0 text-center" placeholder="Ville d'arrivée" list="villes-arrivee" value="<?= htmlspecialchars($search_arrivee) ?>" required>
                            <datalist id="villes-arrivee">
                                <?php foreach ($villes_arrivee as $ville): ?>
                                    <option value="<?= htmlspecialchars($ville) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="search-field col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar text-primary"></i></span>
                            <input type="date" name="date" class="form-control border-start-0 text-center" value="<?= htmlspecialchars($search_date) ?>">
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <button type="submit" name="search_trajet" class="btn btn-primary w-50">Lancer la recherche<i class="bi bi-search ms-2"></i></button>
                </div>
            </form>
        </div>
    </div>
</section>
<!-- Results Section -->
<div class="result-suggestions text-center">
    <div class="bg-dark text-white p-5 mt-0">
        <h2>Trajets disponibles</h2>
        <p class="mb-0"><?= $total_suggestions ?> trajet<?= $total_suggestions > 1 ? 's' : '' ?> trouvé<?= $total_suggestions > 1 ? 's' : '' ?></p>
    </div>
</div>

<section id="results" class="results bg-light py-5">
    <div class="container">
        <?php if (!empty($covoiturages_suggestion)): ?>
            <div class="suggestions-section">
                <div class="row">
                    <?php foreach ($covoiturages_suggestion as $covoiturage): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-dark text-white text-center border-light">
                                    <h6 class="mb-0"><i class="bi bi-car-front me-2"></i>Trajet disponible</h6>
                                </div>
                                <div class="card-body">
                                    <h6 class="text-primary mb-2"><i class="bi bi-geo-alt-fill me-1"></i>Trajet</h6>
                                    <p class="mb-2"><strong><?= htmlspecialchars($covoiturage['lieu_depart']) ?></strong> → <strong><?= htmlspecialchars($covoiturage['lieu_arrivee']) ?></strong></p>

                                    <h6 class="text-primary mb-2"><i class="bi bi-calendar-event me-1"></i>Départ</h6>
                                    <p class="mb-2"><?= date('d/m/Y', strtotime($covoiturage['date_depart'])) ?> à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?></p>

                                    <h6 class="text-primary mb-2"><i class="bi bi-person-circle me-1"></i>Conducteur</h6>
                                    <p class="mb-2"><?= htmlspecialchars($covoiturage['prenom'] . ' ' . $covoiturage['nom']) ?></p>

                                    <div class="row mt-3 text-center">
                                        <div class="col-6">
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
                                            <span class="badge bg-warning text-dark"><i class="bi bi-coin me-1"></i><?= number_format($covoiturage['prix_personne'], 0) ?> cédits</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <?php if (isUserConnected()): ?>
                                        <a href="/pages/detail_covoiturage.php?covoiturage_id=<?= $covoiturage['covoiturage_id'] ?>" class="btn btn-secondary btn-sm me-2">
                                            <i class="bi bi-eye me-1"></i>Voir détails
                                        </a>
                                    <?php else: ?>
                                        <a href="../login.php" class="btn btn-secondary btn-sm">Se connecter</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-4">
                    <a href="trajets.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-2"></i>Retour aux trajets
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Aucun trajet disponible</strong><br>
                Il n'y a actuellement aucun trajet disponible.
            </div>

            <div class="text-center mt-4">
                <a href="trajets.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-2"></i>Retour aux trajets
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php";
?>