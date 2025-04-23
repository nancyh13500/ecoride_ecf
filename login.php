<?php require_once __DIR__ . "/templates/header.php";
?>


<section class="w-100 p-4 d-flex justify-content-center pb-4">
    <div style="width: 26rem;">
        <!-- Pills navs -->
        <ul class="nav nav-pills nav-justified mb-3" id="ex1" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="tab-login" data-bs-toggle="pill" href="#pills-login" role="tab" aria-controls="pills-login" aria-selected="true">Se connecter</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="tab-register" data-bs-toggle="pill" href="#pills-register" role="tab" aria-controls="pills-register" aria-selected="false">S'enregistrer</a>
            </li>
        </ul>
        <!-- Pills navs -->

        <!-- Pills content -->
        <div class="tab-content">
            <div class="tab-pane fade show active" id="pills-login" role="tabpanel" aria-labelledby="tab-login">
                <form>
                    <!-- Email input -->
                    <div class="form-outline mb-4">
                        <input type="email" id="loginName" class="form-control border-dark" placeholder="Email">
                        <label class="form-label" for="loginName"></label>
                    </div>

                    <!-- Password input -->
                    <div class="form-outline mb-4">
                        <input type="password" id="loginPassword" class="form-control border-dark" placeholder="Mot de passe">
                        <label class="form-label" for="loginPassword"></label>
                    </div>

                    <!-- 2 column grid layout -->
                    <div class="row mb-4">
                        <div class="col-md-6 d-flex justify-content-center">
                            <!-- Checkbox -->
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="loginCheck" checked />
                                <label class="form-check-label" for="loginCheck">Se souvenir de moi</label>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex justify-content-center">
                            <!-- Simple link -->
                            <a href="#!">Mot de passe oublié ?</a>
                        </div>
                    </div>

                    <!-- Submit button -->
                    <button type="submit" class="btn btn-primary btn-block mb-4 w-100">Se connecter</button>

                    <!-- Register buttons -->
                </form>
            </div>

            <div class="tab-pane fade" id="pills-register" role="tabpanel" aria-labelledby="tab-register">
                <form>
                    <!-- Name input -->
                    <div class="form-outline mb-4">
                        <input type="text" id="registerName" class="form-control border-dark" placeholder="Nom">
                        <label class="form-label" for="registerName"></label>
                    </div>

                    <!-- Username input -->
                    <div class="form-outline mb-4">
                        <input type="text" id="registerUsername" class="form-control border-dark" placeholder="Nom utilisateur">
                        <label class="form-label" for="registerUsername"></label>
                    </div>

                    <!-- Email input -->
                    <div class="form-outline mb-4">
                        <input type="email" id="registerEmail" class="form-control border-dark" placeholder="Email">
                        <label class="form-label" for="registerEmail"></label>
                    </div>

                    <!-- Password input -->
                    <div class="form-outline mb-4">
                        <input type="password" id="registerPassword" class="form-control border-dark" placeholder="Mot de passe">
                        <label class="form-label" for="registerPassword"></label>
                    </div>

                    <!-- Repeat Password input -->
                    <div class="form-outline mb-4">
                        <input type="password" id="registerRepeatPassword" class="form-control border-dark" placeholder="Confirmer mot de passe">
                        <label class="form-label" for="registerRepeatPassword"></label>
                    </div>

                    <!-- Checkbox -->
                    <div class="form-check d-flex justify-content-center mb-4">
                        <input class="form-check-input me-2" type="checkbox" value="" id="registerCheck" checked>
                        <label class="form-check-label" for="registerCheck">
                            J'accepte les conditions d'utilisation
                        </label>
                    </div>

                    <!-- Submit button -->
                    <button type="submit" class="btn btn-primary btn-block mb-3 w-100">S'inscrire</button>

                    <!-- Login link -->
                </form>
            </div>
        </div>
        <!-- Pills content -->
    </div>
</section>

<?php require_once __DIR__ . "/templates/footer.php";
?>