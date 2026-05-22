<?php
require_once __DIR__ . "/../../lib/session.php";
require_once __DIR__ . "/../../lib/pdo.php";


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
$etapes_by_covoiturage = [];
$etapes_details_by_covoiturage = [];

// Récupérer les étapes associées aux trajets affichés
try {
    $trajet_ids = array_map(static function ($t) {
        return (int)($t['covoiturage_id'] ?? 0);
    }, $trajets);
    $trajet_ids = array_values(array_filter($trajet_ids));

    if (!empty($trajet_ids)) {
        $placeholders = implode(',', array_fill(0, count($trajet_ids), '?'));
        $query_etapes = $pdo->prepare("
            SELECT e.covoiturage_id, e.ordre, e.ville_id, e.heure_prevue, v.nom
            FROM etape e
            JOIN ville v ON v.ville_id = e.ville_id
            WHERE e.covoiturage_id IN ($placeholders)
            ORDER BY e.covoiturage_id ASC, e.ordre ASC
        ");
        $query_etapes->execute($trajet_ids);
        $etapes = $query_etapes->fetchAll(PDO::FETCH_ASSOC);

        foreach ($etapes as $etape) {
            $cid = (int)$etape['covoiturage_id'];
            if (!isset($etapes_by_covoiturage[$cid])) {
                $etapes_by_covoiturage[$cid] = [];
            }
            $etapes_by_covoiturage[$cid][] = $etape['nom'];

            if (!isset($etapes_details_by_covoiturage[$cid])) {
                $etapes_details_by_covoiturage[$cid] = [];
            }
            $etapes_details_by_covoiturage[$cid][] = [
                'ville_id' => (int)$etape['ville_id'],
                'heure' => !empty($etape['heure_prevue']) ? substr((string)$etape['heure_prevue'], 0, 5) : '',
            ];
        }
    }
} catch (PDOException $e) {
    $etapes_by_covoiturage = [];
    $etapes_details_by_covoiturage = [];
}

// Récupérer les voitures de l'utilisateur pour le formulaire d'ajout
$query_voitures = $pdo->prepare("SELECT voiture_id, modele, immatriculation FROM voiture WHERE user_id = :user_id ORDER BY modele");
$query_voitures->execute(['user_id' => $user['user_id']]);
$voitures = $query_voitures->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les villes pour les Etape(s) intermédiaire(s)
try {
    $query_villes = $pdo->prepare("SELECT ville_id, nom, code_postal FROM ville ORDER BY nom");
    $query_villes->execute();
    $villes = $query_villes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $villes = [];
}
$villeIdsByNom = [];
foreach ($villes as $ville) {
    $villeIdsByNom[strtolower((string)$ville['nom'])] = (int)$ville['ville_id'];
}

// Gérer la modification d'un trajet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_trajet'])) {
    $trajet_id = intval($_POST['trajet_id'] ?? 0);

    if ($trajet_id <= 0) {
        $error_message = "Trajet invalide.";
    } else {
        $date_depart = $_POST['date_depart'] ?? null;
        $heure_depart = $_POST['heure_depart'] ?? null;
        $ville_depart_id = (int)($_POST['ville_depart_id'] ?? 0);
        $date_arrivee = $_POST['date_arrivee'] ?? null;
        $heure_arrivee = $_POST['heure_arrivee'] ?? null;
        $ville_arrivee_id = (int)($_POST['ville_arrivee_id'] ?? 0);
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
                $pdo->beginTransaction();

                $villeStmt = $pdo->prepare("SELECT nom FROM ville WHERE ville_id = :id");
                $villeStmt->execute(['id' => $ville_depart_id]);
                $lieu_depart = (string)($villeStmt->fetchColumn() ?: '');

                $villeStmt->execute(['id' => $ville_arrivee_id]);
                $lieu_arrivee = (string)($villeStmt->fetchColumn() ?: '');

                $updateStmt = $pdo->prepare("
                    UPDATE covoiturage
                    SET date_depart = :date_depart,
                        heure_depart = :heure_depart,
                        lieu_depart = :lieu_depart,
                        ville_depart_id = :ville_depart_id,
                        date_arrivee = :date_arrivee,
                        heure_arrivee = :heure_arrivee,
                        lieu_arrivee = :lieu_arrivee,
                        ville_arrivee_id = :ville_arrivee_id,
                        nb_place = :nb_place,
                        prix_personne = :prix_personne,
                        voiture_id = :voiture_id
                    WHERE covoiturage_id = :id AND user_id = :user_id
                ");
                $updateStmt->execute([
                    'date_depart' => $date_depart,
                    'heure_depart' => $heure_depart,
                    'lieu_depart' => $lieu_depart,
                    'ville_depart_id' => $ville_depart_id ?: null,
                    'date_arrivee' => $date_arrivee,
                    'heure_arrivee' => $heure_arrivee,
                    'lieu_arrivee' => $lieu_arrivee,
                    'ville_arrivee_id' => $ville_arrivee_id ?: null,
                    'nb_place' => $nb_place,
                    'prix_personne' => $prix_personne,
                    'voiture_id' => $voiture_id ?: null,
                    'id' => $trajet_id,
                    'user_id' => $user['user_id'],
                ]);

                // Réécrire les Etape(s) du trajet : départ -> intermédiaire(s) -> arrivée
                $deleteEtape = $pdo->prepare("DELETE FROM etape WHERE covoiturage_id = :covoiturage_id");
                $deleteEtape->execute(['covoiturage_id' => $trajet_id]);

                $insertEtape = $pdo->prepare("
                    INSERT INTO etape (covoiturage_id, ville_id, ordre, heure_prevue, date_prevue)
                    VALUES (:covoiturage_id, :ville_id, :ordre, :heure_prevue, :date_prevue)
                ");

                $ordre = 1;
                $insertEtape->execute([
                    'covoiturage_id' => $trajet_id,
                    'ville_id' => $ville_depart_id,
                    'ordre' => $ordre,
                    'heure_prevue' => $heure_depart,
                    'date_prevue' => $date_depart,
                ]);
                $ordre++;

                $edit_etapes_ville_ids = isset($_POST['edit_etapes_ville_id']) ? $_POST['edit_etapes_ville_id'] : [];
                $edit_etapes_heures = isset($_POST['edit_etapes_heure']) ? $_POST['edit_etapes_heure'] : [];

                foreach ($edit_etapes_ville_ids as $i => $etape_ville_id) {
                    $etape_ville_id = (int)$etape_ville_id;
                    if ($etape_ville_id <= 0 || $etape_ville_id === $ville_depart_id || $etape_ville_id === $ville_arrivee_id) {
                        continue;
                    }

                    $heure_etape = isset($edit_etapes_heures[$i]) ? trim((string)$edit_etapes_heures[$i]) : null;
                    if ($heure_etape === '') {
                        $heure_etape = null;
                    } elseif (preg_match('/^\d{2}:\d{2}$/', $heure_etape)) {
                        $heure_etape .= ':00';
                    } elseif (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure_etape)) {
                        $heure_etape = null;
                    }

                    $insertEtape->execute([
                        'covoiturage_id' => $trajet_id,
                        'ville_id' => $etape_ville_id,
                        'ordre' => $ordre,
                        'heure_prevue' => $heure_etape,
                        'date_prevue' => $date_depart,
                    ]);
                    $ordre++;
                }

                $insertEtape->execute([
                    'covoiturage_id' => $trajet_id,
                    'ville_id' => $ville_arrivee_id,
                    'ordre' => $ordre,
                    'heure_prevue' => $heure_arrivee,
                    'date_prevue' => $date_arrivee,
                ]);

                $pdo->commit();

                header("Location: mes_trajets.php?edit_success=1");
                exit();
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = "Erreur lors de la modification du trajet : " . $e->getMessage();
        }
    }
}

