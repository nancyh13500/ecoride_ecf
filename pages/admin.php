<?php
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../lib/pdo.php';

// Vérification du rôle admin (role_id = 1)
requireLogin();

if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 1) {
    $_SESSION['error'] = "Accès refusé. Cette page est réservée aux administrateurs.";
    header('Location: /index.php');
    exit();
}

$currentUser = $_SESSION['user'];
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';

// Nettoyer les messages de session
unset($_SESSION['success'], $_SESSION['error']);

// Récupérer les statistiques générales
$stats = [];

try {
    // Nombre total d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM user");
    $stats['total_users'] = $stmt->fetchColumn();

    // Nombre d'utilisateurs par rôle
    $stmt = $pdo->query("
        SELECT r.libelle, COUNT(u.user_id) as count 
        FROM role r 
        LEFT JOIN user u ON r.role_id = u.role_id 
        GROUP BY r.role_id, r.libelle
    ");
    $stats['users_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nombre total de covoiturages
    $stmt = $pdo->query("SELECT COUNT(*) as total_covoiturages FROM covoiturage");
    $stats['total_covoiturages'] = $stmt->fetchColumn();

    // Covoiturages par statut
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN statut = 1 THEN 'En cours'
                WHEN statut = 2 THEN 'En attente'
                WHEN statut = 3 THEN 'Terminé'
                ELSE 'Non défini'
            END as statut_name,
            COUNT(*) as count
        FROM covoiturage 
        GROUP BY statut
    ");
    $stats['covoiturages_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nombre total de voitures
    $stmt = $pdo->query("SELECT COUNT(*) as total_voitures FROM voiture");
    $stats['total_voitures'] = $stmt->fetchColumn();

    // Voitures par type d'énergie
    $stmt = $pdo->query("
        SELECT e.libelle, COUNT(v.voiture_id) as count 
        FROM energie e 
        LEFT JOIN voiture v ON e.energie_id = v.energie_id 
        GROUP BY e.energie_id, e.libelle
    ");
    $stats['voitures_by_energy'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nombre d'avis
    $stmt = $pdo->query("SELECT COUNT(*) as total_avis FROM avis");
    $stats['total_avis'] = $stmt->fetchColumn();

    // Moyenne des notes
    $stmt = $pdo->query("SELECT AVG(note) as moyenne_notes FROM avis WHERE note IS NOT NULL");
    $stats['moyenne_notes'] = round($stmt->fetchColumn(), 2);

    // Réservations (si la table existe)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total_reservations FROM reservations");
        $stats['total_reservations'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $stats['total_reservations'] = 0;
    }

    // Covoiturages par jour (30 derniers jours)
    $stmt = $pdo->query("
        SELECT 
            DATE(date_depart) as jour,
            COUNT(*) as nombre_covoiturages
        FROM covoiturage 
        WHERE date_depart >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(date_depart)
        ORDER BY jour ASC
    ");
    $stats['covoiturages_par_jour'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = [];
    $error_message = "Erreur lors du chargement des statistiques : " . $e->getMessage();
}

require_once __DIR__ . '/../templates/header.php';
?>

<section class="hero count-section py-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="ps-3 pt-3 mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Administration</li>
            </ol>
            <div class="col text-end me-3 pb-3">
                <a href="user_count.php" class="btn btn-primary btn-sm d-md-inline-block">Retour Mon compte
                </a>
            </div>
        </nav>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-dark text-white">
                    <div class="card-body">
                        <h2 class="mb-0">
                            <i class="bi bi-shield-check me-2"></i>
                            Tableau de bord Administrateur
                        </h2>
                        <p class="mb-0 mt-2">Bienvenue <?= htmlspecialchars($currentUser['prenom'] . ' ' . $currentUser['nom']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiques générales -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?= $stats['total_users'] ?? 0 ?></h4>
                                <p class="mb-0">Utilisateurs</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-people fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?= $stats['total_covoiturages'] ?? 0 ?></h4>
                                <p class="mb-0">Covoiturages</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-car-front fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?= $stats['total_voitures'] ?? 0 ?></h4>
                                <p class="mb-0">Voitures</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-truck fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?= $stats['total_avis'] ?? 0 ?></h4>
                                <p class="mb-0">Avis</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-star fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détails des statistiques -->
        <div class="row">
            <!-- Utilisateurs par rôle -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>
                            Utilisateurs par rôle
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($stats['users_by_role'])): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Rôle</th>
                                            <th>Nombre</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['users_by_role'] as $role): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($role['libelle']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?= $role['count'] ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune donnée disponible</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Covoiturages par statut -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-car-front me-2"></i>
                            Covoiturages par statut
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($stats['covoiturages_by_status'])): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Statut</th>
                                            <th>Nombre</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['covoiturages_by_status'] as $status): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($status['statut_name']) ?></td>
                                                <td>
                                                    <?php
                                                    $badge_class = 'secondary';
                                                    switch ($status['statut_name']) {
                                                        case 'En cours':
                                                            $badge_class = 'success';
                                                            break;
                                                        case 'En attente':
                                                            $badge_class = 'warning';
                                                            break;
                                                        case 'Terminé':
                                                            $badge_class = 'info';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?= $badge_class ?>"><?= $status['count'] ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune donnée disponible</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Voitures par énergie -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-lightning me-2"></i>
                            Voitures par type d'énergie
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($stats['voitures_by_energy'])): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Type d'énergie</th>
                                            <th>Nombre</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['voitures_by_energy'] as $energy): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($energy['libelle']) ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?= $energy['count'] ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune donnée disponible</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Statistiques des avis -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-star me-2"></i>
                            Statistiques des avis
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-warning"><?= $stats['total_avis'] ?? 0 ?></h4>
                                <p class="mb-0">Total avis</p>
                            </div>
                            <div class="col-6">
                                <h4 class="text-warning"><?= $stats['moyenne_notes'] ?? 0 ?></h4>
                                <p class="mb-0">Note moyenne</p>
                            </div>
                        </div>
                        <?php if (isset($stats['total_reservations']) && $stats['total_reservations'] > 0): ?>
                            <hr>
                            <div class="text-center">
                                <h5 class="text-success"><?= $stats['total_reservations'] ?></h5>
                                <p class="mb-0">Réservations</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique des covoiturages par jour -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up me-2"></i>
                            Évolution des covoiturages (30 derniers jours)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="covoituragesChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-lightning me-2"></i>
                            Actions rapides
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="/pages/employe.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-person-badge me-2"></i>Espace Employé
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="/pages/user_count.php" class="btn btn-outline-success w-100">
                                    <i class="bi bi-person-plus me-2"></i>Créer un employé
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="/pages/avis.php" class="btn btn-outline-warning w-100">
                                    <i class="bi bi-star me-2"></i>Gérer les avis
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="/pages/covoiturage.php" class="btn btn-outline-info w-100">
                                    <i class="bi bi-car-front me-2"></i>Voir les trajets
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Passage des données PHP vers JavaScript -->
<script>
    window.covoituragesData = <?= json_encode($stats['covoiturages_par_jour'] ?? []) ?>;
</script>
<!-- Script Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/chart.js"></script>


<?php require_once __DIR__ . '/../templates/footer.php'; ?>