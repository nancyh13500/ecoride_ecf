<?php
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";

// Vérifier si l'utilisateur est connecté
if (!isUserConnected()) {
    header("Location: /login.php");
    exit();
}

$user = $_SESSION['user'];

// Récupérer les trajets de l'utilisateur
$query_trajets = $pdo->prepare("
    SELECT c.*, v.modele, v.immatriculation, v.couleur, v.date_premire_immatriculation, m.libelle AS marque_libelle, e.libelle AS energie_libelle
    FROM covoiturage c
    LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
    LEFT JOIN marque m ON v.marque_id = m.marque_id
    LEFT JOIN energie e ON v.energie_id = e.energie_id
    WHERE c.user_id = :user_id
    ORDER BY c.covoiturage_id DESC
");
$query_trajets->execute(['user_id' => $user['user_id']]);
$trajets = $query_trajets->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les voitures de l'utilisateur pour le formulaire d'ajout
$query_voitures = $pdo->prepare("SELECT voiture_id, modele, immatriculation FROM voiture WHERE user_id = :user_id ORDER BY modele");
$query_voitures->execute(['user_id' => $user['user_id']]);
$voitures = $query_voitures->fetchAll(PDO::FETCH_ASSOC);

// Gérer la soumission du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trajet'])) {
    $date_depart = $_POST['date_depart'];
    $heure_depart = $_POST['heure_depart'];
    $lieu_depart = $_POST['lieu_depart'];
    $lieu_arrivee = $_POST['lieu_arrivee'];
    $nb_place = $_POST['nb_place'];
    $prix_personne = $_POST['prix_personne'];
    $voiture_id = $_POST['voiture_id'];

    try {
        $query = $pdo->prepare("
            INSERT INTO covoiturage (date_depart, heure_depart, lieu_depart, lieu_arrivee, nb_place, prix_personne, user_id, voiture_id)
            VALUES (:date_depart, :heure_depart, :lieu_depart, :lieu_arrivee, :nb_place, :prix_personne, :user_id, :voiture_id)
        ");
        $query->execute([
            'date_depart' => $date_depart,
            'heure_depart' => $heure_depart,
            'lieu_depart' => $lieu_depart,
            'lieu_arrivee' => $lieu_arrivee,
            'nb_place' => $nb_place,
            'prix_personne' => $prix_personne,
            'user_id' => $user['user_id'],
            'voiture_id' => $voiture_id,
        ]);
        header("Location: mes_trajets.php?success=1");
        exit();
    } catch (PDOException $e) {
        $error_message = "Erreur lors de l'ajout du trajet : " . $e->getMessage();
    }
}

// Gérer la soumission du formulaire de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selection'])) {
    if (!empty($_POST['delete_trajet_ids'])) {
        $ids_to_delete = $_POST['delete_trajet_ids'];
        $ids_to_delete = array_map('intval', $ids_to_delete);
        $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));

        try {
            $query = $pdo->prepare("DELETE FROM covoiturage WHERE covoiturage_id IN ($placeholders) AND user_id = ?");
            $params = $ids_to_delete;
            $params[] = $user['user_id'];
            $query->execute($params);
            header("Location: mes_trajets.php?delete_success=1");
            exit();
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la suppression du trajet : " . $e->getMessage();
        }
    } else {
        $error_message = "Veuillez sélectionner au moins un trajet à supprimer.";
    }
}

// Gérer le démarrage du trajet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_trajet_id'])) {
    $trajet_id = intval($_POST['start_trajet_id']);
    $query = $pdo->prepare("UPDATE covoiturage SET statut = 2 WHERE covoiturage_id = :id AND user_id = :user_id");
    $query->execute(['id' => $trajet_id, 'user_id' => $user['user_id']]);
    header("Location: mes_trajets.php");
    exit();
}
// Gérer l'arrêt du trajet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stop_trajet_id'])) {
    $trajet_id = intval($_POST['stop_trajet_id']);
    $query = $pdo->prepare("UPDATE covoiturage SET statut = 3 WHERE covoiturage_id = :id AND user_id = :user_id");
    $query->execute(['id' => $trajet_id, 'user_id' => $user['user_id']]);
    header("Location: mes_trajets.php");
    exit();
}

require_once __DIR__ . "/../templates/header.php";
?>

