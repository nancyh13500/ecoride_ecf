<?php require_once __DIR__ . "/templates/header.php";
?>

<!--section search -->
<section class="hero">
    <div class="background-img"></div>
    <div class="px-4 py-5 my-5 text-center text-dark content">
        <h2 class="fw-bold text-dark">Trouvez un covoiturage</h2>
        <div class="col-lg-6 mx-auto">
            <p class="lead mb-4">La solution accessible et durable pour tous.</p>
            <div class="search-bar row">
                <div class="search-field col-md-4">
                    <div class="input-group text-dark">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                        <input type="text" name="depart" class="form-control border-start-0 text-center" placeholder="Ville de départ" required>
                    </div>
                </div>
                <div class="search-field col-md-4">
                    <div class="input-group text-dark">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt text-primary"></i></span>
                        <input type="text" name="arrivee" class="form-control border-start-0 text-center" placeholder="Ville d'arrivée" required>
                    </div>
                </div>
                <div class="search-field col-md-4">
                    <div class="input-group text-dark">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar text-primary"></i></span>
                        <input type="date" name="date" class="form-control border-start-0 text-center" required>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button type="button" class="btn text-dark bg-primary w-50">Lancer la recherche<i class="bi bi-search text-white ms-2"></i></button>
            </div>
        </div>
    </div>
</section>
<!--end section search -->


<?php require_once __DIR__ . "/templates/footer.php";
?>