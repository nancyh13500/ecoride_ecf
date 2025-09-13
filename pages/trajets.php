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

// Récupérer les paramètres de recherche depuis l'URL
$search_depart = $_GET['depart'] ?? '';
$search_arrivee = $_GET['arrivee'] ?? '';
$search_date = $_GET['date'] ?? '';

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
        <div class="col-lg-6 mx-auto">
            <p class="lead mb-4 ">La solution accessible et durable pour tous.</p>
            <div class="search-bar row">
                <div class="search-field col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                        <input type="text" name="depart" class="form-control border-start-0 text-center" placeholder="Ville de départ" required>
                    </div>
                </div>
                <div class="search-field col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt text-primary"></i></span>
                        <input type="text" name="arrivee" class="form-control border-start-0 text-center" placeholder="Ville d'arrivée" required>
                    </div>
                </div>
                <div class="search-field col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar text-primary"></i></span>
                        <input type="date" name="date" class="form-control border-start-0 text-center" required>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button type="button" class="btn btn-primary w-50">Lancer la recherche<i class="bi bi-search ms-2"></i></button>
            </div>
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
    <h5 class="mb-4 ms-4">Résultat(s) trouvé(s) :</h5>
    <div class="container">
        <?php if ($covoiturage_hero): ?>
            <!-- Affichage d'un covoiturage disponible -->
            <div class="covoiturage-hero-card mb-5">
                <div class="card col-md-3 mx-auto covoiturage-results-card">
                    <div class="card-header bg-dark text-white text-center">
                        <h5 class="mb-0"><i class="bi bi-car-front me-2"></i>Covoiturage disponible</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary text-center mb-2"><i class="bi bi-geo-alt-fill me-1"></i>Trajet</h6>
                                <p class="mb-2"><strong><?= htmlspecialchars($covoiturage_hero['lieu_depart']) ?></strong> → <strong><?= htmlspecialchars($covoiturage_hero['lieu_arrivee']) ?></strong></p>

                                <h6 class="text-primary text-center mb-2"><i class="bi bi-calendar-event me-1"></i>Départ</h6>
                                <p class="mb-2"><?= date('d/m/Y', strtotime($covoiturage_hero['date_depart'])) ?> à <?= date('H:i', strtotime($covoiturage_hero['heure_depart'])) ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary text-center mb-2"><i class="bi bi-person-circle me-1"></i>Conducteur</h6>
                                <p class="mb-2"><?= htmlspecialchars($covoiturage_hero['prenom'] . ' ' . $covoiturage_hero['nom']) ?></p>

                                <h6 class="text-primary text-center mb-2"><i class="bi bi-car-front me-1"></i>Véhicule</h6>
                                <p class="mb-2"><?= htmlspecialchars($covoiturage_hero['marque_libelle'] . ' ' . $covoiturage_hero['modele']) ?></p>
                            </div>
                        </div>
                        <div class="col mt-3 mb-3 text-center">
                            <div class="row-md-6 mt-2 mb-2 pt-2 pb-2">
                                <span class="badge bg-success fs-6"><i class="bi bi-people me-1"></i><?= $covoiturage_hero['nb_place'] ?> place<?= $covoiturage_hero['nb_place'] > 1 ? 's' : '' ?> disponible<?= $covoiturage_hero['nb_place'] > 1 ? 's' : '' ?></span>
                            </div>
                            <div class="row-md-6 mt-2 mb-2 pt-2 pb-2">
                                <span class="badge bg-warning text-dark fs-6"><i class="bi bi-coin me-1"></i><?= number_format($covoiturage_hero['prix_personne'], 2) ?>€ par personne</span>
                            </div>
                        </div>
                        <div class="col-md-12 mt-4 mb-2 text-center">
                            <?php if (isUserConnected()): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="start_trajet_from_hero" value="<?= $covoiturage_hero['covoiturage_id'] ?>">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-play-circle me-1"></i>Démarrer le trajet</button>
                                </form>
                            <?php else: ?>
                                <a href="/login.php?redirect=mes_trajets.php&from_covoiturage=1" class="btn btn-secondary justify-content-center"><i class="bi bi-person-circle me-1"></i>Se connecter pour démarrer</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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