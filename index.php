<?php require_once __DIR__ . "/templates/header.php";
?>

<!--section search -->
<section class="hero">
    <div class="px-4 py-5 my-5 text-center text-dark">
        <h2 class="fw-bold text-dark">Trouvez un covoiturage</h2>
        <div class="col-lg-6 mx-auto">
            <p class="lead mb-4">La solution accessible et durable pour tous.</p>
            <div class="search-bar row">
                <div class="search-field col-md-4 align-items-center">
                    <i class="geo bi bi-geo-alt-fill text-primary"></i>
                    <input type="text" name="depart" class="text-center" placeholder="Ville de départ" required="">
                </div>
                <div class="search-field col-md-4">
                    <i class="geo bi bi-geo-alt text-primary"></i>
                    <input type="text" name="arrivee" class="text-center" placeholder="Ville d'arrivée" required="">
                </div>
                <div class="d-flex col-md-4 justify-content-center">
                    <button type="button" class="btn text-dark bg-primary justify-content-start ">Lancer la recherche<i class="bi bi-search text-white ms-2"></i></button>
                </div>
            </div>
        </div>
    </div>
</section>
<!--end section search -->


<?php require_once __DIR__ . "/templates/footer.php";
?>