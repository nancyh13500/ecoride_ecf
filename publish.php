<?php require_once __DIR__ . "/templates/header.php";
?>

<div class="publish-title text-center">
    <div class="container">
        <h1 class="contact-title mt-3 mb-3 fw-bold">Proposer un covoiturage</h1>
    </div>
</div>

<div class="col-lg-6 mx-auto">
    <div class="row">
        <div class="search-field col-md-8 mt-3 mb-3">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                <input type="text" name="depart" class="form-control border-start-0 text-center" placeholder="Ville de départ" required>
            </div>
        </div>
        <div class="search-field col-md-8 mt-3 mb-3">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt text-primary"></i></span>
                <input type="text" name="arrivee" class="form-control border-start-0 text-center" placeholder="Ville d'arrivée" required>
            </div>
        </div>
        <div class="search-field col-md-8 mt-3 mb-3">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar text-primary"></i></span>
                <input type="date" name="date" class="form-control border-start-0 text-center" required>
            </div>
        </div>
        <div class="search-field col-md-8 mt-3 mb-3">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-person-standing text-primary"></i></span>
                <input type="text" name="arrivee" class="form-control border-start-0 text-center" placeholder="Nombre de passagers" required>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-center mt-3 mb-3">
        <button type="button" class="btn btn-primary w-50">Valider</button>
    </div>
</div>


<?php require_once __DIR__ . "/templates/footer.php";
?>