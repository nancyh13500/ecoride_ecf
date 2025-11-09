<?php
require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../lib/pdo.php";

// Récupérer les avis validés depuis la base de données avec gestion d'erreur
try {
    $query = $pdo->prepare("
        SELECT a.*, u.nom, u.prenom, u.photo, u.pseudo
        FROM avis a
        LEFT JOIN user u ON a.user_id = u.user_id
        WHERE a.statut = 'valide'
        ORDER BY a.avis_id DESC
    ");
    $query->execute();
    $avis_list = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la table avis n'existe pas, on utilise des données statiques
    $avis_list = [];
    $error_message = "Table avis non trouvée, affichage des données statiques.";
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
            <div class="row justify-content-center g-4">
                <?php foreach ($avis_list as $avis): ?>
                    <div class="col-12 col-md-3 d-flex justify-content-center">
                        <div class="card p-3 shadow-sm avis-card">
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
                <p class="text-muted mb-4">Votre avis nous aide à améliorer nos services et aide d'autres utilisateurs à faire leur choix.</p>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="/pages/deposer_avis.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-star-fill me-2"></i>Déposer un avis
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-person-fill me-2"></i>Se connecter pour laisser un avis
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php";
?>