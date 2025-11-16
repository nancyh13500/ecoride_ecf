<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../lib/pdo.php';
require_once __DIR__ . '/../lib/mongodb.php';

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

// Gérer la requête AJAX pour récupérer les crédits du site
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_site_credits') {
    header('Content-Type: application/json');
    $site_credits_ajax = 0;
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'site_credits'");
        if ($checkTable->rowCount() > 0) {
            $stmt = $pdo->query("SELECT total_credits FROM site_credits WHERE site_credits_id = 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $site_credits_ajax = $result ? (int)$result['total_credits'] : 0;
        }
        echo json_encode(['success' => true, 'credits' => $site_credits_ajax]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Récupérer les statistiques générales
$stats = [
    'total_users' => 0,
    'users_by_role' => [],
    'total_covoiturages' => 0,
    'covoiturages_by_status' => [],
    'total_voitures' => 0,
    'voitures_by_energy' => [],
    'total_avis' => 0,
    'moyenne_notes' => 0,
    'total_reservations' => 0,
    'covoiturages_par_jour' => []
];

// Liste des employés (role_id = 2)
$employees = [];
try {
    $stmt = $pdo->prepare("SELECT user_id, nom, prenom, email, telephone, pseudo, role_covoiturage, credits, suspended FROM user WHERE role_id = 2 ORDER BY prenom, nom");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    // Si la colonne suspended n'existe pas, retenter sans
    try {
        $stmt = $pdo->prepare("SELECT user_id, nom, prenom, email, telephone, pseudo, role_covoiturage, credits FROM user WHERE role_id = 2 ORDER BY prenom, nom");
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        // Marquer suspendu à 0 par défaut
        foreach ($employees as &$emp) {
            $emp['suspended'] = 0;
        }
        unset($emp);
    } catch (PDOException $e2) {
    }
}

// Liste des utilisateurs (role_id = 3)
$users = [];
try {
    $stmt = $pdo->prepare("SELECT user_id, nom, prenom, email, telephone, pseudo, role_covoiturage, credits, suspended FROM user WHERE role_id = 3 ORDER BY prenom, nom");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    // Si la colonne suspended n'existe pas, retenter sans
    try {
        $stmt = $pdo->prepare("SELECT user_id, nom, prenom, email, telephone, pseudo, role_covoiturage, credits FROM user WHERE role_id = 3 ORDER BY prenom, nom");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($users as &$u) {
            $u['suspended'] = 0;
        }
        unset($u);
    } catch (PDOException $e2) {
    }
}

// Nombre total d'utilisateurs
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM user");
    $stats['total_users'] = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
}

// Nombre d'utilisateurs par rôle
try {
    $stmt = $pdo->query("SELECT r.libelle, COUNT(u.user_id) as count FROM role r LEFT JOIN user u ON r.role_id = u.role_id GROUP BY r.role_id, r.libelle");
    $stats['users_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
}

// Nombre total de covoiturages
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM covoiturage");
    $stats['total_covoiturages'] = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
}

// Covoiturages par statut
try {
    $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM covoiturage GROUP BY statut");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $map = [
        1 => 'En attente',
        2 => 'En cours',
        3 => 'Terminé'
    ];
    $stats['covoiturages_by_status'] = array_map(function ($row) use ($map) {
        $code = (int)($row['statut'] ?? 0);
        return [
            'statut_name' => $map[$code] ?? 'Non défini',
            'count' => (int)$row['count']
        ];
    }, $rows);
} catch (PDOException $e) {
}

// Nombre total de voitures
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM voiture");
    $stats['total_voitures'] = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
}

