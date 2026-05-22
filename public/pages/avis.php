<?php
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../../templates/header.php";
require_once __DIR__ . "/../../lib/pdo.php";
require_once __DIR__ . "/../../lib/mongodb.php";

// Récupérer les avis validés depuis MongoDB
$avis_list = [];
$error_message = '';
$moyenne_notes = 0;
$total_notes = 0;
$count_notes = 0;

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

            // Ajouter le covoiturage_id si présent
            if (isset($avis['covoiturage_id'])) {
                $avisArray['covoiturage_id'] = $avis['covoiturage_id'];
            }

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

            // Récupérer les informations du covoiturage si présent
            if (isset($avisArray['covoiturage_id'])) {
                try {
                    $covQuery = $pdo->prepare("SELECT lieu_depart, lieu_arrivee, date_depart, heure_depart FROM covoiturage WHERE covoiturage_id = :covoiturage_id LIMIT 1");
                    $covQuery->execute(['covoiturage_id' => $avisArray['covoiturage_id']]);
                    $covData = $covQuery->fetch(PDO::FETCH_ASSOC);

                    if ($covData) {
                        $avisArray['covoiturage_lieu_depart'] = $covData['lieu_depart'];
                        $avisArray['covoiturage_lieu_arrivee'] = $covData['lieu_arrivee'];
                        $avisArray['covoiturage_date_depart'] = $covData['date_depart'];
                        $avisArray['covoiturage_heure_depart'] = $covData['heure_depart'];
                    }
                } catch (PDOException $e) {
                    // En cas d'erreur, on continue sans les infos du covoiturage
                }
            }

            $avis_list[] = $avisArray;

            // Calculer la moyenne des notes
            if (isset($avis['note']) && is_numeric($avis['note'])) {
                $total_notes += (float)$avis['note'];
                $count_notes++;
            }
        }

        // Calculer la note moyenne
        $moyenne_notes = $count_notes > 0 ? round($total_notes / $count_notes, 2) : 0;
    }
} catch (Exception $e) {
    $avis_list = [];
    $error_message = "Erreur lors de la récupération des avis : " . $e->getMessage();
    error_log("Erreur MongoDB dans avis.php : " . $e->getMessage());
    $moyenne_notes = 0;
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
            <div class="row text-center justify-content-center">
                <div class="col-6">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
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
            <div class="container">
                <!-- Affichage de la note moyenne -->
                <div class="row mb-4 justify-content-center">
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card bg-light text-dark">
                            <div class="card-body">
                                <div class="d-flex flex-column align-items-center justify-content-center text-center">
                                    <div class="mb-2">
                                        <h3 class="mb-0">
                                            <i class="bi bi-star-fill me-2"></i>
                                            Note moyenne : <?= $moyenne_notes ?>/5
                                        </h3>
                                    </div>
                                    <div>
                                        <p class="mb-0">Basée sur <?= $count_notes ?> avis publiés</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th scope="col" style="width: 15%;">Utilisateur</th>
                                <th scope="col" style="width: 10%;">Note</th>
                                <th scope="col" style="width: 15%;">Covoiturage</th>
                                <th scope="col" style="width: 45%;">Commentaire</th>
                                <th scope="col" style="width: 15%;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avis_list as $avis): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars(trim(($avis['prenom'] ?? '') . ' ' . ($avis['nom'] ?? '')) ?: ($avis['pseudo'] ?? 'Utilisateur')) ?></strong>
                                    </td>
                                    <td>
                                        <div class="text-warning text-center">
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
                                    </td>
                                    <td class="text-center">
                                        <?php if (isset($avis['covoiturage_lieu_depart']) && isset($avis['covoiturage_lieu_arrivee'])): ?>
                                            <small>
                                                <i class="bi bi-geo-alt me-1"></i>
                                                <?= htmlspecialchars($avis['covoiturage_lieu_depart'] . ' → ' . $avis['covoiturage_lieu_arrivee']) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted small">Avis général</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <p class="mb-0"><?= htmlspecialchars($avis['commentaire'] ?? '') ?></p>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">
                                            <?php if (!empty($avis['created_at'])): ?>
                                                <?= date('d/m/Y', strtotime($avis['created_at'])) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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

<?php require_once __DIR__ . "/../../templates/footer.php";
?>