<section class="hero count-section py-5">
    <div class="container">

        <nav aria-label="breadcrumb" class="me-2 mb-4">
            <ol class="breadcrumb ms-2 pt-3">
                <li class="breadcrumb-item "><a href="/index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="/pages/user_count.php">Mon compte</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mes trajets</li>
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
                        <a href="/pages/mes_trajets.php" class="list-group-item list-group-item-action active">Mes trajets</a>
                        <a href="/pages/mes_reservations.php" class="list-group-item list-group-item-action">Mes réservations</a>
                        <a href="/pages/mes_voitures.php" class="list-group-item list-group-item-action">Mes voitures</a>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9">

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Votre trajet a été ajouté avec succès !</div>
                <?php endif; ?>
                <?php if (isset($_GET['delete_success'])): ?>
                    <div class="alert alert-success">La sélection a été supprimée avec succès !</div>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <!-- Liste des trajets -->
                <div class="card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Mes trajets</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($trajets)): ?>
                            <p>Vous n'avez pas encore de trajet enregistré.</p>
                        <?php else: ?>
                            <form method="POST" action="mes_trajets.php">
                                <div class="table-responsive">
                                    <table class="table table-striped text-center">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Date</th>
                                                <th>Départ</th>
                                                <th>Arrivée</th>
                                                <th>Crédits</th>
                                                <th>Voiture</th>
                                                <th>Statut et action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($trajets as $trajet): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="delete_trajet_ids[]" value="<?= $trajet['covoiturage_id'] ?>" class="form-check-input ms-2 border-dark">
                                                    </td>
                                                    <td><?= htmlspecialchars(date("d/m/Y", strtotime($trajet['date_depart']))) ?></td>
                                                    <td><?= htmlspecialchars($trajet['lieu_depart']) ?></td>
                                                    <td><?= htmlspecialchars($trajet['lieu_arrivee']) ?></td>
                                                    <td><?= htmlspecialchars($trajet['prix_personne']) ?></td>
                                                    <td><?= htmlspecialchars($trajet['modele']) ?> (<?= htmlspecialchars($trajet['immatriculation']) ?>)</td>
                                                    <td>
                                                        <?php
                                                        $statutLabels = [1 => 'En attente', 2 => 'En cours', 3 => 'Terminé'];
                                                        $statut = $trajet['statut'] ?? 1;
                                                        echo '<span class="badge bg-secondary me-2">' . (isset($statutLabels[$statut]) ? $statutLabels[$statut] : 'Inconnu') . '</span>';
                                                        if ($statut == 1): // En attente
                                                        ?>
                                                            <button type="submit" name="start_trajet_id" value="<?= $trajet['covoiturage_id'] ?>" class="btn btn-primary btn-sm">Démarrer le covoiturage</button>
                                                        <?php elseif ($statut == 2): // En cours 
                                                        ?>
                                                            <button type="submit" name="stop_trajet_id" value="<?= $trajet['covoiturage_id'] ?>" class="btn btn-warning btn-sm">Arrêter le covoiturage</button>
                                                        <?php endif; ?>
                                                    </td>
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

                <!-- Formulaire d'ajout de trajet (toujours visible) -->
                <?php if (in_array(($_SESSION['user']['role_covoiturage'] ?? ''), ['Chauffeur', 'Les deux'])): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h4 class="mb-0">Ajouter un nouveau trajet</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="mes_trajets.php">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="date_depart" class="form-label">Date de départ</label>
                                        <input type="date" class="form-control" id="date_depart" name="date_depart" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="heure_depart" class="form-label">Heure de départ</label>
                                        <input type="time" class="form-control" id="heure_depart" name="heure_depart" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="lieu_depart" class="form-label">Lieu de départ</label>
                                        <input type="text" class="form-control" id="lieu_depart" name="lieu_depart" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lieu_arrivee" class="form-label">Lieu d'arrivée</label>
                                        <input type="text" class="form-control" id="lieu_arrivee" name="lieu_arrivee" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="nb_place" class="form-label">Nombre de places</label>
                                        <input type="number" class="form-control" id="nb_place" name="nb_place" min="1" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="prix_personne" class="form-label">Prix par personne (€)</label>
                                        <input type="number" step="0.01" class="form-control" id="prix_personne" name="prix_personne" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="voiture_id" class="form-label">Voiture</label>
                                        <select class="form-select" id="voiture_id" name="voiture_id" required>
                                            <option value="">Sélectionnez une voiture</option>
                                            <?php foreach ($voitures as $voiture): ?>
                                                <option value="<?= $voiture['voiture_id'] ?>">
                                                    <?= htmlspecialchars($voiture['modele']) ?> (<?= htmlspecialchars($voiture['immatriculation']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="add_trajet" class="btn btn-primary">Ajouter mon trajet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-4">
                        Vous devez définir votre rôle sur "Chauffeur" ou "Les deux" dans votre profil pour pouvoir ajouter un trajet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>