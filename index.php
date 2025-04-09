<?php require_once __DIR__ . "/templates/header.php";
?>

<!--section search -->
<section class="hero">
    <div class="background-img"></div>
    <div class="content px-4 py-5 my-5 text-center text-dark">
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

    <div class="col-xxl-12 px-4 py-5 bg-dark">
        <div class="row flex-lg align-items-center justify-content-center g-5 py-5">
            <div class="col-lg-4">
                <img src="/assets/img/friends-car.jpg" class="d-flex mx-lg-auto img-fluid rounded" alt="friends-car" width="700" height="500" loading="lazy">
            </div>
            <div class="col-lg-6">
                <h1 class="fw-bold text-center text-white lh-1 mb-3">Pourquoi choisir EcoRide ?</h1>
                <p class="text text-center text-white">Notre mission : rendre les déplacements quotidiens accessibles, pratiques et durables pour tous.
                    Découvrez comment nous accompagnons les employeurs et les collectivités vers une mobilité décarbonée.</p>
                <p class="text text-center text-white">
                    Employeurs, collectivités, vous recherchez un accompagnement pour développer la mobilité ?<br>
                </p>
                <p class="text text-center text-white">
                    Découvrez nos offres issues de plus de 15 ans d'ex

































                    périence et éprouvées auprès de 360 employeurs et collectivités déjà accompagnés.
                </p>
                <div class="d-flex d-md-flex justify-content-center">
                    <button type="button" class="bnt_contact btn btn-secondary btn-lg px-4 mb-2 me-2">Contactez-nous</button>
                </div>
            </div>
        </div>
    </div>
</section>
<!--end section presentation -->


<!--section trajet-->
<div class="container col-xxl-8 px-4 py-5">
    <div class="row text-center">
        <h2>Découvrez les fonctionnalités principales :</h2>
        <div class="col-md-4 my-2">
            <div class="card w-100">
                <div class="card-header">
                    <i class="bi bi-card-checklist"></i>
                </div>
                <div class="card-body">
                    <h3 class="card-text">Créer un nombre illimité de listes</h3>
                    <a href="#" class="btn btn-primary">S'inscrire</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 my-2">
            <div class="card w-100">
                <div class="card-header">
                    <i class="bi bi-tags-fill"></i>
                </div>
                <div class="card-body">
                    <h3 class="card-text">Classer les listes par catégories</h3>
                    <a href="#" class="btn btn-primary">S'inscrire</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 my-2">
            <div class="card w-100">
                <div class="card-header">
                    <i class="bi bi-search"></i>
                </div>
                <div class="card-body">
                    <h3 class="card-text">Retrouver facilement vos listes</h3>
                    <a href="#" class="btn btn-primary">S'inscrire</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- end section trajet-->


<?php require_once __DIR__ . "/templates/footer.php";
?>