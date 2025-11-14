<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/mongodb.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
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
    } elseif (strlen($commentaire) > 250) {
        $error_message = "Le commentaire ne doit pas dépasser 250 caractères (actuellement " . strlen($commentaire) . " caractères).";
    } else {
        try {
            $avisCollection = getAvisCollection();
            
            if ($avisCollection === null) {
                throw new Exception("MongoDB n'est pas disponible. Veuillez contacter l'administrateur.");
            }

            // Créer un nouvel avis dans MongoDB
            $avisDocument = [
                'user_id' => (int)$user['user_id'],
                'note' => $note,
                'commentaire' => $commentaire,
                'statut' => 'en attente',
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];

            $result = $avisCollection->insertOne($avisDocument);
            
            if ($result->getInsertedCount() > 0) {
                // Rediriger vers la page avis avec un message de succès
                $_SESSION['success_message'] = "Votre avis a été publié avec succès ! Il sera visible après validation par un employé.";
                header("Location: /pages/avis.php");
                exit();
            } else {
                throw new Exception("Erreur lors de l'insertion de l'avis.");
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de la publication de votre avis : " . $e->getMessage();
        }
    }
}

// Le formulaire est toujours vide pour permettre de déposer un nouvel avis
$user_avis = null;

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
                            Déposer un avis
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

                        <form method="POST" action="deposer_avis.php" id="avisForm">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Votre note globale :</label>
                                <div class="d-flex align-items-center justify-content-between">
                                    <small class="text-avis mb-0">Cliquez sur les étoiles pour donner votre note</small>
                                    <div class="rating-stars">
                                        <?php
                                        for ($i = 5; $i >= 1; $i--):
                                        ?>
                                            <input type="radio" name="note" value="<?= $i ?>" id="star<?= $i ?>" required>
                                            <label for="star<?= $i ?>" class="star-label">
                                                <i class="bi bi-star text-warning"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="commentaire" class="form-label fw-bold">Votre commentaire :</label>
                                <textarea class="form-control" id="commentaire" name="commentaire" rows="5"
                                    placeholder="Partagez votre expérience avec EcoRide..." maxlength="250" required></textarea>
                                <small class="text-avis">Entre 10 et 250 caractères (<span id="charCount">0</span>/250)</small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="/pages/avis.php" class="btn">
                                    <i class="bi bi-arrow-left me-2"></i>Retour aux avis
                                </a>
                                <button type="submit" name="submit_avis" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i>
                                    Publier mon avis
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Compteur de caractères pour le commentaire
        const commentaireTextarea = document.getElementById('commentaire');
        const charCountSpan = document.getElementById('charCount');

        if (commentaireTextarea && charCountSpan) {
            // Initialiser le compteur avec le contenu existant
            charCountSpan.textContent = commentaireTextarea.value.length;

            // Mettre à jour le compteur à chaque saisie
            commentaireTextarea.addEventListener('input', function() {
                charCountSpan.textContent = this.value.length;
            });
        }

        const ratingStars = document.querySelectorAll('.rating-stars input[type="radio"]');
        const starLabels = document.querySelectorAll('.star-label');

        // Fonction pour mettre à jour l'affichage des étoiles
        function updateStars(selectedValue) {
            starLabels.forEach((label, index) => {
                const starIndex = 5 - index; // Inversé car flex-direction: row-reverse
                const icon = label.querySelector('i');

                if (starIndex <= selectedValue) {
                    icon.classList.remove('bi-star');
                    icon.classList.add('bi-star-fill');
                } else {
                    icon.classList.remove('bi-star-fill');
                    icon.classList.add('bi-star');
                }
            });
        }

        // Gérer le clic sur les étoiles
        ratingStars.forEach(radio => {
            radio.addEventListener('change', function() {
                updateStars(parseInt(this.value));
            });
        });

        // Gérer le survol des étoiles
        starLabels.forEach((label, index) => {
            const starIndex = 5 - index;

            label.addEventListener('mouseenter', function() {
                starLabels.forEach((l, i) => {
                    const sIndex = 5 - i;
                    const icon = l.querySelector('i');
                    if (sIndex <= starIndex) {
                        icon.classList.remove('bi-star');
                        icon.classList.add('bi-star-fill');
                    }
                });
            });
        });

        // Réinitialiser au survol de la zone de notation
        const ratingContainer = document.querySelector('.rating-stars');
        ratingContainer.addEventListener('mouseleave', function() {
            const checkedRadio = document.querySelector('.rating-stars input[type="radio"]:checked');
            if (checkedRadio) {
                updateStars(parseInt(checkedRadio.value));
            } else {
                updateStars(0);
            }
        });

        // Initialiser l'affichage au chargement
        const checkedRadio = document.querySelector('.rating-stars input[type="radio"]:checked');
        if (checkedRadio) {
            updateStars(parseInt(checkedRadio.value));
        }
    });
</script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>