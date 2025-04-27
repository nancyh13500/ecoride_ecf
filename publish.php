<?php require_once __DIR__ . "/templates/header.php";
?>

<section class="publish w-100 px-4 py-5">

    <div class="publish-title text-center ">
        <div class="container">
            <h1 class="publish-title mt-3 mb-3 fw-bold">Proposer un covoiturage</h1>
        </div>
        <div class="container">
            <h4 class="publish-subtitle mt-3 mb-5">Remplissez les détails de votre trajet pour partager votre voyage.</h4>
        </div>
    </div>

    <div class="row d-flex justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card rounded-3">
                <img src="https://mdbcdn.b-cdn.net/img/Photos/new-templates/bootstrap-registration/img3.webp" class="w-100" style="border-top-left-radius: .3rem; border-top-right-radius: .3rem;" alt="Sample photo">
                <div class="card-body p-4 p-md-5">
                    <h3 class="mb-4 pb-2 pb-md-0 mb-md-5 px-md-2">Informations</h3>

                    <form class="px-md-2" data-gtm-form-interact-id="0">
                        <div class="row">
                            <div data-mdb-input-init="" class="col-md-6 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                <label class="form-label" for="form3Example1q">Nom</label>
                                <input type="text" id="form3Example1q" class="form-control bg-light" required>
                            </div>
                            <div data-mdb-input-init="" class="col-md-6 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                <label class="form-label" for="form3Example1q">Prénom</label>
                                <input type="text" id="form3Example1q" class="form-control bg-light" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div data-mdb-input-init="" class="form-outline datepicker" data-mdb-input-initialized="true">
                                    <label for="exampleDatepicker1" class="form-label form-adress">Adresse</label>
                                    <input type="text" class="form-control bg-light" id="exampleDatepicker1" required>
                                </div>
                            </div>
                            <div class=" col-md-6">
                                <div id="select-wrapper-644779" class="select-wrapper">
                                    <div class="form-label select-fake-value">Complément adresse</div>
                                    <div class="form-outline" data-mdb-input-init="" data-mdb-input-initialized="true"><input class="form-control select-input placeholder-active active bg-light mb-3" type="text" role="combobox" aria-disabled="false" aria-haspopup="listbox" aria-expanded="false" aria-controls="select-dropdown-container-206827" id="select-input-849282" readonly="true"><span class="select-arrow"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div data-mdb-input-init="" class="form-outline" data-mdb-input-initialized="true">
                                    <label class="form-label" for="form3Example1w">CP</label>
                                    <input type="text" id="form3Example1w" class="form-control bg-light" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div data-mdb-input-init="" class="form-outline" data-mdb-input-initialized="true">
                                    <label class="form-label" for="form3Example1w">Ville</label>
                                    <input type="text" id="form3Example1w" class="form-control bg-light" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div data-mdb-input-init="" class="form-outline" data-mdb-input-initialized="true">
                                    <label class="form-label" for="form3Example1w">Téléphone</label>
                                    <input type="text" id="form3Example1w" class="form-control bg-light" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" data-mdb-button-init="" data-mdb-ripple-init="" class="btn btn-secondary btn-lg mt-3 mb-1" data-mdb-button-initialized="true">Valider</button>

                    </form>

                </div>
            </div>
        </div>
    </div>

</section>

<?php require_once __DIR__ . "/templates/footer.php";
?>