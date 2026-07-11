<?php
require_once __DIR__ . '/../bootstrap/app.php';

use Ecoride\Ecf\Service\PasswordResetService;

$session = ecoride_session();
$passwordResetService = new PasswordResetService();

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$errors = [];
$tokenData = null;

if ($token !== '') {
    $tokenData = $passwordResetService->findValidToken($token);
}

if (isset($_POST['resetPassword'])) {
    $session->verifyCSRFToken();

    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        try {
            if ($passwordResetService->resetPassword($token, $password)) {
                $_SESSION['success'] = "Votre mot de passe a été modifié. Vous pouvez vous connecter.";
                header('Location: /login.php');
                exit();
            }
            $errors[] = "Le lien de réinitialisation est invalide ou a expiré.";
            $tokenData = null;
        } catch (\PDOException $e) {
            error_log('Erreur réinitialisation mot de passe : ' . $e->getMessage());
            $errors[] = "Une erreur est survenue. Veuillez réessayer plus tard.";
        }
    } else {
        $tokenData = $passwordResetService->findValidToken($token);
    }
}

$pageTitle = 'Réinitialiser le mot de passe';
require_once __DIR__ . '/../templates/header.php';
?>
<section class="hero px-4 py-5">
    <div class="background-login"></div>
    <div class="container login-register mt-5">
        <h1 class="visually-hidden">Réinitialiser le mot de passe</h1>
        <div class="row justify-content-center mb-4">
            <div class="col-md-4">
                <div class="card border-dark">
                    <div class="card-body p-4">
                        <h2 class="h4 text-center mb-4">Nouveau mot de passe</h2>

                        <?php foreach ($errors as $error) { ?>
                            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                        <?php } ?>

                        <?php if (!$tokenData) { ?>
                            <div class="alert alert-warning" role="alert">
                                Ce lien de réinitialisation est invalide ou a expiré.
                            </div>
                            <a href="/mot_de_passe_oublie.php" class="btn btn-connect btn-secondary text-dark w-100 mb-3">
                                Demander un nouveau lien
                            </a>
                            <a href="/login.php" class="btn btn-outline-secondary w-100">Retour à la connexion</a>
                        <?php } else { ?>
                            <p class="text-muted mb-4">
                                Choisissez un nouveau mot de passe pour le compte
                                <strong><?= htmlspecialchars($tokenData['email']) ?></strong>.
                            </p>

                            <form action="" method="post">
                                <?php $session->csrfField(); ?>
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                <div class="form-outline mb-4">
                                    <label class="form-label" for="newPassword">Nouveau mot de passe</label>
                                    <input
                                        type="password"
                                        id="newPassword"
                                        name="password"
                                        class="form-control border-dark bg-light"
                                        autocomplete="new-password"
                                        required
                                        minlength="6"
                                    >
                                </div>
                                <div class="form-outline mb-4">
                                    <label class="form-label" for="confirmNewPassword">Confirmer le mot de passe</label>
                                    <input
                                        type="password"
                                        id="confirmNewPassword"
                                        name="confirmPassword"
                                        class="form-control border-dark bg-light"
                                        autocomplete="new-password"
                                        required
                                        minlength="6"
                                    >
                                </div>
                                <button type="submit" class="btn btn-connect btn-secondary text-dark w-100 mb-3" name="resetPassword">
                                    Enregistrer le mot de passe
                                </button>
                                <a href="/login.php" class="btn btn-outline-secondary w-100">Retour à la connexion</a>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
