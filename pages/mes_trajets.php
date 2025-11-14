<?php
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";


// Vérifier si l'utilisateur est connecté
if (!isUserConnected()) {
    header("Location: ../login.php");
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

// Gérer la modification d'un trajet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_trajet'])) {
    $trajet_id = intval($_POST['trajet_id'] ?? 0);

    if ($trajet_id <= 0) {
        $error_message = "Trajet invalide.";
    } else {
        $date_depart = $_POST['date_depart'] ?? null;
        $heure_depart = $_POST['heure_depart'] ?? null;
        $lieu_depart = $_POST['lieu_depart'] ?? null;
        $date_arrivee = $_POST['date_arrivee'] ?? null;
        $heure_arrivee = $_POST['heure_arrivee'] ?? null;
        $lieu_arrivee = $_POST['lieu_arrivee'] ?? null;
        $nb_place = $_POST['nb_place'] ?? null;
        $prix_personne = $_POST['prix_personne'] ?? null;
        $voiture_id = $_POST['voiture_id'] ?? null;

        try {
            $checkStmt = $pdo->prepare("
                SELECT covoiturage_id 
                FROM covoiturage 
                WHERE covoiturage_id = :id AND user_id = :user_id
                LIMIT 1
            ");
            $checkStmt->execute([
                'id' => $trajet_id,
                'user_id' => $user['user_id'],
            ]);

            if (!$checkStmt->fetchColumn()) {
                $error_message = "Trajet introuvable ou non autorisé.";
            } else {
                $updateStmt = $pdo->prepare("
                    UPDATE covoiturage
                    SET date_depart = :date_depart,
                        heure_depart = :heure_depart,
                        lieu_depart = :lieu_depart,
                        date_arrivee = :date_arrivee,
                        heure_arrivee = :heure_arrivee,
                        lieu_arrivee = :lieu_arrivee,
                        nb_place = :nb_place,
                        prix_personne = :prix_personne,
                        voiture_id = :voiture_id
                    WHERE covoiturage_id = :id AND user_id = :user_id
                ");
                $updateStmt->execute([
                    'date_depart' => $date_depart,
                    'heure_depart' => $heure_depart,
                    'lieu_depart' => $lieu_depart,
                    'date_arrivee' => $date_arrivee,
                    'heure_arrivee' => $heure_arrivee,
                    'lieu_arrivee' => $lieu_arrivee,
                    'nb_place' => $nb_place,
                    'prix_personne' => $prix_personne,
                    'voiture_id' => $voiture_id ?: null,
                    'id' => $trajet_id,
                    'user_id' => $user['user_id'],
                ]);

                header("Location: mes_trajets.php?edit_success=1");
                exit();
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la modification du trajet : " . $e->getMessage();
        }
    }
}

// Gérer la soumission du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trajet'])) {
    $date_depart = $_POST['date_depart'];
    $heure_depart = $_POST['heure_depart'];
    $lieu_depart = $_POST['lieu_depart'];
    $date_arrivee = $_POST['date_arrivee'];
    $heure_arrivee = $_POST['heure_arrivee'];
    $lieu_arrivee = $_POST['lieu_arrivee'];
    $nb_place = $_POST['nb_place'];
    $prix_personne = $_POST['prix_personne'];
    $voiture_id = $_POST['voiture_id'];

    try {
        $query = $pdo->prepare("
            INSERT INTO covoiturage (date_depart, heure_depart, lieu_depart, date_arrivee, heure_arrivee, lieu_arrivee, nb_place, prix_personne, user_id, voiture_id, statut)
            VALUES (:date_depart, :heure_depart, :lieu_depart, :date_arrivee, :heure_arrivee, :lieu_arrivee, :nb_place, :prix_personne, :user_id, :voiture_id, 1)
        ");
        $query->execute([
            'date_depart' => $date_depart,
            'heure_depart' => $heure_depart,
            'lieu_depart' => $lieu_depart,
            'date_arrivee' => $date_arrivee,
            'heure_arrivee' => $heure_arrivee,
            'lieu_arrivee' => $lieu_arrivee,
            'nb_place' => $nb_place,
            'prix_personne' => $prix_personne,
            'user_id' => $user['user_id'],
            'voiture_id' => $voiture_id,
        ]);
        // Les crédits seront versés au chauffeur et au site uniquement à la fin du trajet
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

    // CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ
    // Enregistrer le démarrage dans MongoDB (sans bloquer si erreur)
    /*
    try {
        if (file_exists(__DIR__ . '/../lib/duree_trajet.php')) {
            require_once __DIR__ . '/../lib/duree_trajet.php';
            demarrerTrajetMongo($user['user_id'], $trajet_id);
        }
    } catch (Exception $e) {
        // Ne pas bloquer l'exécution en cas d'erreur MongoDB
        error_log("Erreur MongoDB lors du démarrage: " . $e->getMessage());
    }
    */

    header("Location: mes_trajets.php");
    exit();
}
// Gérer l'arrêt du trajet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stop_trajet_id'])) {
    $trajet_id = intval($_POST['stop_trajet_id']);

    try {
        // Récupérer les informations du trajet
        $trajetStmt = $pdo->prepare("
            SELECT prix_personne, nb_place, user_id 
            FROM covoiturage 
            WHERE covoiturage_id = :id AND user_id = :user_id
        ");
        $trajetStmt->execute(['id' => $trajet_id, 'user_id' => $user['user_id']]);
        $trajet = $trajetStmt->fetch(PDO::FETCH_ASSOC);

        if ($trajet) {
            // Vérifier si une table reservations existe pour compter les vrais passagers
            $nb_passagers = $trajet['nb_place']; // Par défaut, utiliser nb_place

            try {
                // Vérifier si la table reservations existe
                $checkReservations = $pdo->query("SHOW TABLES LIKE 'reservations'");
                if ($checkReservations->rowCount() > 0) {
                    // Compter le nombre réel de passagers depuis la table reservations
                    $passagersStmt = $pdo->prepare("
                        SELECT COALESCE(SUM(nb_places_reservees), 0) as total_passagers 
                        FROM reservations 
                        WHERE covoiturage_id = :id AND statut = 'confirmée'
                    ");
                    $passagersStmt->execute(['id' => $trajet_id]);
                    $result = $passagersStmt->fetch(PDO::FETCH_ASSOC);
                    $nb_passagers_reel = intval($result['total_passagers'] ?? 0);

                    // Utiliser le nombre réel de passagers s'il est supérieur à 0
                    if ($nb_passagers_reel > 0) {
                        $nb_passagers = $nb_passagers_reel;
                    }
                }
            } catch (PDOException $e) {
                // Si la table n'existe pas ou erreur, continuer avec nb_place
            }

            // Calculer le total des crédits (prix_personne * nombre de passagers)
            $prix_personne = floatval($trajet['prix_personne']);
            $total_credits = $prix_personne * $nb_passagers;

            // Le chauffeur reçoit les crédits moins les 2 crédits pour le site
            $credits_chauffeur = $total_credits - 2;

            // Mettre à jour le statut du trajet
            $query = $pdo->prepare("UPDATE covoiturage SET statut = 3 WHERE covoiturage_id = :id AND user_id = :user_id");
            $query->execute(['id' => $trajet_id, 'user_id' => $user['user_id']]);

            // CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ
            // Calculer et enregistrer la durée dans MongoDB, puis mettre à jour MySQL
            /*
            try {
                if (file_exists(__DIR__ . '/../lib/duree_trajet.php')) {
                    require_once __DIR__ . '/../lib/duree_trajet.php';
                    $dureeResult = arreterTrajetMongo($user['user_id'], $trajet_id);
                    if ($dureeResult && isset($dureeResult['duration_minutes'])) {
                        // Mettre à jour la durée dans MySQL si disponible
                        $updateDuree = $pdo->prepare("UPDATE covoiturage SET duree = :duree WHERE covoiturage_id = :id AND user_id = :user_id");
                        $updateDuree->execute([
                            'duree' => $dureeResult['duration_minutes'],
                            'id' => $trajet_id,
                            'user_id' => $user['user_id']
                        ]);
                    }
                }
            } catch (Exception $e) {
                // Ne pas bloquer l'exécution en cas d'erreur MongoDB
                error_log("Erreur MongoDB lors de l'arrêt: " . $e->getMessage());
            }
            */

            // Créditer le chauffeur (total - 2 crédits pour le site)
            if ($credits_chauffeur > 0) {
                $creditChauffeurStmt = $pdo->prepare("UPDATE user SET credits = credits + :credits WHERE user_id = :user_id");
                $creditChauffeurStmt->execute([
                    'credits' => $credits_chauffeur,
                    'user_id' => $user['user_id']
                ]);
            }

            // Créditer le site de 2 crédits
            try {
                // Vérifier si la table existe, sinon la créer
                $checkTable = $pdo->query("SHOW TABLES LIKE 'site_credits'");
                if ($checkTable->rowCount() == 0) {
                    // Créer la table si elle n'existe pas
                    $createTableSql = "
                        CREATE TABLE IF NOT EXISTS `site_credits` (
                            `site_credits_id` int NOT NULL AUTO_INCREMENT,
                            `total_credits` int NOT NULL DEFAULT 0,
                            `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`site_credits_id`)
                        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci
                    ";
                    $pdo->exec($createTableSql);

                    // Insérer l'enregistrement initial
                    $insertInitial = $pdo->prepare("INSERT INTO site_credits (total_credits) VALUES (0)");
                    $insertInitial->execute();
                }

                // Vérifier si un enregistrement existe déjà
                $checkSiteStmt = $pdo->prepare("SELECT site_credits_id FROM site_credits WHERE site_credits_id = 1");
                $checkSiteStmt->execute();

                if ($checkSiteStmt->rowCount() > 0) {
                    // Mettre à jour l'enregistrement existant
                    $creditSiteStmt = $pdo->prepare("
                        UPDATE site_credits 
                        SET total_credits = total_credits + 2 
                        WHERE site_credits_id = 1
                    ");
                    $creditSiteStmt->execute();
                } else {
                    // Créer un nouvel enregistrement
                    $insertSiteStmt = $pdo->prepare("INSERT INTO site_credits (site_credits_id, total_credits) VALUES (1, 2)");
                    $insertSiteStmt->execute();
                }
            } catch (PDOException $e) {
                // Ne pas bloquer l'exécution si erreur avec site_credits
                error_log("Erreur lors de la gestion des crédits du site: " . $e->getMessage());
            }
        }

        header("Location: mes_trajets.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Erreur lors de l'arrêt du trajet : " . $e->getMessage();
    }
}

// CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ
// Gérer la mise à jour de la durée
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_duree_trajet_id'])) {
    $trajet_id = intval($_POST['update_duree_trajet_id']);
    $duree_minutes = intval($_POST['duree_minutes']);

    try {
        // Mise à jour MySQL
        $query = $pdo->prepare("UPDATE covoiturage SET duree = :duree WHERE covoiturage_id = :id AND user_id = :user_id");
        $query->execute([
            'duree' => $duree_minutes,
            'id' => $trajet_id,
            'user_id' => $user['user_id']
        ]);
        header("Location: mes_trajets.php");
        exit();
    } catch (Exception $e) {
        $error_message = "Erreur lors de la mise à jour de la durée : " . $e->getMessage();
    }
}
*/

require_once __DIR__ . "/../templates/header.php";

// Messages de succès
$success_message = '';
if (isset($_GET['started']) && $_GET['started'] == '1') {
    $success_message = 'Trajet démarré avec succès !';
} elseif (isset($_GET['from_covoiturage']) && $_GET['from_covoiturage'] == '1') {
    $success_message = 'Connecté avec succès ! Vous pouvez maintenant démarrer votre trajet.';
}
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

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Menu latéral -->
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Mon compte</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="/pages/user_count.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-person-circle me-2"></i>Mes informations
                        </a>
                        <a href="/pages/mes_trajets.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-signpost-2 me-2"></i>Mes trajets
                        </a>
                        <a href="/pages/mes_reservations.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-calendar-check me-2"></i>Mes réservations
                        </a>
                        <a href="/pages/mes_voitures.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-car-front me-2"></i>Mes voitures
                        </a>
                        <?php if (($_SESSION['user']['role_id'] ?? 3) == 2): ?>
                            <a href="/pages/employe.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-person-badge me-2"></i>Espace Employé
                            </a>
                        <?php endif; ?>
                        <?php if (($_SESSION['user']['role_id'] ?? 3) == 1): ?>
                            <a href="/pages/admin.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-gear me-2"></i>Administration
                            </a>
                            <a href="/pages/user_count.php?create_employee=1" class="list-group-item list-group-item-action">
                                <i class="bi bi-person-plus me-2"></i>Créer un employé
                            </a>
                        <?php endif; ?>
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
                <?php if (isset($_GET['edit_success'])): ?>
                    <div class="alert alert-success">Votre trajet a été modifié avec succès !</div>
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
                                                <!-- <th>Durée</th> --> <!-- CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ -->
                                                <th>Statut et action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($trajets as $trajet):
                                                $statutLabels = [1 => 'En attente', 2 => 'En cours', 3 => 'Terminé'];
                                                $statut = $trajet['statut'] ?? 1;
                                                $dateDepartValue = !empty($trajet['date_depart']) ? date('Y-m-d', strtotime($trajet['date_depart'])) : '';
                                                $heureDepartValue = !empty($trajet['heure_depart']) ? date('H:i', strtotime($trajet['heure_depart'])) : '';
                                                $dateArriveeValue = !empty($trajet['date_arrivee']) ? date('Y-m-d', strtotime($trajet['date_arrivee'])) : '';
                                                $heureArriveeValue = !empty($trajet['heure_arrivee']) ? date('H:i', strtotime($trajet['heure_arrivee'])) : '';
                                                $canEdit = ($statut == 1);
                                            ?>
                                                <tr>
                                                    <td>
                                                        <input
                                                            type="checkbox"
                                                            name="delete_trajet_ids[]"
                                                            value="<?= $trajet['covoiturage_id'] ?>"
                                                            class="form-check-input ms-2 border-dark trajet-checkbox"
                                                            data-date-depart="<?= htmlspecialchars($dateDepartValue, ENT_QUOTES) ?>"
                                                            data-heure-depart="<?= htmlspecialchars($heureDepartValue, ENT_QUOTES) ?>"
                                                            data-lieu-depart="<?= htmlspecialchars($trajet['lieu_depart'], ENT_QUOTES) ?>"
                                                            data-date-arrivee="<?= htmlspecialchars($dateArriveeValue, ENT_QUOTES) ?>"
                                                            data-heure-arrivee="<?= htmlspecialchars($heureArriveeValue, ENT_QUOTES) ?>"
                                                            data-lieu-arrivee="<?= htmlspecialchars($trajet['lieu_arrivee'], ENT_QUOTES) ?>"
                                                            data-nb-place="<?= htmlspecialchars($trajet['nb_place'], ENT_QUOTES) ?>"
                                                            data-prix-personne="<?= htmlspecialchars($trajet['prix_personne'], ENT_QUOTES) ?>"
                                                            data-voiture-id="<?= htmlspecialchars($trajet['voiture_id'], ENT_QUOTES) ?>">
                                                    </td>
                                                    <td><?= htmlspecialchars(date("d/m/Y", strtotime($trajet['date_depart']))) ?></td>
                                                    <td><?= htmlspecialchars($trajet['lieu_depart']) ?></td>
                                                    <td><?= htmlspecialchars($trajet['lieu_arrivee']) ?></td>
                                                    <td><?= htmlspecialchars($trajet['prix_personne']) ?></td>
                                                    <td><?= htmlspecialchars($trajet['modele']) ?> (<?= htmlspecialchars($trajet['immatriculation']) ?>)</td>
                                                    <!-- CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ -->
                                                    <!--
                                                    <td>
                                                        <?php if ($trajet['duree']): ?>
                                                            <?php
                                                            $heures = floor($trajet['duree'] / 60);
                                                            $minutes = $trajet['duree'] % 60;
                                                            echo $heures > 0 ? "{$heures}h {$minutes}min" : "{$minutes}min";
                                                            ?>
                                                        <?php elseif ($statut == 2): ?>
                                                            <span id="temps-<?= $trajet['covoiturage_id'] ?>" class="text-primary">En cours...</span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    -->
                                                    <td>
                                                        <?php
                                                        echo '<span class="badge bg-secondary me-2">' . (isset($statutLabels[$statut]) ? $statutLabels[$statut] : 'Inconnu') . '</span>';
                                                        ?>
                                                        <?php if ($statut == 1): // En attente 
                                                        ?>
                                                            <button type="submit" name="start_trajet_id" value="<?= $trajet['covoiturage_id'] ?>" class="btn btn-primary btn-sm mt-2">Démarrer le covoiturage</button>
                                                        <?php elseif ($statut == 2): // En cours 
                                                        ?>
                                                            <button type="submit" name="stop_trajet_id" value="<?= $trajet['covoiturage_id'] ?>" class="btn btn-warning btn-sm mt-2">Arrêter le covoiturage</button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end align-items-center gap-2 mt-3">
                                    <button type="button" id="openEditTrajet" class="btn btn-secondary">Modifier la sélection</button>
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
                                    <div class="col-md-6">
                                        <label for="date_arrivee" class="form-label">Date d'arrivée</label>
                                        <input type="date" class="form-control" id="date_arrivee" name="date_arrivee" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="heure_arrivee" class="form-label">Heure d'arrivée</label>
                                        <input type="time" class="form-control" id="heure_arrivee" name="heure_arrivee" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="nb_place" class="form-label">Nombre de places</label>
                                        <input type="number" class="form-control" id="nb_place" name="nb_place" min="1" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="prix_personne" class="form-label">Crédit par personne</label>
                                        <input type="number" step="1" class="form-control" id="prix_personne" name="prix_personne" required>
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

<!-- Modal de modification -->
<div class="modal fade" id="editTrajetModal" tabindex="-1" aria-labelledby="editTrajetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTrajetModalLabel">Modifier mon trajet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" action="mes_trajets.php">
                <div class="modal-body">
                    <input type="hidden" name="trajet_id" id="edit_trajet_id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_date_depart" class="form-label">Date de départ</label>
                            <input type="date" class="form-control" id="edit_date_depart" name="date_depart" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_heure_depart" class="form-label">Heure de départ</label>
                            <input type="time" class="form-control" id="edit_heure_depart" name="heure_depart" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_lieu_depart" class="form-label">Lieu de départ</label>
                            <input type="text" class="form-control" id="edit_lieu_depart" name="lieu_depart" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_lieu_arrivee" class="form-label">Lieu d'arrivée</label>
                            <input type="text" class="form-control" id="edit_lieu_arrivee" name="lieu_arrivee" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_date_arrivee" class="form-label">Date d'arrivée</label>
                            <input type="date" class="form-control" id="edit_date_arrivee" name="date_arrivee" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_heure_arrivee" class="form-label">Heure d'arrivée</label>
                            <input type="time" class="form-control" id="edit_heure_arrivee" name="heure_arrivee" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="edit_nb_place" class="form-label">Nombre de places</label>
                            <input type="number" class="form-control" id="edit_nb_place" name="nb_place" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_prix_personne" class="form-label">Crédit par personne</label>
                            <input type="number" step="1" class="form-control" id="edit_prix_personne" name="prix_personne" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_voiture_id" class="form-label">Voiture</label>
                            <select class="form-select" id="edit_voiture_id" name="voiture_id" required>
                                <option value="">Sélectionnez une voiture</option>
                                <?php foreach ($voitures as $voiture): ?>
                                    <option value="<?= $voiture['voiture_id'] ?>">
                                        <?= htmlspecialchars($voiture['modele']) ?> (<?= htmlspecialchars($voiture['immatriculation']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="edit_trajet" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ -->
<!-- <script src="/assets/js/temps_trajet.js"></script> -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var editModal = document.getElementById('editTrajetModal');
        var editTrigger = document.getElementById('openEditTrajet');

        if (!editModal || !editTrigger) {
            return;
        }

        editTrigger.addEventListener('click', function() {
            var checked = document.querySelectorAll('.trajet-checkbox:checked');

            if (checked.length === 0) {
                alert('Veuillez sélectionner un trajet à modifier.');
                return;
            }

            if (checked.length > 1) {
                alert('Veuillez ne sélectionner qu\'un seul trajet à modifier.');
                return;
            }

            var checkbox = checked[0];
            var form = editModal.querySelector('form');
            var setValue = function(selector, value) {
                var element = form.querySelector(selector);
                if (element) {
                    element.value = value || '';
                }
            };

            setValue('#edit_trajet_id', checkbox.value);
            setValue('#edit_date_depart', checkbox.getAttribute('data-date-depart'));
            setValue('#edit_heure_depart', checkbox.getAttribute('data-heure-depart'));
            setValue('#edit_lieu_depart', checkbox.getAttribute('data-lieu-depart'));
            setValue('#edit_date_arrivee', checkbox.getAttribute('data-date-arrivee'));
            setValue('#edit_heure_arrivee', checkbox.getAttribute('data-heure-arrivee'));
            setValue('#edit_lieu_arrivee', checkbox.getAttribute('data-lieu-arrivee'));
            setValue('#edit_nb_place', checkbox.getAttribute('data-nb-place'));
            setValue('#edit_prix_personne', checkbox.getAttribute('data-prix-personne'));

            var voitureId = checkbox.getAttribute('data-voiture-id') || '';
            var voitureSelect = form.querySelector('#edit_voiture_id');
            if (voitureSelect) {
                voitureSelect.value = voitureId;
            }

            var modalInstance = bootstrap.Modal.getOrCreateInstance(editModal);
            modalInstance.show();
        });
    });
</script>
<?php require_once __DIR__ . "/../templates/footer.php"; ?>