// Voitures par type d'énergie
try {
    $stmt = $pdo->query("SELECT e.libelle, COUNT(v.voiture_id) as count FROM energie e LEFT JOIN voiture v ON e.energie_id = v.energie_id GROUP BY e.energie_id, e.libelle");
    $stats['voitures_by_energy'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
}

// Nombre d'avis et moyenne depuis MongoDB
$avis_list = [];
$total_notes = 0;
$count_notes = 0;
try {
    $avisCollection = getAvisCollection();

    if ($avisCollection !== null) {
        // Récupérer tous les avis depuis MongoDB
        $cursor = $avisCollection->find([], ['sort' => ['created_at' => -1]]);

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
                $userQuery = $pdo->prepare("SELECT nom, prenom, pseudo, email, photo FROM user WHERE user_id = :user_id LIMIT 1");
                $userQuery->execute(['user_id' => $avis['user_id']]);
                $userData = $userQuery->fetch(PDO::FETCH_ASSOC);

                if ($userData) {
                    $avisArray['nom'] = $userData['nom'];
                    $avisArray['prenom'] = $userData['prenom'];
                    $avisArray['pseudo'] = $userData['pseudo'];
                    $avisArray['email'] = $userData['email'];
                    $avisArray['photo'] = $userData['photo'];
                } else {
                    $avisArray['nom'] = 'Utilisateur';
                    $avisArray['prenom'] = '';
                    $avisArray['pseudo'] = 'Anonyme';
                    $avisArray['email'] = '';
                    $avisArray['photo'] = null;
                }
            } catch (PDOException $e) {
                $avisArray['nom'] = 'Utilisateur';
                $avisArray['prenom'] = '';
                $avisArray['pseudo'] = 'Anonyme';
                $avisArray['email'] = '';
                $avisArray['photo'] = null;
            }

            $avis_list[] = $avisArray;

            // Calculer la moyenne
            if (isset($avis['note']) && is_numeric($avis['note'])) {
                $total_notes += (float)$avis['note'];
                $count_notes++;
            }
        }

        $stats['total_avis'] = count($avis_list);
        $stats['moyenne_notes'] = $count_notes > 0 ? round($total_notes / $count_notes, 2) : 0;
    } else {
        $stats['total_avis'] = 0;
        $stats['moyenne_notes'] = 0;
    }
} catch (Exception $e) {
    $stats['total_avis'] = 0;
    $stats['moyenne_notes'] = 0;
    error_log("Erreur lors de la récupération des avis depuis MongoDB : " . $e->getMessage());
}

// Réservations (si la table existe)
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM reservations");
    $stats['total_reservations'] = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
}

