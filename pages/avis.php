<?php
require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../lib/pdo.php";

// Récupérer les avis depuis la base de données avec gestion d'erreur
try {
    $query = $pdo->prepare("
        SELECT a.*, u.nom, u.prenom, u.photo, u.pseudo
        FROM avis a
        LEFT JOIN user u ON a.user_id = u.user_id
        ORDER BY a.date_avis DESC
        LIMIT 6
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
        <?php if (empty($avis_list)): ?>
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    <p class="text-muted">Aucun avis disponible pour le moment.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row gy-4 gy-md-0 gx-xxl-5">
                <?php foreach ($avis_list as $avis): ?>
                    <div class="col-12 col-md-4">
                        <div class="card border-0 border-bottom border-primary shadow-sm">
                            <div class="card-body p-4 p-xxl-5">
                                <figure>
                                    <?php if (!empty($avis['photo'])): ?>
                                        <img class="img-fluid rounded rounded-circle mb-4 border border-5"
                                            loading="lazy"
                                            src="data:image/jpeg;base64,<?= base64_encode($avis['photo']) ?>"
                                            alt="<?= htmlspecialchars(($avis['prenom'] ?? '') . ' ' . ($avis['nom'] ?? '')) ?>"
                                            style="width: 100px; height: 100px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="d-flex justify-content-center align-items-center mb-4 border border-5 rounded rounded-circle bg-light"
                                            style="width: 100px; height: 100px;">
                                            <i class="bi bi-person-fill fs-1 text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <figcaption>
                                        <div class="bsb-ratings text-warning mb-3">
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
                                        <blockquote class="bsb-blockquote-icon mb-4"><?= htmlspecialchars($avis['commentaire'] ?? 'Excellent service, je recommande !') ?></blockquote>
                                        <h4 class="mb-2"><?= htmlspecialchars(($avis['prenom'] ?? 'Utilisateur') . ' ' . ($avis['nom'] ?? 'EcoRide')) ?></h4>
                                        <h5 class="fs-6 text-secondary mb-0"><?= htmlspecialchars($avis['pseudo'] ?? 'Utilisateur EcoRide') ?></h5>
                                        <small class="text-muted"><?= date("d/m/Y", strtotime($avis['date_avis'] ?? 'now')) ?></small>
                                    </figcaption>
                                </figure>
                            </div>
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