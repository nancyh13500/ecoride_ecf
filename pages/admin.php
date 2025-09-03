<?php
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../templates/header.php';
// require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Vérification du rôle admin (à adapter selon ta logique de session)
if (!isset($_SESSION['user']) || ($_SESSION['user']['role_covoiturage'] ?? '') !== 'admin') {
    header('Location: /index.php');
    exit();
}

// Connexion à MongoDB
$mongoClient = new Client('mongodb://localhost:27017');
$db = $mongoClient->ecoride;
$usersCollection = $db->users;
$covoituragesCollection = $db->covoiturages;

// Création d'un employé
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_employee'])) {
    $newUser = [
        'nom' => $_POST['nom'],
        'prenom' => $_POST['prenom'],
        'email' => $_POST['email'],
        'role' => 'employe',
        'suspendu' => false,
        'credits' => 0,
        'created_at' => new UTCDateTime()
    ];
    try {
        $usersCollection->insertOne($newUser);
        $success = "Employé créé avec succès.";
    } catch (Exception $e) {
        $error = "Erreur lors de la création : " . $e->getMessage();
    }
}

// Suspension d'un compte
if (isset($_GET['suspend']) && $_GET['suspend']) {
    $id = new ObjectId($_GET['suspend']);
    $usersCollection->updateOne(['_id' => $id], ['$set' => ['suspendu' => true]]);
}
if (isset($_GET['unsuspend']) && $_GET['unsuspend']) {
    $id = new ObjectId($_GET['unsuspend']);
    $usersCollection->updateOne(['_id' => $id], ['$set' => ['suspendu' => false]]);
}

// Récupération des utilisateurs/employés
$users = $usersCollection->find([])->toArray();

// Statistiques pour les graphiques
$pipelineCovoiturages = [
    [
        '$group' => [
            '_id' => [
                'jour' => [
                    '$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$date_depart']
                ]
            ],
            'count' => ['$sum' => 1]
        ]
    ],
    ['$sort' => ['_id.jour' => 1]]
];
$covoituragesParJour = $covoituragesCollection->aggregate($pipelineCovoiturages)->toArray();

$pipelineCredits = [
    [
        '$group' => [
            '_id' => [
                'jour' => [
                    '$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$date_depart']
                ]
            ],
            'credits' => ['$sum' => '$prix']
        ]
    ],
    ['$sort' => ['_id.jour' => 1]]
];
$creditsParJour = $covoituragesCollection->aggregate($pipelineCredits)->toArray();

$totalCredits = $covoituragesCollection->aggregate([
    ['$group' => ['_id' => null, 'total' => ['$sum' => '$prix']]]
])->toArray();
$totalCredits = $totalCredits[0]['total'] ?? 0;
?>

<div class="container py-5">
    <h1 class="mb-4">Tableau de bord Administrateur</h1>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <h2>Créer un employé</h2>
    <form method="POST" class="row g-3 mb-4">
        <input type="hidden" name="create_employee" value="1">
        <div class="col-md-4">
            <input type="text" name="nom" class="form-control" placeholder="Nom" required>
        </div>
        <div class="col-md-4">
            <input type="text" name="prenom" class="form-control" placeholder="Prénom" required>
        </div>
        <div class="col-md-4">
            <input type="email" name="email" class="form-control" placeholder="Email" required>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Créer</button>
        </div>
    </form>

    <h2>Utilisateurs & Employés</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Crédits</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['nom'] ?? '') ?></td>
                    <td><?= htmlspecialchars($user['prenom'] ?? '') ?></td>
                    <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($user['role'] ?? 'utilisateur') ?></td>
                    <td><?= htmlspecialchars($user['credits'] ?? 0) ?></td>
                    <td><?= !empty($user['suspendu']) ? '<span class="text-danger">Suspendu</span>' : '<span class="text-success">Actif</span>' ?></td>
                    <td>
                        <?php if (empty($user['suspendu'])): ?>
                            <a href="?suspend=<?= $user['_id'] ?>" class="btn btn-warning btn-sm">Suspendre</a>
                        <?php else: ?>
                            <a href="?unsuspend=<?= $user['_id'] ?>" class="btn btn-success btn-sm">Réactiver</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Statistiques</h2>
    <div class="row">
        <div class="col-md-6">
            <canvas id="covoituragesChart"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="creditsChart"></canvas>
        </div>
    </div>
    <div class="mt-4">
        <h4>Total des crédits gagnés par la plateforme : <span class="text-primary fw-bold"><?= $totalCredits ?> €</span></h4>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Données PHP -> JS
    const covoituragesData = <?= json_encode($covoituragesParJour) ?>;
    const creditsData = <?= json_encode($creditsParJour) ?>;

    const labelsCovoiturages = covoituragesData.map(item => item._id.jour);
    const dataCovoiturages = covoituragesData.map(item => item.count);

    const labelsCredits = creditsData.map(item => item._id.jour);
    const dataCredits = creditsData.map(item => item.credits);

    // Graphique covoiturages/jour
    new Chart(document.getElementById('covoituragesChart'), {
        type: 'line',
        data: {
            labels: labelsCovoiturages,
            datasets: [{
                label: 'Covoiturages par jour',
                data: dataCovoiturages,
                borderColor: 'blue',
                backgroundColor: 'rgba(0,0,255,0.1)',
                fill: true
            }]
        }
    });
    // Graphique crédits/jour
    new Chart(document.getElementById('creditsChart'), {
        type: 'line',
        data: {
            labels: labelsCredits,
            datasets: [{
                label: 'Crédits gagnés par jour',
                data: dataCredits,
                borderColor: 'green',
                backgroundColor: 'rgba(0,255,0,0.1)',
                fill: true
            }]
        }
    });
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>