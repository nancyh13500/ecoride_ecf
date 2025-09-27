<?php
require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/session.php";

// Gérer le démarrage du trajet depuis cette page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_trajet_from_hero'])) {
    if (isUserConnected()) {
        $user = $_SESSION['user'];
        $trajet_id = intval($_POST['start_trajet_from_hero']);

        try {
            $query = $pdo->prepare("UPDATE covoiturage SET statut = 2 WHERE covoiturage_id = :id AND user_id = :user_id");
            $query->execute(['id' => $trajet_id, 'user_id' => $user['user_id']]);
            header("Location: mes_trajets.php?started=1");
            exit();
        } catch (PDOException $e) {
            $error_message = "Erreur lors du démarrage du trajet : " . $e->getMessage();
        }
    }
}

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

// Traitement de la recherche depuis le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_trajet'])) {
    $depart = $_POST['depart'] ?? '';
    $arrivee = $_POST['arrivee'] ?? '';
    $date = $_POST['date'] ?? '';

    // Redirection vers la même page avec les paramètres de recherche
    $params = http_build_query([
        'depart' => $depart,
        'arrivee' => $arrivee,
        'date' => $date
    ]);

    header("Location: trajets.php?$params");
    exit();
}

// Récupérer les paramètres de recherche depuis l'URL
$search_depart = $_GET['depart'] ?? '';
$search_arrivee = $_GET['arrivee'] ?? '';
$search_date = $_GET['date'] ?? '';

// Rechercher les covoiturages selon les critères de recherche
$covoiturages_recherche = [];
$has_search_criteria = !empty($search_depart) && !empty($search_arrivee);