// Gérer la soumission du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trajet'])) {
    $date_depart = $_POST['date_depart'];
    $heure_depart = $_POST['heure_depart'];
    $ville_depart_id = (int)($_POST['ville_depart_id'] ?? 0);
    $date_arrivee = $_POST['date_arrivee'];
    $heure_arrivee = $_POST['heure_arrivee'];
    $ville_arrivee_id = (int)($_POST['ville_arrivee_id'] ?? 0);
    $nb_place = $_POST['nb_place'];
    $prix_personne = $_POST['prix_personne'];
    $voiture_id = $_POST['voiture_id'];

    try {
        $pdo->beginTransaction();

        $villeStmt = $pdo->prepare("SELECT nom FROM ville WHERE ville_id = :id");
        $villeStmt->execute(['id' => $ville_depart_id]);
        $lieu_depart = (string)($villeStmt->fetchColumn() ?: '');

        $villeStmt->execute(['id' => $ville_arrivee_id]);
        $lieu_arrivee = (string)($villeStmt->fetchColumn() ?: '');

        $query = $pdo->prepare("
            INSERT INTO covoiturage (date_depart, heure_depart, lieu_depart, ville_depart_id, date_arrivee, heure_arrivee, lieu_arrivee, ville_arrivee_id, nb_place, prix_personne, user_id, voiture_id, statut)
            VALUES (:date_depart, :heure_depart, :lieu_depart, :ville_depart_id, :date_arrivee, :heure_arrivee, :lieu_arrivee, :ville_arrivee_id, :nb_place, :prix_personne, :user_id, :voiture_id, 1)
        ");
        $query->execute([
            'date_depart' => $date_depart,
            'heure_depart' => $heure_depart,
            'lieu_depart' => $lieu_depart,
            'ville_depart_id' => $ville_depart_id ?: null,
            'date_arrivee' => $date_arrivee,
            'heure_arrivee' => $heure_arrivee,
            'lieu_arrivee' => $lieu_arrivee,
            'ville_arrivee_id' => $ville_arrivee_id ?: null,
            'nb_place' => $nb_place,
            'prix_personne' => $prix_personne,
            'user_id' => $user['user_id'],
            'voiture_id' => $voiture_id,
        ]);

        $covoiturage_id = (int)$pdo->lastInsertId();

        // Ajouter les Etape(s) intermédiaire(s) si renseignée(s)
        $etapes_ville_ids = isset($_POST['etapes_ville_id']) ? $_POST['etapes_ville_id'] : [];
        $etapes_heures = isset($_POST['etapes_heure']) ? $_POST['etapes_heure'] : [];

        if (!empty($etapes_ville_ids)) {
            // Détecter l'ordre de départ/arrivée existant pour ne pas casser l'unicité (covoiturage_id, ordre)
            $ordreStmt = $pdo->prepare("SELECT COALESCE(MAX(ordre), 0) FROM etape WHERE covoiturage_id = :covoiturage_id");
            $ordreStmt->execute(['covoiturage_id' => $covoiturage_id]);
            $ordre = ((int)$ordreStmt->fetchColumn()) + 1;

            $insertEtape = $pdo->prepare("
                INSERT INTO etape (covoiturage_id, ville_id, ordre, heure_prevue, date_prevue)
                VALUES (:covoiturage_id, :ville_id, :ordre, :heure_prevue, :date_prevue)
            ");

            foreach ($etapes_ville_ids as $i => $villeId) {
                $villeId = (int)$villeId;
                if ($villeId <= 0) {
                    continue;
                }

                $heureEtape = isset($etapes_heures[$i]) ? trim((string)$etapes_heures[$i]) : null;
                if ($heureEtape === '') {
                    $heureEtape = null;
                } elseif (preg_match('/^\d{2}:\d{2}$/', $heureEtape)) {
                    $heureEtape .= ':00';
                } elseif (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $heureEtape)) {
                    $heureEtape = null;
                }

                $insertEtape->execute([
                    'covoiturage_id' => $covoiturage_id,
                    'ville_id' => $villeId,
                    'ordre' => $ordre,
                    'heure_prevue' => $heureEtape,
                    'date_prevue' => $date_depart,
                ]);
                $ordre++;
            }
        }

        $pdo->commit();

        // Les crédits seront versés au chauffeur et au site uniquement à la fin du trajet
        header("Location: mes_trajets.php?success=1");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
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

require_once __DIR__ . "/../../templates/header.php";

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
                                    <table class="table table-striped table-sm align-middle text-center">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Date</th>
                                                <th>Départ</th>
                                                <th>Arrivée</th>
                                                <th>Etape(s)</th>
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
                                                            data-ville-depart-id="<?= htmlspecialchars($trajet['ville_depart_id'] ?: ($villeIdsByNom[strtolower((string)$trajet['lieu_depart'])] ?? ''), ENT_QUOTES) ?>"
                                                            data-date-arrivee="<?= htmlspecialchars($dateArriveeValue, ENT_QUOTES) ?>"
                                                            data-heure-arrivee="<?= htmlspecialchars($heureArriveeValue, ENT_QUOTES) ?>"
                                                            data-ville-arrivee-id="<?= htmlspecialchars($trajet['ville_arrivee_id'] ?: ($villeIdsByNom[strtolower((string)$trajet['lieu_arrivee'])] ?? ''), ENT_QUOTES) ?>"
                                                            data-nb-place="<?= htmlspecialchars($trajet['nb_place'], ENT_QUOTES) ?>"
                                                            data-prix-personne="<?= htmlspecialchars($trajet['prix_personne'], ENT_QUOTES) ?>"
                                                            data-voiture-id="<?= htmlspecialchars($trajet['voiture_id'], ENT_QUOTES) ?>">
                                                    </td>
                                                    <td><?= htmlspecialchars(date("d/m/Y", strtotime($trajet['date_depart']))) ?></td>
                                                    <td><?= htmlspecialchars($trajet['lieu_depart']) ?></td>
                                                    <td><?= htmlspecialchars($trajet['lieu_arrivee']) ?></td>
                                                    <td>
                                                        <?php
                                                        $etapes_trajet = $etapes_by_covoiturage[(int)$trajet['covoiturage_id']] ?? [];
                                                        // Afficher les Etape(s) réellement intermédiaire(s).
                                                        // Si seules des Etape(s) intermédiaire(s) sont stockée(s), elles doivent quand même s'afficher.
                                                        $depart_nom = strtolower(trim((string)$trajet['lieu_depart']));
                                                        $arrivee_nom = strtolower(trim((string)$trajet['lieu_arrivee']));
                                                        $etapes_intermediaires = array_values(array_filter($etapes_trajet, static function ($nom) use ($depart_nom, $arrivee_nom) {
                                                            $nom_normalise = strtolower(trim((string)$nom));
                                                            return $nom_normalise !== '' && $nom_normalise !== $depart_nom && $nom_normalise !== $arrivee_nom;
                                                        }));
                                                        ?>
                                                        <?php if (!empty($etapes_intermediaires)): ?>
                                                            <small class="text-muted"><?= htmlspecialchars(implode(' → ', $etapes_intermediaires)) ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">Direct</span>
                                                        <?php endif; ?>
                                                    </td>
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
                                        <label for="ville_depart_id" class="form-label">Ville de départ</label>
                                        <select class="form-select" id="ville_depart_id" name="ville_depart_id" required>
                                            <option value="">Sélectionnez une ville</option>
                                            <?php foreach ($villes as $ville): ?>
                                                <option value="<?= (int)$ville['ville_id'] ?>">
                                                    <?= htmlspecialchars($ville['nom']) ?> (<?= htmlspecialchars($ville['code_postal']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ville_arrivee_id" class="form-label">Ville d'arrivée</label>
                                        <select class="form-select" id="ville_arrivee_id" name="ville_arrivee_id" required>
                                            <option value="">Sélectionnez une ville</option>
                                            <?php foreach ($villes as $ville): ?>
                                                <option value="<?= (int)$ville['ville_id'] ?>">
                                                    <?= htmlspecialchars($ville['nom']) ?> (<?= htmlspecialchars($ville['code_postal']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <p class="form-label fw-semibold d-block mb-2">
                                            Etape(s) intermédiaire(s)
                                            <small class="text-muted fw-normal">(facultatif)</small>
                                        </p>
                                        <div id="etapes-container-mes-trajets"></div>
                                        <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addEtapeMesTrajet()">
                                            + Ajouter une étape
                                        </button>
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
                            <label for="edit_ville_depart_id" class="form-label">Ville de départ</label>
                            <select class="form-select" id="edit_ville_depart_id" name="ville_depart_id" required>
                                <option value="">Sélectionnez une ville</option>
                                <?php foreach ($villes as $ville): ?>
                                    <option value="<?= (int)$ville['ville_id'] ?>">
                                        <?= htmlspecialchars($ville['nom']) ?> (<?= htmlspecialchars($ville['code_postal']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_ville_arrivee_id" class="form-label">Ville d'arrivée</label>
                            <select class="form-select" id="edit_ville_arrivee_id" name="ville_arrivee_id" required>
                                <option value="">Sélectionnez une ville</option>
                                <?php foreach ($villes as $ville): ?>
                                    <option value="<?= (int)$ville['ville_id'] ?>">
                                        <?= htmlspecialchars($ville['nom']) ?> (<?= htmlspecialchars($ville['code_postal']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                        <div class="col-12">
                            <p class="form-label fw-semibold d-block mb-2">
                                Étape(s) intermédiaire(s)
                                <small class="text-muted fw-normal">(facultatif)</small>
                            </p>
                            <div id="edit-etapes-container"></div>
                            <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addEtapeMesTrajetEdit()">
                                + Ajouter une étape
                            </button>
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
        var etapesParTrajet = <?= json_encode($etapes_details_by_covoiturage, JSON_UNESCAPED_UNICODE); ?>;

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
            setValue('#edit_ville_depart_id', checkbox.getAttribute('data-ville-depart-id'));
            setValue('#edit_date_arrivee', checkbox.getAttribute('data-date-arrivee'));
            setValue('#edit_heure_arrivee', checkbox.getAttribute('data-heure-arrivee'));
            setValue('#edit_ville_arrivee_id', checkbox.getAttribute('data-ville-arrivee-id'));
            setValue('#edit_nb_place', checkbox.getAttribute('data-nb-place'));
            setValue('#edit_prix_personne', checkbox.getAttribute('data-prix-personne'));

            var voitureId = checkbox.getAttribute('data-voiture-id') || '';
            var voitureSelect = form.querySelector('#edit_voiture_id');
            if (voitureSelect) {
                voitureSelect.value = voitureId;
            }

            // Préremplir les Etape(s) intermédiaire(s) dans la modale de modification
            var container = form.querySelector('#edit-etapes-container');
            if (container) {
                container.innerHTML = '';
                var trajetId = parseInt(checkbox.value || '0', 10);
                var villeDepartId = parseInt(checkbox.getAttribute('data-ville-depart-id') || '0', 10);
                var villeArriveeId = parseInt(checkbox.getAttribute('data-ville-arrivee-id') || '0', 10);
                var etapes = etapesParTrajet[trajetId] || [];

                etapes.forEach(function(etape) {
                    var villeId = parseInt(etape.ville_id || '0', 10);
                    if (villeId === 0 || villeId === villeDepartId || villeId === villeArriveeId) {
                        return;
                    }
                    addEtapeMesTrajetEdit(villeId, etape.heure || '');
                });
            }

            var modalInstance = bootstrap.Modal.getOrCreateInstance(editModal);
            modalInstance.show();
        });
    });

    const villesDataMesTrajets = <?= json_encode(array_map(function ($v) {
                                        return ['id' => (int)$v['ville_id'], 'nom' => $v['nom'], 'cp' => $v['code_postal']];
                                    }, $villes), JSON_UNESCAPED_UNICODE); ?>;

    function buildVilleOptionsMesTrajets() {
        return villesDataMesTrajets.map(v =>
            `<option value="${v.id}">${v.nom} (${v.cp})</option>`
        ).join('');
    }

    function addEtapeMesTrajet() {
        const container = document.getElementById('etapes-container-mes-trajets');
        if (!container) {
            return;
        }

        const div = document.createElement('div');
        div.className = 'row mb-2 align-items-center etape-row';
        div.innerHTML = `
            <div class="col-md-5 mb-2">
                <select name="etapes_ville_id[]" class="form-select">
                    <option value="">Ville de l'étape</option>
                    ${buildVilleOptionsMesTrajets()}
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <input type="time" name="etapes_heure[]" class="form-control" placeholder="Heure de passage">
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

    function addEtapeMesTrajetEdit(selectedVilleId = '', selectedHeure = '') {
        const container = document.getElementById('edit-etapes-container');
        if (!container) {
            return;
        }

        const div = document.createElement('div');
        div.className = 'row mb-2 align-items-center etape-row';
        div.innerHTML = `
            <div class="col-md-5 mb-2">
                <select name="edit_etapes_ville_id[]" class="form-select">
                    <option value="">Ville de l'étape</option>
                    ${buildVilleOptionsMesTrajets()}
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <input type="time" name="edit_etapes_heure[]" class="form-control" placeholder="Heure de passage">
            </div>
            <div class="col-md-3 mb-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100"
                        onclick="this.closest('.etape-row').remove()">
                    Supprimer
                </button>
            </div>
        `;
        container.appendChild(div);

        const select = div.querySelector('select[name="edit_etapes_ville_id[]"]');
        const input = div.querySelector('input[name="edit_etapes_heure[]"]');
        if (select && selectedVilleId !== '') {
            select.value = String(selectedVilleId);
        }
        if (input && selectedHeure !== '') {
            input.value = selectedHeure;
        }
    }
</script>
<?php require_once __DIR__ . "/../../templates/footer.php"; ?>