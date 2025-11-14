<?php
require_once __DIR__ . "/lib/session.php";
require_once __DIR__ . "/lib/pdo.php";
require_once __DIR__ . "/templates/header.php";

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

// Traitement de la recherche
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_trajet'])) {
    $depart = $_POST['depart'] ?? '';
    $arrivee = $_POST['arrivee'] ?? '';
    $date = $_POST['date'] ?? '';

    // Redirection vers trajets.php avec les paramètres de recherche
    $params = http_build_query([
        'depart' => $depart,
        'arrivee' => $arrivee,
        'date' => $date
    ]);

    header("Location: /pages/trajets.php?$params");
    exit();
}

// Récupérer les avis validés depuis la base de données pour le carrousel
$avis_list = [];
try {
    $query_avis = $pdo->prepare("
        SELECT a.*, u.nom, u.prenom, u.pseudo
        FROM avis a
        LEFT JOIN user u ON a.user_id = u.user_id
        WHERE a.statut = 'valide'
        ORDER BY a.avis_id DESC
        LIMIT 3
    ");
    $query_avis->execute();
    $avis_list = $query_avis->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En cas d'erreur, on continue avec une liste vide
    $avis_list = [];
}

// Récupérer les suggestions de trajets
$covoiturages_suggestion = [];
try {
    $query_suggestion = $pdo->prepare("
        SELECT c.covoiturage_id,
               c.lieu_depart,
               c.lieu_arrivee,
               c.date_depart,
               c.heure_depart,
               c.nb_place,
               c.prix_personne,
               u.nom,
               u.prenom
        FROM covoiturage c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.statut = 1
          AND c.nb_place > 0
          AND c.date_depart >= CURDATE()
        ORDER BY c.date_depart ASC, c.heure_depart ASC
        LIMIT 3
    ");
    $query_suggestion->execute();
    $covoiturages_suggestion = $query_suggestion->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $covoiturages_suggestion = [];
}

?>

<!--section search -->
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
                            <input type="text" name="depart" class="form-control border-start-0 text-center" placeholder="Ville de départ" list="villes-depart" required>
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
                            <input type="text" name="arrivee" class="form-control border-start-0 text-center" placeholder="Ville d'arrivée" list="villes-arrivee" required>
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
                            <input type="date" name="date" class="form-control border-start-0 text-center">
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
<!--end section search -->

<!--section presentation -->
<section class="presentation">
    <div class="col-xxl-12 px-4 py-5">
        <div class="row flex-lg justify-content-center align-items-center">
            <div class="card_img col-lg-6">
                <img src="/assets/img/friends-car.jpg" class="d-flex mx-lg-auto img-fluid rounded" alt="friends-car" width="700" height="500" loading="lazy" id="imageFriends">
            </div>
            <div class="card_text col-lg-6 pt-2 pb-2">
                <h1 class="fw-bold text-center text-white mb-3">Pourquoi choisir EcoRide ?</h1>
                <p class="text text-center text-white">Notre mission : rendre les déplacements quotidiens accessibles, pratiques et durables pour tous.
                    Découvrez comment nous accompagnons les employeurs et les collectivités vers une mobilité décarbonée.<br><br>

                    Employeurs, collectivités, vous recherchez un accompagnement pour développer la mobilité ?<br><br>

                    Découvrez nos offres issues de plus de 15 ans d'expérience et éprouvées auprès de 360 employeurs et collectivités déjà accompagnés.
                </p><br>
                <div class="d-flex d-md-flex justify-content-center">
                    <a type="button" href="/pages/contact.php" role="button" class="btn-secondary btn btn-lg px-4 mb-2 me-2">Contactez-nous</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!--end section presentation -->


<!--section trajet-->
<section class="trajet">
    <div class="container col-xxl-8 px-4 py-5">
        <div class="row text-center">
            <h2>Découvrez les trajets du moment</h2>
            <?php if (!empty($covoiturages_suggestion)): ?>
                <?php foreach ($covoiturages_suggestion as $covoiturage): ?>
                    <?php
                    $trajet_url = '/pages/detail_covoiturage.php?id=' . (int)($covoiturage['covoiturage_id'] ?? 0);
                    $nb_places = (int) ($covoiturage['nb_place'] ?? 0);
                    $badge_class = 'bg-danger';
                    if ($nb_places >= 3) {
                        $badge_class = 'bg-success';
                    } elseif ($nb_places === 2) {
                        $badge_class = 'bg-warning text-dark';
                    }
                    $prix_personne = isset($covoiturage['prix_personne']) ? number_format((float) $covoiturage['prix_personne'], 0, ',', ' ') : null;
                    ?>
                    <div class="col-md-4 my-2">
                        <div class="card h-100">
                            <div class="card-header bg-secondary">
                                <p class="text-trajet mt-3 text-white">Trajet</p>
                            </div>
                            <div class="card-body">
                                <img src="/assets/img/profil.jpg" class="user_profile mb-3" alt="user_profile">
                                <p class="card-text">
                                    <strong><?= htmlspecialchars($covoiturage['lieu_depart']) ?></strong>
                                    → <strong><?= htmlspecialchars($covoiturage['lieu_arrivee']) ?></strong>
                                </p>
                                <p>
                                    Date de départ :
                                    <?= !empty($covoiturage['date_depart']) ? date('d/m/Y', strtotime($covoiturage['date_depart'])) : 'À définir' ?>
                                    <?php if (!empty($covoiturage['heure_depart'])): ?>
                                        à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?>
                                    <?php endif; ?>
                                </p>
                                <p>
                                    Conducteur :
                                    <?= htmlspecialchars(trim(($covoiturage['prenom'] ?? '') . ' ' . ($covoiturage['nom'] ?? ''))) ?: 'Non renseigné' ?>
                                </p>
                                <p class="mb-2">
                                    <span class="badge <?= $badge_class ?>">
                                        <i class="bi bi-people me-1"></i>
                                        <?= $nb_places ?> place<?= $nb_places > 1 ? 's' : '' ?>
                                    </span>
                                </p>
                                <p>
                                    Tarif :
                                    <?= $prix_personne !== null ? $prix_personne . ' crédits' : 'Non renseigné' ?>
                                </p>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="<?= htmlspecialchars($trajet_url) ?>" class="btn btn_card btn-primary">Voir le trajet</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 mt-3">
                    <div class="alert alert-info" role="alert">
                        Aucun covoiturage disponible pour le moment. Revenez plus tard ou publiez une annonce !
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- end section trajet-->

<!-- section proposition trajet-->
<section class="suggesting_route">
    <div class="row flex-lg-row-reverse justify-content-center align-items-center py-5 text-white text-center">
        <div class="img_covoiturage d-flex col-sm-12 col-md-6 col-lg-6 justify-content-center align-items-center">
            <img src="/assets/img/covoiturage.jpg" class="image-fluid rounded" alt="covoiturage" width="400" height="250" id="img_covoiturage">
        </div>
        <div class="col-sm-12 col-md-6 col-lg-6">
            <h1 class="display-5 fw-bold mt-3 mb-3">Vous avez une voiture ?</h1>
            <p class="lead">Faites des économies, publiez une annonce.</p>
            <div class="d-flex mt-5 justify-content-center">
                <a type="button" href="<?= isset($_SESSION['user']) ? '/pages/covoiturage.php' : '/pages/publish.php' ?>" role="button" class="btn_route btn btn-secondary btn-lg px-4">Proposer des places</a>
            </div>
        </div>
    </div>
</section>
<!-- end section proposition-->

<!-- section avis -->
<section class="avis">
    <div class="container py-5">
        <h1 class="text-center mb-5">Chaque avis nous donne un peu plus envie</h1>
        <div id="avisCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="row justify-content-center g-4">
                        <div class="col-12 col-md-4 d-flex justify-content-center">
                            <div class="card p-3 shadow-sm avis-card">
                                <div class="d-flex justify-content-center mb-3 text-warning border-bottom border-dark">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                </div>
                                <h5 class="text-center fw-bold mb-2">Laura</h5>
                                <p class="text-center">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="row justify-content-center g-4">
                        <div class="col-12 col-md-4 d-flex justify-content-center">
                            <div class="card p-3 shadow-sm avis-card">
                                <div class="d-flex justify-content-center mb-3 text-warning border-bottom border-dark">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                </div>
                                <h5 class="text-center fw-bold mb-2">Nancy</h5>
                                <p class="text-center">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="row justify-content-center g-4">
                        <div class="col-12 col-md-4 d-flex justify-content-center">
                            <div class="card p-3 shadow-sm avis-card">
                                <div class="d-flex justify-content-center mb-3 text-warning border-bottom border-dark">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                </div>
                                <h5 class="text-center fw-bold mb-2">Baptiste</h5>
                                <p class="text-center">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="row justify-content-center g-4">
                        <div class="col-12 col-md-4 d-flex justify-content-center">
                            <div class="card p-3 shadow-sm avis-card">
                                <div class="d-flex justify-content-center mb-3 text-warning border-bottom border-dark">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                </div>
                                <h5 class="text-center fw-bold mb-2">David</h5>
                                <p class="text-center">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev d-none d-md-flex" type="button" data-bs-target="#avisCarousel" data-bs-slide="prev" style="width: 60px; height: 60px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); border-radius: 50%; border: none; left: -80px;">
                <span aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="white" class="bi bi-chevron-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z" />
                    </svg>
                </span>
                <span class="visually-hidden">Précédent</span>
            </button>
            <button class="carousel-control-next d-none d-md-flex" type="button" data-bs-target="#avisCarousel" data-bs-slide="next" style="width: 60px; height: 60px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); border-radius: 50%; border: none; right: -80px;">
                <span aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="white" class="bi bi-chevron-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" />
                    </svg>
                </span>
                <span class="visually-hidden">Suivant</span>
            </button>
        </div>

        <div class="container text-center p-4">
            <div class="row justify-content-center gy-2">
                <div class="col-12 col-md-auto">
                    <a href="/pages/avis.php" type="button" class="btn btn_tous_avis btn-primary w-100">Voir tous les avis</a>
                </div>
                <div class="col-12 col-md-auto">
                    <a href="/pages/deposer_avis.php" type="button" class="btn btn-secondary btn_avis w-100">Laisser un avis</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- end section avis -->

<script src="/assets/js/avis.js"></script>

<?php require_once __DIR__ . "/templates/footer.php";
?>