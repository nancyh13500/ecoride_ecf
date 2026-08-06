<?php
require_once __DIR__ . '/../bootstrap/app.php';

use Ecoride\Ecf\Service\PasswordResetService;

$session = ecoride_session();
$passwordResetService = new PasswordResetService();

$errors = [];
$success = false;

if (isset($_POST['requestReset'])) {
    $session->verifyCSRFToken();

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?: '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Veuillez saisir une adresse e-mail valide.";
    } else {
        $passwordResetService->requestReset($email);
        $success = true;
    }
}

$pageTitle = 'Mot de passe oublié';
require_once __DIR__ . '/../templates/header.php';
?>
<section class="hero px-4 py-5">
    <div class="background-login"></div>
    <div class="container login-register mt-5">
        <h1 class="visually-hidden">Mot de passe oublié</h1>
        <div class="row justify-content-center mb-4">
            <div class="col-md-4">
                <div class="card border-dark">
                    <div class="card-body p-4">
                        <h2 class="h4 text-center mb-4">Mot de passe oublié</h2>

                        <?php if ($success) { ?>
                            <div class="alert alert-success" role="alert">
                                Si un compte existe avec cette adresse e-mail, vous recevrez un lien de réinitialisation dans quelques instants.
                                Pensez à vérifier vos spams.
                            </div>
                            <a href="/login.php" class="btn btn-connect btn-secondary text-dark w-100">Retour à la connexion</a>
                        <?php } else { ?>
                            <?php foreach ($errors as $error) { ?>
                                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                            <?php } ?>

                            <p class="text-muted mb-4">
                                Saisissez votre adresse e-mail. Nous vous enverrons un lien pour choisir un nouveau mot de passe.
                            </p>

                            <form action="" method="post">
                                <?php $session->csrfField(); ?>
                                <div class="form-outline mb-4">
                                    <label class="form-label" for="resetEmail">Adresse e-mail</label>
                                    <input
                                        type="email"
                                        id="resetEmail"
                                        name="email"
                                        class="form-control border-dark bg-light"
                                        autocomplete="email"
                                        required
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                                <button type="submit" class="btn btn-connect btn-secondary text-dark w-100 mb-3" name="requestReset">
                                    Envoyer le lien
                                </button>
                                <a href="/login.php" class="btn btn-primary w-100">Retour à la connexion</a>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>