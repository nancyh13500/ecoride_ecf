<?php
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../templates/header.php';
// MongoDB désactivé

// Vérification du rôle admin (à adapter selon ta logique de session)
if (!isset($_SESSION['user']) || ($_SESSION['user']['role_covoiturage'] ?? '') !== 'admin') {
    header('Location: /index.php');
    exit();
}

// Mongo: fonctionnalités désactivées temporairement
$success = $error = '';
$users = [];
$covoituragesParJour = [];
$creditsParJour = [];
$totalCredits = 0;
?>

<div class="container py-5">
    <h1 class="mb-4">Tableau de bord Administrateur</h1>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="alert alert-info">Fonctionnalités Admin basées sur MongoDB désactivées pour le moment.</div>

    <!-- Contenu admin à réactiver une fois MongoDB remis -->


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