// Covoiturages par jour (30 derniers jours)
try {
    $stmt = $pdo->query("SELECT DATE(date_depart) as jour, COUNT(*) as nombre_covoiturages FROM covoiturage WHERE date_depart >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(date_depart) ORDER BY jour ASC");
    $stats['covoiturages_par_jour'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
}

// Récupérer les crédits du site
$site_credits = 0;
try {
    // Vérifier si la table existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'site_credits'");
    if ($checkTable->rowCount() > 0) {
        $stmt = $pdo->query("SELECT total_credits FROM site_credits WHERE site_credits_id = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $site_credits = $result ? (int)$result['total_credits'] : 0;
    }
} catch (PDOException $e) {
    // Si la table n'existe pas ou erreur, laisser à 0
    $site_credits = 0;
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
            <div class="col-md-2 mb-3">
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
            <div class="col-md-2 mb-3">
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
            <div class="col-md-2 mb-3">
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
            <div class="col-md-2 mb-3">
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
            <div class="col-md-2 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" id="site-credits-display"><?= $site_credits ?></h4>
                                <p class="mb-0">Crédits du site</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-coin fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <button type="button" class="btn btn-light btn-sm" onclick="refreshSiteCredits()" title="Actualiser les crédits du site">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
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

        <!-- Liste des avis déposés -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-star me-2"></i>
                            Avis déposés (Moyenne: <?= $stats['moyenne_notes'] ?? 0 ?>/5)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($avis_list)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-star text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">Aucun avis</h5>
                                <p class="text-muted">Aucun avis n'a été déposé pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle text-center">
                                    <thead class="table-light text-dark text-center">
                                        <tr>
                                            <th>ID</th>
                                            <th>Utilisateur</th>
                                            <th>Note</th>
                                            <th>Commentaire</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($avis_list as $avis): ?>
                                            <?php
                                            $statut = $avis['statut'] ?? 'en attente';
                                            $badge_class = 'secondary';
                                            $statut_text = 'Inconnu';

                                            switch ($statut) {
                                                case 'valide':
                                                    $badge_class = 'success';
                                                    $statut_text = 'Validé';
                                                    break;
                                                case 'refuse':
                                                    $badge_class = 'danger';
                                                    $statut_text = 'Refusé';
                                                    break;
                                                case 'en attente':
                                                    $badge_class = 'warning';
                                                    $statut_text = 'En attente';
                                                    break;
                                            }
                                            ?>
                                            <tr>
                                                <td class="text-center"><?= htmlspecialchars(substr($avis['_id'] ?? '', 0, 8)) ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']) ?></strong><br>
                                                        <small class="text-muted">@<?= htmlspecialchars($avis['pseudo']) ?></small><br>
                                                        <small class="text-muted"><?= htmlspecialchars($avis['email']) ?></small>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php if ($i <= ($avis['note'] ?? 5)): ?>
                                                                <i class="bi bi-star-fill"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-star"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <small class="text-muted">(<?= $avis['note'] ?? 5 ?>/5)</small>
                                                </td>
                                                <td>
                                                    <div class="text-center">
                                                        <?= htmlspecialchars($avis['commentaire']) ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <?= htmlspecialchars($avis['created_at'] ?? 'N/A') ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?= $badge_class ?>"><?= $statut_text ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
                    <div class="card-header bg-success text-white">
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

        <!-- Liste des employés -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-people-fill me-2"></i>
                            Liste des employés
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($employees)): ?>
                            <p class="text-muted mb-0">Aucun employé trouvé.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle text-center">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Téléphone</th>
                                            <th>Pseudo</th>
                                            <th>Rôle covoiturage</th>
                                            <th>Statut</th>
                                            <th>Crédits</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employees as $emp): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($emp['prenom'] . ' ' . $emp['nom']) ?></td>
                                                <td><?= htmlspecialchars($emp['email']) ?></td>
                                                <td><?= htmlspecialchars($emp['telephone'] ?: '—') ?></td>
                                                <td><?= htmlspecialchars($emp['pseudo']) ?></td>
                                                <td><?= htmlspecialchars($emp['role_covoiturage'] ?: '—') ?></td>
                                                <td>
                                                    <?php if ((int)($emp['suspended'] ?? 0) === 1): ?>
                                                        <span class="badge bg-warning">Suspendu</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Actif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge bg-success"><?= (int)($emp['credits'] ?? 0) ?></span></td>
                                                <td>
                                                    <form method="POST" action="/lib/admin_user_actions.php" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= (int)$emp['user_id'] ?>">
                                                        <?php if ((int)($emp['suspended'] ?? 0) === 1): ?>
                                                            <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">Activer</button>
                                                        <?php else: ?>
                                                            <button type="submit" name="action" value="suspend" class="btn btn-sm btn-warning">Suspendre</button>
                                                        <?php endif; ?>
                                                    </form>
                                                    <form method="POST" action="/lib/admin_user_actions.php" class="d-inline" onsubmit="return confirm('Supprimer définitivement cet utilisateur ? Cette action est irréversible.');">
                                                        <input type="hidden" name="user_id" value="<?= (int)$emp['user_id'] ?>">
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">Supprimer</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des utilisateurs -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>
                            Liste des utilisateurs
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <p class="text-muted mb-0">Aucun utilisateur trouvé.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle text-center">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Téléphone</th>
                                            <th>Pseudo</th>
                                            <th>Rôle covoiturage</th>
                                            <th>Statut</th>
                                            <th>Crédits</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></td>
                                                <td><?= htmlspecialchars($u['email']) ?></td>
                                                <td><?= htmlspecialchars($u['telephone'] ?: '—') ?></td>
                                                <td><?= htmlspecialchars($u['pseudo']) ?></td>
                                                <td><?= htmlspecialchars($u['role_covoiturage'] ?: '—') ?></td>
                                                <td>
                                                    <?php if ((int)($u['suspended'] ?? 0) === 1): ?>
                                                        <span class="badge bg-warning">Suspendu</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Actif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge bg-success"><?= (int)($u['credits'] ?? 0) ?></span></td>
                                                <td>
                                                    <form method="POST" action="/lib/admin_user_actions.php" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                                        <?php if ((int)($u['suspended'] ?? 0) === 1): ?>
                                                            <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">Activer</button>
                                                        <?php else: ?>
                                                            <button type="submit" name="action" value="suspend" class="btn btn-sm btn-warning">Suspendre</button>
                                                        <?php endif; ?>
                                                    </form>
                                                    <form method="POST" action="/lib/admin_user_actions.php" class="d-inline" onsubmit="return confirm('Supprimer définitivement cet utilisateur ? Cette action est irréversible.');">
                                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">Supprimer</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Passage des données PHP vers JavaScript -->
<script>
    window.covoituragesData = <?= json_encode($stats['covoiturages_par_jour'] ?? []) ?>;

    // Fonction pour actualiser les crédits du site
    function refreshSiteCredits() {
        fetch('/pages/admin.php?ajax=get_site_credits')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const displayElement = document.getElementById('site-credits-display');
                    if (displayElement) {
                        // Animation de mise à jour
                        displayElement.style.transition = 'all 0.3s ease';
                        displayElement.style.transform = 'scale(1.2)';
                        displayElement.textContent = data.credits;
                        setTimeout(() => {
                            displayElement.style.transform = 'scale(1)';
                        }, 300);
                    }
                }
            })
            .catch(error => {
                console.error('Erreur lors de la mise à jour des crédits:', error);
            });
    }

    // Actualiser automatiquement toutes les 30 secondes
    setInterval(refreshSiteCredits, 30000);
</script>
<!-- Script Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/chart.js"></script>


<?php require_once __DIR__ . '/../templates/footer.php'; ?>