if ($has_search_criteria) {
    try {
        $query_search = $pdo->prepare("
            SELECT c.*, u.nom, u.prenom, v.modele, m.libelle AS marque_libelle
            FROM covoiturage c
            LEFT JOIN user u ON c.user_id = u.user_id
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            LEFT JOIN marque m ON v.marque_id = m.marque_id
            WHERE c.statut = 1 AND c.nb_place > 0 
            AND c.lieu_depart LIKE :depart 
            AND c.lieu_arrivee LIKE :arrivee
            AND c.date_depart >= CURDATE()
        ");

        // Ajouter la condition de date si spécifiée
        if (!empty($search_date)) {
            $query_search = $pdo->prepare("
                SELECT c.*, u.nom, u.prenom, v.modele, m.libelle AS marque_libelle
                FROM covoiturage c
                LEFT JOIN user u ON c.user_id = u.user_id
                LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
                LEFT JOIN marque m ON v.marque_id = m.marque_id
                WHERE c.statut = 1 AND c.nb_place > 0 
                AND c.lieu_depart LIKE :depart 
                AND c.lieu_arrivee LIKE :arrivee
                AND c.date_depart = :date_search
            ");
            $query_search->execute([
                'depart' => '%' . $search_depart . '%',
                'arrivee' => '%' . $search_arrivee . '%',
                'date_search' => $search_date
            ]);
        } else {
            $query_search->execute([
                'depart' => '%' . $search_depart . '%',
                'arrivee' => '%' . $search_arrivee . '%'
            ]);
        }

        $covoiturages_recherche = $query_search->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $covoiturages_recherche = [];
    }
}

// Récupérer un covoiturage disponible pour l'affichage dans la hero (pour tous les utilisateurs)
$covoiturage_hero = null;
try {
    $query_hero = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, v.modele, m.libelle AS marque_libelle
        FROM covoiturage c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
        LEFT JOIN marque m ON v.marque_id = m.marque_id
        WHERE c.statut = 1 AND c.nb_place > 0 AND c.date_depart >= CURDATE()
        ORDER BY c.date_depart ASC, c.heure_depart ASC
        LIMIT 1
    ");
    $query_hero->execute();
    $covoiturage_hero = $query_hero->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En cas d'erreur, on continue sans afficher de covoiturage
    $covoiturage_hero = null;
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

<div class="result-header text-center mb-5">
    <div class="bg-dark text-white p-4">
        <?php if (!empty($search_depart) && !empty($search_arrivee)): ?>
            <h2>Résultats pour : <?= htmlspecialchars($search_depart) ?> → <?= htmlspecialchars($search_arrivee) ?></h2>
            <?php if (!empty($search_date)): ?>
                <p class="mb-0">Date : <?= date('d/m/Y', strtotime($search_date)) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <h2>Découvrez les trajets disponibles</h2>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="filters mb-4">
    <div class="row">
        <div class="col-md-2 text-center">
            <div class="form-check d-flex flex-column justify-content-center align-items-center">
                <label class="form-check-label-eco mb-2" for="ecoTrip">
                    Voyage écologique
                </label>
                <input class="form-check-input mt-3 border-dark align-items-end" type="checkbox" id="ecoTrip">
            </div>
        </div>

        <div class="col-md-2 text-center">
            <label class="form-label credit-min">Crédit minimum (C)</label>
            <input type="number" class="form-control filter-price" placeholder="Crédit min">
        </div>
        <div class="col-md-2 text-center">
            <label class="form-label price-max">Durée maximum</label>
            <input type="number" class="form-control filter-duration" placeholder="Durée max">
        </div>
        <div class="col-md-3 text-center">
            <label class="form-label note">Note minimale</label>
            <select class="form-select">
                <option selected>Toutes les notes</option>
                <option value="5">5 étoiles</option>
                <option value="4">4 étoiles et plus</option>
                <option value="3">3 étoiles et plus</option>
            </select>
        </div>
        <div class="col-md-3 d-flex justify-content-center align-items-end">
            <button class="btn btn-filtre text-dark btn-secondary w-50">Filtrer</button>
        </div>
    </div>
</div>

<section id="results" class="results bg-light py-5">
    <!-- <h5 class="mb-4 ms-4">Résultat(s) trouvé(s) :</h5> -->
    <div class="container">
        <!-- Section des résultats de recherche -->
        <?php if ($has_search_criteria): ?>
            <div class="search-results-section mb-5">
                <h5 class="mb-4">Résultat(s) trouvé(s) :</h5>
                <?php if (!empty($covoiturages_recherche)): ?>
                    <div class="row">
                        <?php foreach ($covoiturages_recherche as $covoiturage): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-primary text-white text-center">
                                        <h6 class="mb-0"><i class="bi bi-car-front me-2"></i>Trajet disponible</h6>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="text-primary mb-2"><i class="bi bi-geo-alt-fill me-1"></i>Trajet</h6>
                                        <p class="mb-2"><strong><?= htmlspecialchars($covoiturage['lieu_depart']) ?></strong> → <strong><?= htmlspecialchars($covoiturage['lieu_arrivee']) ?></strong></p>

                                        <h6 class="text-primary mb-2"><i class="bi bi-calendar-event me-1"></i>Départ</h6>
                                        <p class="mb-2"><?= date('d/m/Y', strtotime($covoiturage['date_depart'])) ?> à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?></p>

                                        <h6 class="text-primary mb-2"><i class="bi bi-person-circle me-1"></i>Conducteur</h6>
                                        <p class="mb-2"><?= htmlspecialchars($covoiturage['prenom'] . ' ' . $covoiturage['nom']) ?></p>

                                        <div class="row mt-3">
                                            <div class="col-6">
                                                <span class="badge bg-success"><i class="bi bi-people me-1"></i><?= $covoiturage['nb_place'] ?> place<?= $covoiturage['nb_place'] > 1 ? 's' : '' ?></span>
                                            </div>
                                            <div class="col-6">
                                                <span class="badge bg-warning text-dark"><i class="bi bi-coin me-1"></i><?= number_format($covoiturage['prix_personne'], 2) ?>€</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-center">
                                        <?php if (isUserConnected()): ?>
                                            <button class="btn btn-primary btn-sm">Réserver</button>
                                        <?php else: ?>
                                            <a href="../login.php" class="btn btn-secondary btn-sm">Se connecter</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Aucun trajet trouvé</strong><br>
                        Aucun covoiturage ne correspond à votre recherche pour le trajet <strong><?= htmlspecialchars($search_depart) ?> → <?= htmlspecialchars($search_arrivee) ?></strong>
                        <?php if (!empty($search_date)): ?>
                            le <?= date('d/m/Y', strtotime($search_date)) ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- <h5 id="suggestions" class="mt-4 mb-4">Suggestions du moment :</h5>
        <div class="row d-flex justify-content-center">
            <div class="col-md-3">
                <div class="card-trajet">
                    <div class="card-header bg-dark text-white text-center rounded-top py-3">
                        <h4 class="mb-0">Trajet</h4>
                    </div>
                    <div class="card-body bg-white text-center rounded-bottom">
                        <img src="/assets/img/profil.jpg" alt="Profil" class="rounded-circle mb-3" style="width: 90px">
                        <h5 class="card-title mb-3">Martigues → Marseille</h5>
                        <p class="card-text">Le 25 avril 2025</p>
                        <p class="card-text">Chauffeur : David</p>
                        <p class="card-text">Places restantes : 1</p>
                        <p class="card-text">Crédit : 20 C</p>
                        <button class="btn btn-primary mb-4">Voir le trajet</button>
                    </div>
                </div>
            </div>
        </div> -->

    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php";
?>