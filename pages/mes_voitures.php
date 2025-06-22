<?php
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";

// Vérifier si l'utilisateur est connecté
if (!isUserConnected()) {
    header("Location: /login.php");
    exit();
}

$user = $_SESSION['user'];

// Récupérer les voitures de l'utilisateur
$stmt_voitures = $pdo->prepare("
    SELECT v.*, m.libelle AS marque_libelle, e.libelle AS energie_libelle
    FROM voiture v
    LEFT JOIN marque m ON v.marque_id = m.marque_id
    LEFT JOIN energie e ON v.energie_id = e.energie_id
    WHERE v.user_id = :user_id
    ORDER BY v.voiture_id DESC
");
$stmt_voitures->execute(['user_id' => $user['user_id']]);
$voitures = $stmt_voitures->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les listes pour les formulaires
$marques_stmt = $pdo->query("SELECT marque_id, libelle FROM marque ORDER BY libelle");
$marques = $marques_stmt->fetchAll(PDO::FETCH_ASSOC);

$energies_stmt = $pdo->query("SELECT energie_id, libelle FROM energie ORDER BY libelle");
$energies = $energies_stmt->fetchAll(PDO::FETCH_ASSOC);

// Gérer la soumission du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_voiture'])) {
    $modele = $_POST['modele'];
    $immatriculation = $_POST['immatriculation'];
    $couleur = $_POST['couleur'];
    $date_immatriculation = $_POST['date_premire_immatriculation'];
    $marque_id = $_POST['marque_id'];
    $energie_id = $_POST['energie_id'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO voiture (modele, immatriculation, couleur, date_premire_immatriculation, marque_id, energie_id, user_id)
            VALUES (:modele, :immatriculation, :couleur, :date_premire_immatriculation, :marque_id, :energie_id, :user_id)
        ");
        $stmt->execute([
            'modele' => $modele,
            'immatriculation' => $immatriculation,
            'couleur' => $couleur,
            'date_premire_immatriculation' => $date_immatriculation,
            'marque_id' => $marque_id,
            'energie_id' => $energie_id,
            'user_id' => $user['user_id'],
        ]);
        header("Location: mes_voitures.php?success=1");
        exit();
    } catch (PDOException $e) {
        $error_message = "Erreur lors de l'ajout de la voiture : " . $e->getMessage();
    }
}

// Gérer la soumission du formulaire de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selection'])) {
    if (!empty($_POST['delete_voiture_ids'])) {
        $ids_to_delete = $_POST['delete_voiture_ids'];
        $ids_to_delete = array_map('intval', $ids_to_delete);
        $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));

        try {
            $stmt = $pdo->prepare("DELETE FROM voiture WHERE voiture_id IN ($placeholders) AND user_id = ?");
            $params = $ids_to_delete;
            $params[] = $user['user_id'];
            $stmt->execute($params);
            header("Location: mes_voitures.php?delete_success=1");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error_message = "Impossible de supprimer : une ou plusieurs voitures sont associées à un covoiturage.";
            } else {
                $error_message = "Erreur lors de la suppression de la voiture : " . $e->getMessage();
            }
        }
    } else {
        $error_message = "Veuillez sélectionner au moins une voiture à supprimer.";
    }
}

require_once __DIR__ . "/../templates/header.php";
?>

<section class="hero count-section py-5">
    <div class="container">
        <!-- Fil d'Ariane -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="/pages/user_count.php">Mon compte</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mes voitures</li>
            </ol>
        </nav>

        <div class="row">
            <!-- Menu latéral -->
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Mon compte</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="/pages/user_count.php" class="list-group-item list-group-item-action">Mes informations</a>
                        <a href="/pages/mes_trajets.php" class="list-group-item list-group-item-action">Mes trajets</a>
                        <a href="/pages/mes_reservations.php" class="list-group-item list-group-item-action">Mes réservations</a>
                        <a href="/pages/mes_voitures.php" class="list-group-item list-group-item-action active">Mes voitures</a>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9">

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Votre voiture a été ajoutée avec succès !</div>
                <?php endif; ?>
                <?php if (isset($_GET['delete_success'])): ?>
                    <div class="alert alert-success">La sélection a été supprimée avec succès !</div>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <!-- Liste des voitures -->
                <div class="card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Mes voitures</h4>
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addCarForm" aria-expanded="false" aria-controls="addCarForm">
                            Ajouter une voiture
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($voitures)): ?>
                            <p>Vous n'avez pas encore de voiture enregistrée.</p>
                        <?php else: ?>
                            <form method="POST" action="mes_voitures.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer les voitures sélectionnées ?');">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Marque</th>
                                                <th>Modèle</th>
                                                <th>Immatriculation</th>
                                                <th>Énergie</th>
                                                <th>Couleur</th>
                                                <th>1ère immat.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($voitures as $voiture): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="delete_voiture_ids[]" value="<?= $voiture['voiture_id'] ?>" class="form-check-input ms-2">
                                                    </td>
                                                    <td><?= htmlspecialchars($voiture['marque_libelle'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($voiture['modele']) ?></td>
                                                    <td><?= htmlspecialchars($voiture['immatriculation']) ?></td>
                                                    <td><?= htmlspecialchars($voiture['energie_libelle'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($voiture['couleur']) ?></td>
                                                    <td><?= htmlspecialchars(date("d/m/Y", strtotime($voiture['date_premire_immatriculation']))) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" name="delete_selection" class="btn btn-danger">Supprimer la sélection</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulaire d'ajout de voiture (replié par défaut) -->
                <div class="collapse" id="addCarForm">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h4 class="mb-0">Ajouter une nouvelle voiture</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="mes_voitures.php">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="marque_id" class="form-label">Marque</label>
                                        <select class="form-select" id="marque_id" name="marque_id" required>
                                            <option value="">Sélectionnez une marque</option>
                                            <?php foreach ($marques as $marque): ?>
                                                <option value="<?= $marque['marque_id'] ?>"><?= htmlspecialchars($marque['libelle']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="modele" class="form-label">Modèle</label>
                                        <input type="text" class="form-control" id="modele" name="modele" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="immatriculation" class="form-label">Immatriculation</label>
                                        <input type="text" class="form-control" id="immatriculation" name="immatriculation" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="couleur" class="form-label">Couleur</label>
                                        <input type="text" class="form-control" id="couleur" name="couleur" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="energie_id" class="form-label">Énergie</label>
                                        <select class="form-select" id="energie_id" name="energie_id" required>
                                            <option value="">Sélectionnez une énergie</option>
                                            <?php foreach ($energies as $energie): ?>
                                                <option value="<?= $energie['energie_id'] ?>"><?= htmlspecialchars($energie['libelle']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_premire_immatriculation" class="form-label">Date de 1ère immatriculation</label>
                                        <input type="date" class="form-control" id="date_premire_immatriculation" name="date_premire_immatriculation" required>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" name="add_voiture" class="btn btn-primary">Ajouter ma voiture</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>