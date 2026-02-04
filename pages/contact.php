<?php
require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../vendor/autoload.php";

use App\Service\MailerService;

// Variables pour les messages
$success = false;
$error = false;
$message = '';

// Valeurs du formulaire
$name = '';
$email = '';
$subject = '';
$messageContent = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) : false;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $messageContent = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Validation
    if (empty($name) || !$email || empty($subject) || empty($messageContent)) {
        $error = true;
        $message = "Tous les champs sont obligatoires et l'email doit être valide.";
    } else {
        // Envoi de l'email
        try {
            $mailer = new MailerService();
            $emailSent = $mailer->sendContactEmail(array(
                'name' => $name,
                'email' => $email,
                'phone' => $subject,
                'message' => $messageContent
            ));

            if ($emailSent) {
                $success = true;
                $message = "Votre message a été envoyé avec succès ! Nous vous répondrons sous 48 heures.";
                // Réinitialiser les variables pour vider le formulaire
                $name = '';
                $email = '';
                $subject = '';
                $messageContent = '';
            } else {
                $error = true;
                $message = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.";
            }
        } catch (Exception $e) {
            $error = true;
            $message = "Une erreur technique est survenue. Veuillez réessayer plus tard.";
            error_log("Erreur formulaire contact: " . $e->getMessage());
        }
    }
}
?>

<section class="hero px-4 py-5">
    <div class="background-contact"></div>
    <div class="contact-title text-black text-center mt-5">
        <div class="container">
            <h1 class="contact-title mt-3 mb-3 fw-bold">Contact</h1>
        </div>
    </div>

    <div class="container contact mt-3 mb-3">

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mx-auto" style="max-width: 600px;" role="alert">
                <strong>✅ Succès !</strong> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mx-auto" style="max-width: 600px;" role="alert">
                <strong>❌ Erreur !</strong> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <input type="text"
                        name="name"
                        class="form-control bg-light border-dark rounded"
                        placeholder="Ex. Durand"
                        value="<?php echo htmlspecialchars($name); ?>"
                        required>
                </div>
            </div>
            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <input type="email"
                        name="email"
                        class="form-control bg-light border-dark rounded"
                        placeholder="name@example.com"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required>
                </div>
            </div>
            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <input type="text"
                        name="subject"
                        class="form-control bg-light border-dark rounded"
                        placeholder="Sujet"
                        value="<?php echo htmlspecialchars($subject); ?>"
                        required>
                </div>
            </div>
            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <textarea name="message"
                        class="form-control bg-light border-dark rounded"
                        rows="5"
                        placeholder="Message"
                        required><?php echo htmlspecialchars($messageContent); ?></textarea>
                </div>
                <p class="text-center mt-3">Une réponse vous sera envoyée par mail sous 48 heures</p>
                <div class="text-center mt-2 mb-2">
                    <button type="submit" class="btn btn-secondary">Envoyer le message</button>
                </div>
            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>