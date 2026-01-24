<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/mongodb.php";

// Récupérer les avis validés depuis MongoDB
$avis_list = [];
$error_message = '';

try {
    $avisCollection = getAvisCollection();

    if ($avisCollection === null) {
        $error_message = "MongoDB n'est pas disponible.";
        $avis_list = [];
    } else {
        // Récupérer uniquement les avis validés (statut = 'valide')
        $cursor = $avisCollection->find(
            ['statut' => 'valide'],
            ['sort' => ['created_at' => -1]]
        );

        // Convertir les résultats en tableau et enrichir avec les données utilisateur depuis MySQL
        foreach ($cursor as $avis) {
            $avisArray = [
                '_id' => (string)$avis['_id'],
                'user_id' => $avis['user_id'],
                'note' => $avis['note'],
                'commentaire' => $avis['commentaire'],
                'statut' => $avis['statut'],
                'created_at' => isset($avis['created_at']) ? $avis['created_at']->toDateTime()->format('Y-m-d H:i:s') : ''
            ];

            // Récupérer les informations utilisateur depuis MySQL
            try {
                $userQuery = $pdo->prepare("SELECT nom, prenom, pseudo, photo FROM user WHERE user_id = :user_id LIMIT 1");
                $userQuery->execute(['user_id' => $avis['user_id']]);
                $userData = $userQuery->fetch(PDO::FETCH_ASSOC);

                if ($userData) {
                    $avisArray['nom'] = $userData['nom'];
                    $avisArray['prenom'] = $userData['prenom'];
                    $avisArray['pseudo'] = $userData['pseudo'];
                    $avisArray['photo'] = $userData['photo'];
                } else {
                    $avisArray['nom'] = 'Utilisateur';
                    $avisArray['prenom'] = '';
                    $avisArray['pseudo'] = 'Anonyme';
                    $avisArray['photo'] = null;
                }
            } catch (PDOException $e) {
                // Si erreur MySQL, utiliser des valeurs par défaut
                $avisArray['nom'] = 'Utilisateur';
                $avisArray['prenom'] = '';
                $avisArray['pseudo'] = 'Anonyme';
                $avisArray['photo'] = null;
            }

            $avis_list[] = $avisArray;
        }
    }
} catch (Exception $e) {
    $avis_list = [];
    $error_message = "Erreur lors de la récupération des avis : " . $e->getMessage();
    error_log("Erreur MongoDB dans avis.php : " . $e->getMessage());
}
?>

<!-- Testimonial 3 - Bootstrap Brain Component -->
<section class="hero py-5 py-xl-8">
    <div class="avis bg-white">
        <div class="row justify-content-md-center">
            <div class="col-12 col-md-10 col-lg-8 col-xl-7 col-xxl-6">
                <h2 class="fs-6 text-secondary mb-2 text-uppercase text-center">Avis Clients</h2>
                <h4 class="text_avis display-5 mb-4 mb-md-5 text-center">Découvrez ce que nos utilisateurs pensent d'EcoRide</h4>
                <hr class="w-100 mx-auto mb-5 mb-xl-9 border-dark">
            </div>
        </div>
    </div>

    <div class="overflow-hidden">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (empty($avis_list)): ?>
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    <p class="text-muted">Aucun avis disponible pour le moment.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row justify-content-center g-0">
                <?php foreach ($avis_list as $avis): ?>
                    <div class="col-12 col-md-3 d-flex justify-content-center">
                        <div class="card p-3 shadow-sm avis-card mt-2 mb-2">
                            <div class="d-flex justify-content-center mb-3 text-warning border-bottom border-dark">
                                <?php
                                $note = $avis['note'] ?? 5;
                                for ($i = 1; $i <= 5; $i++):
                                ?>
                                    <?php if ($i <= $note): ?>
                                        <i class="bi bi-star-fill"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <h5 class="text-center fw-bold mb-2"><?= htmlspecialchars(($avis['prenom'] ?? 'Utilisateur') . ' ' . ($avis['nom'] ?? 'EcoRide')) ?></h5>
                            <p class="text-center"><?= htmlspecialchars($avis['commentaire'] ?? 'Excellent service, je recommande !') ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h3 class="mb-4">Partagez votre expérience</h3>
                <p class="text-muted mb-4 ms-3 me-3">Votre avis nous aide à améliorer nos services et aide d'autres utilisateurs à faire leur choix.</p>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="/pages/deposer_avis.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-star-fill me-2"></i>Déposer un avis
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-person-fill me-2"></i>Se connecter pour laisser un avis
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php";
?>