<?php require_once __DIR__ . "/templates/header.php";
?>

<!--section search -->
<section class="hero">
    <div class="background-img"></div>
    <div class="px-4 py-5 my-5 text-center text-dark content">
        <h1 class="fw-bold text-dark">Trouvez un covoiturage</h1>
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

<!--section presentation -->
<section class="presentation">

    <div class="container col-xxl-8 px-4 py-5">
        <div class="row flex-lg align-items-center justify-content-center g-5 py-5">
            <div class="col-10 col-sm-8 col-lg-6">
                <img src="/assets/img/friends-car.jpg" class="d-flex mx-lg-auto img-fluid" alt="friends-car" width="700" height="500" loading="lazy">
            </div>
            <div class="col-lg-6">
                <h1 class="fw-bold text-body-emphasis text-center lh-1 mb-3">Pourquoi choisir EcoRide ?</h1>
                <p class="text text-center">Notre mission : rendre les déplacements quotidiens accessibles, pratiques et durables pour tous.
                    Découvrez comment nous accompagnons les employeurs et les collectivités vers une mobilité décarbonée.</p>
                <p class="text text-center">
                    Employeurs, collectivités, vous recherchez un accompagnement pour développer la mobilité ?

                    Découvrez nos offres issues de plus de 15 ans d'expérience et éprouvées auprès de 360 employeurs et collectivités déjà accompagnés.
                </p>
                <div class="d-flex d-md-flex justify-content-center">
                    <button type="button" class="btn btn-secondary btn-lg px-4 me-md-2">Contactez nous</button>
                </div>
            </div>
        </div>
    </div>
</section>








<!--end section presentation -->



<?php require_once __DIR__ . "/templates/footer.php";
?>