<?php require_once __DIR__ . "/templates/header.php";
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="bg-dark text-white p-4 text-center mb-4">
                <h2>S'identifier</h2>
                <p class="mb-0">
                    Pas encore membre,
                    <a href="#" class="inscription text-secondary fw-bold text-decoration-underline">inscrivez-vous</a>
                </p>
            </div>

            <section class="pb-4">
                <div class="border rounded-5">
                    <section class="w-100 p-4 d-flex justify-content-center pb-4">
                        <form class="login-form" method="POST" action="login.php">
                            <div class="form-outline mb-4">
                                <input type="email" class="form-control bg-light" placeholder="Email" required>
                                <label class="form-label" for="form2Example1"></label>

                                <div class="form-outline mb-4">
                                    <input type="password" class="form-control bg-light" placeholder="Mot de passe" required>
                                    <label class="form-label" for="form2Example2"></label>
                                </div>

                                <div class="row mb-4">
                                    <div class="col d-flex justify-content-start">
                                        <div class="form-check">
                                            <input class="form-check-input border-dark" type="checkbox" value="">
                                            <label class="form-check-label">Rester connecté</label>
                                        </div>
                                    </div>
                                    <div class="col d-flex justify-content-center">
                                        <a class="" href="#!">Mot de passe oublié</a>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block d-flex text-dark justify-content-center w-100 mb-4">Se connecter</button>
                        </form>
                    </section>
                </div>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . "/templates/footer.php";
?>