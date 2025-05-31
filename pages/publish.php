<?php require_once __DIR__ . "/../templates/header.php";

?>

<section class="hero publish w-100 px-4 py-5">

    <div class="publish-title text-center ">
        <div class="container">
            <h1 class="publish-title mt-3 mb-3 fw-bold">Proposer un covoiturage</h1>
        </div>
        <div class="container">
            <h5 class="publish-subtitle mt-3 mb-5">L'accès à cette page nécessite une authentification. Veuillez vous connecter à votre compte.</h5>
        </div>
        <div class="container">
            <a type="button" href="/login.php" role="button" class="btn_route btn btn-secondary btn-lg px-4">Se connecter</a>
        </div>
    </div>

    <div class="row d-flex justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card rounded-3 mt-5">
                <img src="/assets/img/city.webp" class="img_city w-100 rounded" alt="city">
            </div>
        </div>
    </div>

</section>

<?php require_once __DIR__ . "/../templates/footer.php";
?>