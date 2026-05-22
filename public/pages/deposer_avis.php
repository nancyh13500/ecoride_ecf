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

// Récupérer les covoiturages auxquels l'utilisateur a participé
$covoiturages = [];
try {
    $currentUserId = (int)$user['user_id'];

    $stmt = $pdo->prepare("
        SELECT c.covoiturage_id, c.lieu_depart, c.lieu_arrivee, c.date_depart, c.heure_depart
        FROM covoiturage c
        WHERE c.user_id = :user_id
        AND (c.date_depart < CURDATE() OR (c.date_depart = CURDATE() AND c.heure_depart < CURTIME()))
        ORDER BY c.date_depart DESC, c.heure_depart DESC
        LIMIT 20
    ");
    $stmt->execute([':user_id' => $currentUserId]);
    $covoituragesConducteur = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reservationSupport = null;
    $reservationTables = ['reservations', 'reservation'];
    foreach ($reservationTables as $tableName) {
        try {
            $pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");
            $reservationSupport = $tableName;
            break;
        } catch (Throwable $ignored) {
            continue;
        }
    }

    $covoituragesPassager = [];
    if ($reservationSupport) {
        $stmt = $pdo->prepare("
            SELECT c.covoiturage_id, c.lieu_depart, c.lieu_arrivee, c.date_depart, c.heure_depart
            FROM {$reservationSupport} r
            JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
            WHERE r.user_id = :user_id
            AND (c.date_depart < CURDATE() OR (c.date_depart = CURDATE() AND c.heure_depart < CURTIME()))
            ORDER BY c.date_depart DESC, c.heure_depart DESC
            LIMIT 20
        ");
        $stmt->execute([':user_id' => $currentUserId]);
        $covoituragesPassager = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $covoituragesIds = [];
    foreach ($covoituragesConducteur as $cov) {
        $id = (int)$cov['covoiturage_id'];
        if (!isset($covoituragesIds[$id])) {
            $covoituragesIds[$id] = $cov;
            $covoiturages[] = $cov;
        }
    }
    foreach ($covoituragesPassager as $cov) {
        $id = (int)$cov['covoiturage_id'];
        if (!isset($covoituragesIds[$id])) {
            $covoituragesIds[$id] = $cov;
            $covoiturages[] = $cov;
        }
    }
} catch (Exception $e) {
    $covoiturages = [];
}

// Traitement du formulaire d'avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_avis'])) {
    verifyCSRFToken(); // vérification CSRF

    $note = intval($_POST['note']);
    $commentaire = trim($_POST['commentaire']);
    $covoiturage_id = isset($_POST['covoiturage_id']) && $_POST['covoiturage_id'] !== ''
        ? intval($_POST['covoiturage_id'])
        : null;

    if ($note < 1 || $note > 5) {
        $error_message = "La note doit être comprise entre 1 et 5.";
    } elseif (empty($commentaire) || strlen($commentaire) < 10) {
        $error_message = "Le commentaire doit contenir au moins 10 caractères.";
    } elseif (strlen($commentaire) > 250) {
        $error_message = "Le commentaire ne doit pas dépasser 250 caractères (actuellement " . strlen($commentaire) . " caractères).";
    } else {
        if ($covoiturage_id !== null) {
            $covoiturageValide = false;
            foreach ($covoiturages as $cov) {
                if ((int)$cov['covoiturage_id'] === $covoiturage_id) {
                    $covoiturageValide = true;
                    break;
                }
            }
            if (!$covoiturageValide) {
                $error_message = "Le covoiturage sélectionné n'est pas valide.";
            }
        }

        if (empty($error_message)) {
            try {
                $avisCollection = getAvisCollection();

                if ($avisCollection === null) {
                    throw new Exception("MongoDB n'est pas disponible. Veuillez contacter l'administrateur.");
                }

                $avisDocument = [
                    'user_id' => (int)$user['user_id'],
                    'note' => $note,
                    'commentaire' => $commentaire,
                    'statut' => 'en attente',
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];

                if ($covoiturage_id !== null) {
                    $avisDocument['covoiturage_id'] = $covoiturage_id;
                }

                $result = $avisCollection->insertOne($avisDocument);

                if ($result->getInsertedCount() > 0) {
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
}

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
                            <?php csrfField(); ?> <!-- ← ajout token CSRF -->

                            <div class="mb-4">
                                <label for="covoiturage_id" class="form-label fw-bold">Covoiturage concerné (optionnel) :</label>
                                <select class="form-select" id="covoiturage_id" name="covoiturage_id">
                                    <option value="">Avis général sur EcoRide</option>
                                    <?php if (!empty($covoiturages)): ?>
                                        <?php foreach ($covoiturages as $cov): ?>
                                            <?php
                                            $dateDepart = '';
                                            if (!empty($cov['date_depart']) && $cov['date_depart'] !== '0000-00-00') {
                                                $dateDepart = date('d/m/Y', strtotime($cov['date_depart']));
                                            }
                                            $heureDepart = '';
                                            if (!empty($cov['heure_depart']) && $cov['heure_depart'] !== '00:00:00') {
                                                $heureDepart = date('H:i', strtotime($cov['heure_depart']));
                                            }
                                            $label = htmlspecialchars($cov['lieu_depart'] . ' → ' . $cov['lieu_arrivee']);
                                            if ($dateDepart) {
                                                $label .= ' (' . $dateDepart;
                                                if ($heureDepart) {
                                                    $label .= ' à ' . $heureDepart;
                                                }
                                                $label .= ')';
                                            }
                                            ?>
                                            <option value="<?= htmlspecialchars((string)$cov['covoiturage_id']) ?>">
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-avis">Sélectionnez un covoiturage auquel vous avez participé, ou laissez "Avis général" pour un avis global</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Votre note globale :</label>
                                <div class="d-flex align-items-center justify-content-between">
                                    <small class="text-avis mb-0">Cliquez sur les étoiles pour donner votre note</small>
                                    <div class="rating-stars">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
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

<script src="/assets/js/rating.js"></script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>