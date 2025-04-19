<?php require_once __DIR__ . "/templates/header.php";
?>

<main class="login-hero">
    <div class="login-background"></div>
    <div class="content py-5">
        <div class="row justify-content-center">
            <div class="col-md-3">
                <div class="bg-dark text-white p-4 text-center mb-4">
                    <h3>Créer un compte</h3>
                </div>

                <section class="pb-4">
                    <div class="border border-dark rounded-5">
                        <section class="w-100 p-4 d-flex justify-content-center pb-4">
                            <form class="login-form" method="POST" action="inscription.php">
                                <div class="form-outline mb-4">
                                    <input type="email" class="form-control bg-light border-dark" placeholder="Email" required>
                                    <label class="form-label" for="form2Example1"></label>

                                    <div class="form-outline mb-4">
                                        <input type="password" class="form-control bg-light border-dark" placeholder="Mot de passe" required>
                                        <label class="form-label" for="form2Example2"></label>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block d-flex text-dark justify-content-center w-100 mb-4">Valider</button>
                            </form>
                        </section>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . "/templates/footer.php";
?>