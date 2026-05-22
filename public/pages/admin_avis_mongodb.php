<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/mongodb.php";

// Vérifier si l'utilisateur est connecté et est admin ou employé
requireLogin();

$isAdmin = isset($_SESSION['user']) && ($_SESSION['user']['role_id'] == 1);
$isEmploye = isset($_SESSION['user']) && ($_SESSION['user']['role_id'] == 2);

if (!$isAdmin && !$isEmploye) {
    $_SESSION['error'] = "Accès refusé. Cette page est réservée aux administrateurs et employés.";
    header('Location: ../index.php');
    exit();
}

$currentUser = $_SESSION['user'];
$avis_list = [];
$stats = [
    'total' => 0,
    'en_attente' => 0,
    'valides' => 0,
    'refuses' => 0,
    'note_moyenne' => 0
];
$error_message = '';

try {
    $avisCollection = getAvisCollection();

    if ($avisCollection === null) {
        $error_message = "MongoDB n'est pas disponible.";
    } else {
        // Récupérer tous les avis
        $cursor = $avisCollection->find([], ['sort' => ['created_at' => -1]]);

        foreach ($cursor as $avis) {
            $avisArray = [
                '_id' => (string)$avis['_id'],
                'user_id' => $avis['user_id'],
                'note' => $avis['note'],
                'commentaire' => $avis['commentaire'],
                'statut' => $avis['statut'],
                'created_at' => isset($avis['created_at']) ? $avis['created_at']->toDateTime()->format('d/m/Y H:i:s') : '',
                'updated_at' => isset($avis['updated_at']) ? $avis['updated_at']->toDateTime()->format('d/m/Y H:i:s') : ''
            ];

            // Ajouter les informations de validation si présentes
            if (isset($avis['validated_by'])) {
                $avisArray['validated_by'] = $avis['validated_by'];
                $avisArray['validated_at'] = isset($avis['validated_at']) ? $avis['validated_at']->toDateTime()->format('d/m/Y H:i:s') : '';
            }
            if (isset($avis['rejected_by'])) {
                $avisArray['rejected_by'] = $avis['rejected_by'];
                $avisArray['rejected_at'] = isset($avis['rejected_at']) ? $avis['rejected_at']->toDateTime()->format('d/m/Y H:i:s') : '';
            }

            // Récupérer les informations utilisateur depuis MySQL
            try {
                $userQuery = $pdo->prepare("SELECT nom, prenom, pseudo, email FROM user WHERE user_id = :user_id LIMIT 1");
                $userQuery->execute(['user_id' => $avis['user_id']]);
                $userData = $userQuery->fetch(PDO::FETCH_ASSOC);

                if ($userData) {
                    $avisArray['nom'] = $userData['nom'];
                    $avisArray['prenom'] = $userData['prenom'];
                    $avisArray['pseudo'] = $userData['pseudo'];
                    $avisArray['email'] = $userData['email'];
                } else {
                    $avisArray['nom'] = 'Utilisateur';
                    $avisArray['prenom'] = '';
                    $avisArray['pseudo'] = 'Supprimé';
                    $avisArray['email'] = '';
                }
            } catch (PDOException $e) {
                $avisArray['nom'] = 'Utilisateur';
                $avisArray['prenom'] = '';
                $avisArray['pseudo'] = 'Inconnu';
                $avisArray['email'] = '';
            }

            $avis_list[] = $avisArray;
        }

        // Calculer les statistiques
        $stats['total'] = count($avis_list);
        $stats['en_attente'] = count(array_filter($avis_list, fn($a) => $a['statut'] === 'en attente'));
        $stats['valides'] = count(array_filter($avis_list, fn($a) => $a['statut'] === 'valide'));
        $stats['refuses'] = count(array_filter($avis_list, fn($a) => $a['statut'] === 'refuse'));

        $avisValides = array_filter($avis_list, fn($a) => $a['statut'] === 'valide');
        if (count($avisValides) > 0) {
            $stats['note_moyenne'] = round(array_sum(array_column($avisValides, 'note')) / count($avisValides), 2);
        }
    }
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des avis : " . $e->getMessage();
}

require_once __DIR__ . "/../templates/header.php";
?>

<section class="hero count-section py-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="ps-3 pt-3 mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tous les avis</li>
            </ol>
        </nav>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h2 class="mb-0">
                            <i class="bi bi-database me-2"></i>
                            Tous les avis déposés
                        </h2>
                        <p class="mb-0 mt-2">Vue complète de tous les avis (tous statuts confondus)</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary"><?= $stats['total'] ?></h3>
                        <p class="mb-0">Total avis</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning"><?= $stats['en_attente'] ?></h3>
                        <p class="mb-0">En attente</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success"><?= $stats['valides'] ?></h3>
                        <p class="mb-0">Validés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-info"><?= $stats['note_moyenne'] ?: 'N/A' ?></h3>
                        <p class="mb-0">Note moyenne (validés)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des avis -->
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>
                    Liste complète des avis
                </h4>
            </div>
            <div class="card-body">
                <?php if (empty($avis_list)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">Aucun avis trouvé</h5>
                        <p class="text-muted">Aucun avis n'a été déposé pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th>ID</th>
                                    <th>Utilisateur</th>
                                    <th>Note</th>
                                    <th>Commentaire</th>
                                    <th>Statut</th>
                                    <th>Créé le</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($avis_list as $avis): ?>
                                    <tr>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars(substr($avis['_id'], 0, 8)) ?>...</small>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']) ?></strong><br>
                                            <small class="text-muted">@<?= htmlspecialchars($avis['pseudo']) ?></small><br>
                                            <small class="text-muted"><?= htmlspecialchars($avis['email']) ?></small>
                                        </td>
                                        <td>
                                            <div class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $avis['note']): ?>
                                                        <i class="bi bi-star-fill"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted"><?= $avis['note'] ?>/5</small>
                                        </td>
                                        <td>
                                            <p class="mb-0"><?= htmlspecialchars($avis['commentaire']) ?></p>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = 'secondary';
                                            switch ($avis['statut']) {
                                                case 'valide':
                                                    $badge_class = 'success';
                                                    break;
                                                case 'en attente':
                                                    $badge_class = 'warning';
                                                    break;
                                                case 'refuse':
                                                    $badge_class = 'danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?= $badge_class ?>"><?= htmlspecialchars(ucfirst($avis['statut'])) ?></span>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($avis['created_at']) ?></small>
                                            <?php if (!empty($avis['validated_at'])): ?>
                                                <br><small class="text-success">Validé: <?= htmlspecialchars($avis['validated_at']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($avis['rejected_at'])): ?>
                                                <br><small class="text-danger">Refusé: <?= htmlspecialchars($avis['rejected_at']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="/pages/employe.php" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i> Gérer
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <a href="/pages/employe.php" class="btn btn-primary">
                <i class="bi bi-arrow-left me-2"></i>Retour à l'espace employé
            </a>
            <?php if ($isAdmin): ?>
                <a href="/pages/admin.php" class="btn btn-secondary">
                    <i class="bi bi-gear me-2"></i>Administration
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>