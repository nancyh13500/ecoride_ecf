<?php
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit();
}

$user = $_SESSION['user'];
$success_message = '';
$error_message = '';

// Traitement du formulaire d'avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_avis'])) {
    $note = intval($_POST['note']);
    $commentaire = trim($_POST['commentaire']);

    // Validation des données
    if ($note < 1 || $note > 5) {
        $error_message = "La note doit être comprise entre 1 et 5.";
    } elseif (empty($commentaire) || strlen($commentaire) < 10) {
        $error_message = "Le commentaire doit contenir au moins 10 caractères.";
    } else {
        try {
            // Vérifier si l'utilisateur a déjà laissé un avis
            $query_check = $pdo->prepare("SELECT avis_id FROM avis WHERE user_id = :user_id");
            $query_check->execute(['user_id' => $user['user_id']]);
            $existing_avis = $query_check->fetch();

            if ($existing_avis) {
                // Mettre à jour l'avis existant
                $query = $pdo->prepare("UPDATE avis SET note = :note, commentaire = :commentaire, date_avis = NOW() WHERE user_id = :user_id");
                $success_message = "Votre avis a été mis à jour avec succès !";
            } else {
                // Créer un nouvel avis
                $query = $pdo->prepare("INSERT INTO avis (user_id, note, commentaire, date_avis) VALUES (:user_id, :note, :commentaire, NOW())");
                $success_message = "Votre avis a été publié avec succès !";
            }

            $query->execute([
                'user_id' => $user['user_id'],
                'note' => $note,
                'commentaire' => $commentaire
            ]);
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la publication de votre avis : " . $e->getMessage();
        }
    }
}

// Récupérer l'avis existant de l'utilisateur s'il en a un
$user_avis = null;
try {
    $stmt_get = $pdo->prepare("SELECT * FROM avis WHERE user_id = :user_id");
    $stmt_get->execute(['user_id' => $user['user_id']]);
    $user_avis = $stmt_get->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table avis n'existe pas encore
}

require_once __DIR__ . "/../templates/header.php";
?>

<section class="hero count-section py-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="ps-3 pt-3 mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="/pages/avis.php">Avis</a></li>
                <li class="breadcrumb-item active" aria-current="page">Déposer un avis</li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card-avis">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-3 pt-2 pb-2 ps-2">
                            <i class="bi bi-star-fill me-2"></i>
                            <?= $user_avis ? 'Modifier votre avis' : 'Déposer un avis' ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                <?= htmlspecialchars($success_message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error_message) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="deposer_avis.php">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Votre note globale :</label>
                                <div class="rating-stars">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="note" value="<?= $i ?>" id="star<?= $i ?>"
                                            <?= ($user_avis && $user_avis['note'] == $i) ? 'checked' : '' ?> required>
                                        <label for="star<?= $i ?>" class="star-label">
                                            <i class="bi bi-star-fill text-warning"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <small class="text-avis">Cliquez sur les étoiles pour donner votre note</small>
                            </div>

                            <div class="mb-4">
                                <label for="commentaire" class="form-label fw-bold">Votre commentaire :</label>
                                <textarea class="form-control" id="commentaire" name="commentaire" rows="5"
                                    placeholder="Partagez votre expérience avec EcoRide..." required><?= htmlspecialchars($user_avis['commentaire'] ?? '') ?></textarea>
                                <small class="text-avis">Minimum 10 caractères</small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="/pages/avis.php" class="btn">
                                    <i class="bi bi-arrow-left me-2"></i>Retour aux avis
                                </a>
                                <button type="submit" name="submit_avis" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i>
                                    <?= $user_avis ? 'Mettre à jour' : 'Publier' ?> mon avis
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($user_avis): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Votre avis actuel</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <?php if (!empty($user['photo'])): ?>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($user['photo']) ?>"
                                        alt="Votre photo" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="d-flex justify-content-center align-items-center rounded-circle bg-light me-3"
                                        style="width: 50px; height: 50px;">
                                        <i class="bi bi-person-fill text-avis"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h6>
                                    <small class="text-avis"><?= date("d/m/Y", strtotime($user_avis['date_avis'])) ?></small>
                                </div>
                            </div>
                            <div class="text-warning mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $user_avis['note']): ?>
                                        <i class="bi bi-star-fill"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <p class="mb-0"><?= htmlspecialchars($user_avis['commentaire']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>