<?php require_once __DIR__ . "/templates/header.php";
?>

<div class="container mt-4">
    <div class="row justify-content-center mb-4">
        <div class="col-md-4">
            <div class="card border-dark">
                <ul class="nav nav-pills nav-justified" id="ex1" role="tablist">
                    <li class="nav-item ms-3 mt-3 mb-3" role="presentation">
                        <a class="nav-link active" id="tab-login" data-bs-toggle="pill" href="#pills-login" role="tab" aria-controls="pills-login" aria-selected="true">Se connecter</a>
                    </li>
                    <li class="nav-item me-3 mt-3 mb-3" role="presentation">
                        <a class="nav-link" id="tab-register" data-bs-toggle="pill" href="#pills-register" role="tab" aria-controls="pills-register" aria-selected="false">S'enregistrer</a>
                    </li>
                </ul>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="login tab-pane fade show active" id="pills-login" role="tabpanel" aria-labelledby="tab-login">
                            <form>
                                <div class="form-outline mb-4">
                                    <input type="email" id="loginName" class="form-control border-dark bg-light" placeholder="Email" required>
                                    <label class="form-label" for="loginName"></label>
                                </div>

                                <div class="form-outline mb-4">
                                    <input type="password" id="loginPassword" class="form-control border-dark bg-light" placeholder="Mot de passe" required>
                                    <label class="form-label" for="loginPassword"></label>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6 d-flex justify-content-center align-items-center">
                                        <div class="form-check-login">
                                            <input class="form-check-input-login border-dark" type="checkbox" value="" id="loginCheck">
                                            <label class="form-check-label-login" for="loginCheck">Rester connecté</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6 d-flex justify-content-center">
                                        <a href="#">Mot de passe oublié</a>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-connect btn-secondary text-dark btn-block mb-4 w-100">Se connecter</button>
                            </form>
                        </div>

                        <div class="register tab-pane fade" id="pills-register" role="tabpanel" aria-labelledby="tab-register">
                            <form>
                                <div class="form-outline mb-4">
                                    <input type="text" id="registerName" class="form-control border-dark bg-light" placeholder="Nom" required>
                                    <label class="form-label" for="registerName"></label>
                                </div>

                                <div class="form-outline mb-4">
                                    <input type="text" id="registerUsername" class="form-control border-dark bg-light" placeholder="Nom utilisateur" required>
                                    <label class="form-label" for="registerUsername"></label>
                                </div>

                                <div class="form-outline mb-4">
                                    <input type="email" id="registerEmail" class="form-control border-dark bg-light" placeholder="Email" required>
                                    <label class="form-label" for="registerEmail"></label>
                                </div>

                                <div class="form-outline mb-4">
                                    <input type="password" id="registerPassword" class="form-control border-dark bg-light" placeholder="Mot de passe" required>
                                    <label class="form-label" for="registerPassword"></label>
                                </div>

                                <div class="form-outline mb-4">
                                    <input type="password" id="registerRepeatPassword" class="form-control border-dark bg-light" placeholder="Confirmer mot de passe" required>
                                    <label class="form-label" for="registerRepeatPassword"></label>
                                </div>
                                <div class="row check-accept align-items-center">

                                    <div class="form-check col-md-12 d-flex justify-content-center mb-4">
                                        <input class="form-check-input me-2 border-dark" type="checkbox" value="" id="registerCheck" required>
                                        <label class="form-check-label-register" for="registerCheck">
                                            J'accepte les conditions d'utilisation
                                        </label>
                                    </div>

                                </div>

                                <button type="submit" class="btn btn-connect btn-secondary text-dark btn-block mt-3 mb-3 w-100">S'inscrire</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . "/templates/footer.php";
?>