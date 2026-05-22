<?php
ob_start();
require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Ecoride\Ecf\Core\Session;

// Connexion PDO
try {
    $pdo = new PDO(
        "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8",
        getenv('DB_USER'),
        getenv('DB_PASS')
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérifier si l'utilisateur est connecté (fonction isUserConnected() définie dans lib/session.php)
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

// Récupérer toutes les villes pour les selects
try {
    $stmt_villes = $pdo->prepare("SELECT ville_id, nom, code_postal FROM ville ORDER BY nom");
    $stmt_villes->execute();
    $villes = $stmt_villes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $villes = [];
}

// Traitement du formulaire d'ajout de voiture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_voiture'])) {
    $modele = $_POST['modele'];
    $immatriculation = $_POST['immatriculation'];
    $couleur = $_POST['couleur'];
    $date_immatriculation = $_POST['date_premire_immatriculation'];
    $marque_id = $_POST['marque_id'];
    $energie_id = $_POST['energie_id'];

    try {
        // Récupérer le libellé d'énergie pour remplir la colonne non nulle `energie`
        $energie_libelle_stmt = $pdo->prepare("SELECT libelle FROM energie WHERE energie_id = :energie_id");
        $energie_libelle_stmt->execute(['energie_id' => $energie_id]);
        $energie_libelle = $energie_libelle_stmt->fetchColumn();
        if ($energie_libelle === false) {
            $energie_libelle = '';
        }

        $query = $pdo->prepare("
            INSERT INTO voiture (modele, immatriculation, energie, couleur, date_premire_immatriculation, marque_id, energie_id, user_id)
            VALUES (:modele, :immatriculation, :energie, :couleur, :date_premire_immatriculation, :marque_id, :energie_id, :user_id)
        ");
        $query->execute([
            'modele' => $modele,
            'immatriculation' => $immatriculation,
            'energie' => $energie_libelle,
            'couleur' => $couleur,
            'date_premire_immatriculation' => $date_immatriculation,
            'marque_id' => $marque_id,
            'energie_id' => $energie_id,
            'user_id' => $user['user_id'],
        ]);
        // Rediriger vers mes_voitures.php avec un message de succès
        header("Location: mes_voitures.php?success=voiture_ajoutee");
        exit();
    } catch (PDOException $e) {
        $error_message = "Erreur lors de l'ajout de la voiture : " . $e->getMessage();
    }
}

// Traitement du formulaire de covoiturage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_covoiturage'])) {
    $date_depart       = isset($_POST['date_depart'])      ? $_POST['date_depart']      : '';
    $heure_depart_input = isset($_POST['heure_depart'])    ? $_POST['heure_depart']     : '';
    $ville_depart_id   = isset($_POST['ville_depart_id'])  ? (int)$_POST['ville_depart_id']  : 0;
    $ville_arrivee_id  = isset($_POST['ville_arrivee_id']) ? (int)$_POST['ville_arrivee_id'] : 0;
    $nb_place          = isset($_POST['nb_place'])         ? $_POST['nb_place']         : '';
    $prix_personne     = isset($_POST['prix_personne'])    ? $_POST['prix_personne']    : '';
    $voiture_id        = isset($_POST['voiture_id'])       ? $_POST['voiture_id']       : '';

    $date_depart_obj   = DateTime::createFromFormat('Y-m-d', $date_depart);
    $date_depart_valide = $date_depart_obj && $date_depart_obj->format('Y-m-d') === $date_depart;
    $heure_depart = $heure_depart_input;
    if (preg_match('/^\d{2}:\d{2}$/', $heure_depart)) {
        $heure_depart .= ':00';
    } elseif (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure_depart)) {
        $heure_depart = '';
    }

    if ($date_depart_valide && $heure_depart !== '' && $ville_depart_id > 0 && $ville_arrivee_id > 0) {
        // Récupérer les noms de ville pour les colonnes lieu_depart / lieu_arrivee
        $stmt_vd = $pdo->prepare("SELECT nom FROM ville WHERE ville_id = :id");
        $stmt_vd->execute(['id' => $ville_depart_id]);
        $lieu_depart = $stmt_vd->fetchColumn() ?: '';

        $stmt_va = $pdo->prepare("SELECT nom FROM ville WHERE ville_id = :id");
        $stmt_va->execute(['id' => $ville_arrivee_id]);
        $lieu_arrivee = $stmt_va->fetchColumn() ?: '';

        $date_arrivee = $date_depart;
        $heure_arrivee = $heure_depart;

        try {
            $pdo->beginTransaction();

            $query = $pdo->prepare("
                INSERT INTO covoiturage
                    (date_depart, heure_depart, lieu_depart, ville_depart_id,
                     date_arrivee, heure_arrivee, lieu_arrivee, ville_arrivee_id,
                     nb_place, prix_personne, user_id, voiture_id, statut)
                VALUES
                    (:date_depart, :heure_depart, :lieu_depart, :ville_depart_id,
                     :date_arrivee, :heure_arrivee, :lieu_arrivee, :ville_arrivee_id,
                     :nb_place, :prix_personne, :user_id, :voiture_id, 1)
            ");
            $query->execute([
                'date_depart'      => $date_depart,
                'heure_depart'     => $heure_depart,
                'lieu_depart'      => $lieu_depart,
                'ville_depart_id'  => $ville_depart_id,
                'date_arrivee'     => $date_arrivee,
                'heure_arrivee'    => $heure_arrivee,
                'lieu_arrivee'     => $lieu_arrivee,
                'ville_arrivee_id' => $ville_arrivee_id,
                'nb_place'         => $nb_place,
                'prix_personne'    => $prix_personne,
                'user_id'          => $user['user_id'],
                'voiture_id'       => $voiture_id,
            ]);
            $covoiturage_id = $pdo->lastInsertId();

            // Insertion des Etape(s) : ordre 1 = départ, intermédiaire(s), dernier = arrivée
            $stmt_etape = $pdo->prepare("
                INSERT INTO etape (covoiturage_id, ville_id, ordre, heure_prevue, date_prevue)
                VALUES (:covoiturage_id, :ville_id, :ordre, :heure_prevue, :date_prevue)
            ");

            $ordre = 1;

            // Étape départ
            $stmt_etape->execute([
                'covoiturage_id' => $covoiturage_id,
                'ville_id'       => $ville_depart_id,
                'ordre'          => $ordre,
                'heure_prevue'   => $heure_depart,
                'date_prevue'    => $date_depart,
            ]);
            $ordre++;

            // Etape(s) intermédiaire(s) (facultative(s))
            $etapes_ville_ids = isset($_POST['etapes_ville_id']) ? $_POST['etapes_ville_id'] : [];
            $etapes_heures    = isset($_POST['etapes_heure'])    ? $_POST['etapes_heure']    : [];

            foreach ($etapes_ville_ids as $i => $etape_ville_id) {
                $etape_ville_id = (int)$etape_ville_id;
                if ($etape_ville_id <= 0) continue;

                $heure_etape = isset($etapes_heures[$i]) ? trim($etapes_heures[$i]) : null;
                if ($heure_etape && preg_match('/^\d{2}:\d{2}$/', $heure_etape)) {
                    $heure_etape .= ':00';
                } elseif (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure_etape ?? '')) {
                    $heure_etape = null;
                }

                $stmt_etape->execute([
                    'covoiturage_id' => $covoiturage_id,
                    'ville_id'       => $etape_ville_id,
                    'ordre'          => $ordre,
                    'heure_prevue'   => $heure_etape,
                    'date_prevue'    => $date_depart,
                ]);
                $ordre++;
            }

            // Étape arrivée
            $stmt_etape->execute([
                'covoiturage_id' => $covoiturage_id,
                'ville_id'       => $ville_arrivee_id,
                'ordre'          => $ordre,
                'heure_prevue'   => null,
                'date_prevue'    => $date_arrivee,
            ]);

            $pdo->commit();
            header("Location: mes_trajets.php?success=1");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Erreur lors de la création du covoiturage : " . $e->getMessage();
        }
    } else {
        $error_message = "Veuillez remplir tous les champs obligatoires (date, heure, ville de départ et d'arrivée).";
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

                    <h3 class="mb-4 pb-2 pb-md-0 mb-md-5 ms-2">Informations</h3>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>



                    <!-- Formulaire de covoiturage -->
                    <?php if (!empty($voitures)): ?>
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
                                    <label class="form-label city_depart" for="ville_depart_id">Ville de départ</label>
                                    <select id="ville_depart_id" name="ville_depart_id" class="form-select bg-light" required>
                                        <option value="">Sélectionnez une ville</option>
                                        <?php foreach ($villes as $ville): ?>
                                            <option value="<?php echo $ville['ville_id']; ?>">
                                                <?php echo htmlspecialchars($ville['nom']); ?> (<?php echo htmlspecialchars($ville['code_postal']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label city_arrivee" for="ville_arrivee_id">Ville d'arrivée</label>
                                    <select id="ville_arrivee_id" name="ville_arrivee_id" class="form-select bg-light" required>
                                        <option value="">Sélectionnez une ville</option>
                                        <?php foreach ($villes as $ville): ?>
                                            <option value="<?php echo $ville['ville_id']; ?>">
                                                <?php echo htmlspecialchars($ville['nom']); ?> (<?php echo htmlspecialchars($ville['code_postal']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Etape(s) intermédiaire(s) facultative(s) -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="mb-2 fw-semibold">
                                        Etape(s) intermédiaire(s)
                                        <small class="text-muted fw-normal">(facultatif — ex : prise en charge en chemin)</small>
                                    </h6>
                                    <div id="etapes-container"></div>
                                    <button type="button" class="btn btn-primary btn-sm mt-2" onclick="addEtape()">
                                        + Ajouter une étape
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <label class="form-label" for="voiture_id">Voiture</label>
                                    <select id="voiture_id" name="voiture_id" class="form-control bg-light" required>
                                        <option value="">Sélectionnez une voiture</option>
                                        <?php foreach ($voitures as $voiture): ?>
                                            <option value="<?php echo $voiture['voiture_id']; ?>">
                                                <?php echo htmlspecialchars($voiture['modele']); ?> (<?php echo htmlspecialchars($voiture['immatriculation']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                    <label class="form-label" for="nb_place">Nombre de places</label>
                                    <input type="number" id="nb_place" name="nb_place" min="1" class="form-control bg-light" required>
                                </div>
                                <div class="col-md-4 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                    <label class="form-label" for="prix_personne">Crédit / personne</label>
                                    <input type="number" step="1" id="prix_personne" name="prix_personne" class="form-control bg-light" required>
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
                                        <select class="form-select" aria-label="Default select example" name="marque_id">
                                            <option value="">Choisissez une marque</option>
                                            <?php foreach ($marques as $marque): ?>
                                                <option value="<?php echo htmlspecialchars($marque['marque_id']); ?>">
                                                    <?php echo htmlspecialchars($marque['libelle']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <div class="col-md-8 form-outline form-name mb-4" data-mdb-input-initialized="true">
                                        <label class="form-label text-center w-100" for="gridCheck">Energie véhicule</label>
                                        <select class="form-select" aria-label="Default select example" name="energie_id">
                                            <option value="">Choisissez une option</option>
                                            <?php foreach ($energies as $energie): ?>
                                                <option value="<?php echo htmlspecialchars($energie['energie_id']); ?>">
                                                    <?php echo htmlspecialchars($energie['libelle']); ?>
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
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Vous devez d'abord ajouter une voiture pour pouvoir proposer un covoiturage.
                            <a href="/pages/mes_voitures.php" class="alert-link">Ajouter une voiture</a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

</section>

<script>
    const villesData = <?php echo json_encode(array_map(function ($v) {
                            return ['id' => (int)$v['ville_id'], 'nom' => $v['nom'], 'cp' => $v['code_postal']];
                        }, $villes), JSON_UNESCAPED_UNICODE); ?>;

    function buildVilleOptions() {
        return villesData.map(v =>
            `<option value="${v.id}">${v.nom} (${v.cp})</option>`
        ).join('');
    }

    function addEtape() {
        const container = document.getElementById('etapes-container');
        const index = container.children.length;
        const div = document.createElement('div');
        div.className = 'row mb-2 align-items-center etape-row';
        div.innerHTML = `
        <div class="col-md-5 mb-2">
            <select name="etapes_ville_id[]" class="form-select bg-light" required>
                <option value="">Ville de l'étape</option>
                ${buildVilleOptions()}
            </select>
        </div>
        <div class="col-md-4 mb-2">
            <input type="time" name="etapes_heure[]" class="form-control bg-light" placeholder="Heure de passage">
        </div>
        <div class="col-md-3 mb-2">
            <button type="button" class="btn btn-outline-danger btn-sm w-100"
                    onclick="this.closest('.etape-row').remove()">
                Supprimer
            </button>
        </div>
    `;
        container.appendChild(div);
    }
</script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>