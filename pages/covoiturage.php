<?php require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/session.php";

// Vérifier si l'utilisateur est connecté
if (!isUserConnected()) {
    header("Location: /login.php");
    exit();
}

$user = $_SESSION['user'];
$success_message = '';
$error_message = '';

// Récupération des marques depuis la base de données
$stmt = $pdo->prepare("SELECT marque_id, libelle FROM marque ORDER BY libelle");
$stmt->execute();
$marques = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des énergies depuis la base de données
$stmt_energies = $pdo->prepare("SELECT energie_id, libelle FROM energie ORDER BY libelle");
$stmt_energies->execute();
$energies = $stmt_energies->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les voitures de l'utilisateur pour le formulaire
$query_voitures = $pdo->prepare("SELECT voiture_id, modele, immatriculation FROM voiture WHERE user_id = :user_id ORDER BY modele");
$query_voitures->execute(['user_id' => $user['user_id']]);
$voitures = $query_voitures->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_covoiturage'])) {
    $date_depart = $_POST['date_depart'];
    $heure_depart = $_POST['heure_depart'];
    $lieu_depart = $_POST['lieu_depart'];
    $lieu_arrivee = $_POST['lieu_arrivee'];
    $nb_place = $_POST['nb_place'];
    $prix_personne = $_POST['prix_personne'];
    $voiture_id = $_POST['voiture_id'];

    // Normaliser l'heure au format HH:MM:SS
    if (preg_match('/^\d{2}:\d{2}$/', $heure_depart)) {
        $heure_depart .= ':00';
    }

    try {
        $query = $pdo->prepare("
            INSERT INTO covoiturage (date_depart, heure_depart, lieu_depart, lieu_arrivee, nb_place, prix_personne, user_id, voiture_id, statut)
            VALUES (:date_depart, :heure_depart, :lieu_depart, :lieu_arrivee, :nb_place, :prix_personne, :user_id, :voiture_id, 1)
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
        $success_message = "Votre covoiturage a été créé avec succès !";
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la création du covoiturage : " . $e->getMessage();
    }
}
?>

<section class="hero publish w-100 px-4 py-5">

    <div class="publish-title text-center ">
        <div class="container">
            <h1 class="publish-title mt-3 mb-3 fw-bold">Proposer un covoiturage</h1>
        </div>
        <div class="container">
            <h4 class="publish-subtitle mt-3 mb-5">Remplissez les détails de votre trajet pour partager votre voyage.</h4>
        </div>
    </div>

    <div class="row d-flex justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card rounded-3">
                <img src="/assets/img/city.jpg" class="img_city w-100 rounded-top" alt="city">
                <div class="card-body p-4 p-md-5">
                    <h3 class="mb-4 pb-2 pb-md-0 mb-md-5 px-md-2">Informations</h3>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                    <?php endif; ?>

                    <form class="px-md-2" method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" for="date_depart">Date de départ</label>
                                <input type="date" id="date_depart" name="date_depart" class="form-control bg-light" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label" for="heure_depart">Heure de départ</label>
                                <input type="time" id="heure_depart" name="heure_depart" class="form-control bg-light" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label city_depart" for="lieu_depart">Ville de départ</label>
                                <input type="text" id="lieu_depart" name="lieu_depart" class="form-control bg-light" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label city_arrivee" for="lieu_arrivee">Ville d'arrivée</label>
                                <input type="text" id="lieu_arrivee" name="lieu_arrivee" class="form-control bg-light" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label class="form-label" for="voiture_id">Voiture</label>
                                <select id="voiture_id" name="voiture_id" class="form-control bg-light" required>
                                    <option value="">Sélectionnez une voiture</option>
                                    <?php foreach ($voitures as $voiture): ?>
                                        <option value="<?= $voiture['voiture_id'] ?>">
                                            <?= htmlspecialchars($voiture['modele']) ?> (<?= htmlspecialchars($voiture['immatriculation']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                <label class="form-label" for="nb_place">Nombre de places</label>
                                <input type="number" id="nb_place" name="nb_place" min="1" class="form-control bg-light" required>
                            </div>
                            <div class="col-md-4 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                <label class="form-label" for="prix_personne">Prix / personne (€)</label>
                                <input type="number" step="0.01" id="prix_personne" name="prix_personne" class="form-control bg-light" required>
                            </div>
                        </div>

                        <div class="row">
                            <?php if (isUserConnected()): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="col-md-8 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                        <label class="form-label text-center w-100" for="gridCheck">Chauffeur / Passager</label>
                                        <select class="form-select" aria-label="Default select example" required>
                                            <option selected>Choisissez une option</option>
                                            <option value="1">Chauffeur</option>
                                            <option value="2">Passager</option>
                                            <option value="3">Les deux</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-4 mb-4">
                                <div class="col-md-8 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                    <label class="form-label text-center w-100" for="gridCheck">Marque véhicule</label>
                                    <select class="form-select" aria-label="Default select example" name="marque_id" required>
                                        <option value="">Choisissez une marque</option>
                                        <?php foreach ($marques as $marque): ?>
                                            <option value="<?= htmlspecialchars($marque['marque_id']) ?>">
                                                <?= htmlspecialchars($marque['libelle']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="col-md-8 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                    <label class="form-label text-center w-100" for="gridCheck">Energie véhicule</label>
                                    <select class="form-select" aria-label="Default select example" name="energie_id" required>
                                        <option value="">Choisissez une option</option>
                                        <?php foreach ($energies as $energie): ?>
                                            <option value="<?= htmlspecialchars($energie['energie_id']) ?>">
                                                <?= htmlspecialchars($energie['libelle']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                <label class="form-label text-center w-100" for="gridCheck">Voyage écologique</label>
                                <div class="mt-2 d-flex justify-content-center">
                                    <input class="form-check-input border-dark" type="checkbox" id="gridCheck">
                                </div>
                            </div>
                            <div class="col-md-4 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                <label class="form-label text-center w-100" for="gridCheck">Fumeur / Non fumeur</label>
                                <select class="form-select" aria-label="Default select example">
                                    <option selected>Choisissez une option</option>
                                    <option value="1">Fumeur</option>
                                    <option value="2">Non fumeur</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                <label class="form-label text-center w-100" for="checkNativeSwitch">Animal / pas d'animal</label>
                                <div class="d-flex justify-content-center align-items-center mt-2">
                                    <input class="form-check-input border-dark me-2" type="checkbox" id="checkNativeSwitch">
                                    <label class="form-label mb-0" for="checkNativeSwitch">Autorisé</label>
                                </div>
                            </div>
                        </div>

                        <div class="row text-center">
                            <div class="col">
                                <button type="submit" name="add_covoiturage" class="btn btn-secondary btn-lg mt-1 mb-1">Proposer votre trajet</button>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

</section>




<?php require_once __DIR__ . "/../templates/footer.php";
?>