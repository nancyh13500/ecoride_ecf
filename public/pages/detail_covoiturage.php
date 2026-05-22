<?php
require_once __DIR__ . "/../../templates/header.php";
require_once __DIR__ . "/../../lib/pdo.php";
require_once __DIR__ . "/../../lib/session.php";

// Vérifier si l'utilisateur est connecté (optionnel pour voir les détails)
$user = null;
$isConnected = isUserConnected();
if ($isConnected) {
    $user = $_SESSION['user'];
}

$success_message = '';
$error_message = '';

// Déterminer l'ID du covoiturage (GET par défaut, POST lors de la réservation)
$covoiturage_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    $covoiturage_id = isset($_POST['covoiturage_id']) ? intval($_POST['covoiturage_id']) : $covoiturage_id;
}

if ($covoiturage_id <= 0) {
    header("Location: trajets.php");
    exit();
}

// Reprise de la recherche trajets.php (passée en query depuis les liens « Voir le détail »)
$trajets_retour_keys = ['depart', 'arrivee', 'date', 'etape', 'credit_min', 'note_min'];
$trajets_retour_q = [];
foreach ($trajets_retour_keys as $rk) {
    if (!isset($_GET[$rk]) || $_GET[$rk] === '') {
        continue;
    }
    $trajets_retour_q[$rk] = (string)$_GET[$rk];
}
$href_retour_trajets = 'trajets.php';
if ($trajets_retour_q !== []) {
    $href_retour_trajets .= '?' . http_build_query($trajets_retour_q);
}

// Traitement de la réservation (nécessite une connexion)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    if (!$isConnected || !$user) {
        header("Location: /login.php?redirect=detail_covoiturage.php&id=" . $covoiturage_id);
        exit();
    }
    verifyCSRFToken(); // vérification CSRF

    if (empty($error_message)) {
        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            $queryReservation = $pdo->prepare("
                SELECT covoiturage_id, nb_place, prix_personne, user_id, statut
                FROM covoiturage
                WHERE covoiturage_id = :id
                FOR UPDATE
            ");
            $queryReservation->execute(['id' => $covoiturage_id]);
            $covoiturageRow = $queryReservation->fetch(PDO::FETCH_ASSOC);

            if (!$covoiturageRow) {
                throw new Exception("Covoiturage introuvable.");
            }

            if ((int)$covoiturageRow['user_id'] === (int)$user['user_id']) {
                throw new Exception("Vous ne pouvez pas réserver votre propre covoiturage.");
            }

            if ((int)$covoiturageRow['statut'] !== 1) {
                throw new Exception("Ce covoiturage n'est plus disponible.");
            }

            if ((int)$covoiturageRow['nb_place'] <= 0) {
                throw new Exception("Plus de places disponibles pour ce covoiturage.");
            }

            $checkReservation = $pdo->prepare("
            SELECT reservation_id
            FROM reservations
            WHERE user_id = :user_id AND covoiturage_id = :covoiturage_id AND statut != 'annulée'
            LIMIT 1
        ");
            $checkReservation->execute([
                'user_id' => $user['user_id'],
                'covoiturage_id' => $covoiturage_id,
            ]);

            if ($checkReservation->fetch()) {
                throw new Exception("Vous avez déjà réservé une place pour ce covoiturage.");
            }

            $nb_places_reservees = 1;
            $prix_total = (float)$covoiturageRow['prix_personne'] * $nb_places_reservees;

            $insertReservation = $pdo->prepare("
            INSERT INTO reservations (user_id, covoiturage_id, nb_places_reservees, prix_total, statut)
            VALUES (:user_id, :covoiturage_id, :nb_places_reservees, :prix_total, 'En attente')
        ");
            $insertReservation->execute([
                'user_id' => $user['user_id'],
                'covoiturage_id' => $covoiturage_id,
                'nb_places_reservees' => $nb_places_reservees,
                'prix_total' => $prix_total,
            ]);

            $updateCovoiturage = $pdo->prepare("
            UPDATE covoiturage
            SET nb_place = nb_place - :nb_places
            WHERE covoiturage_id = :covoiturage_id AND nb_place >= :nb_places_check
        ");
            $updateCovoiturage->execute([
                'nb_places' => $nb_places_reservees,
                'nb_places_check' => $nb_places_reservees,
                'covoiturage_id' => $covoiturage_id,
            ]);

            if ($updateCovoiturage->rowCount() === 0) {
                throw new Exception("La réservation n'a pas pu être confirmée. Veuillez réessayer.");
            }

            $pdo->commit();

            header("Location: mes_reservations.php?success=1");
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = $e->getMessage();
        }
    }
}

// Récupérer les détails du covoiturage
$covoiturage = null;
$etapes_intermediaires = [];

try {
    $query = $pdo->prepare("
        SELECT c.*,
               u.nom, u.prenom, u.email, u.telephone, u.pseudo,
               v.modele, v.immatriculation, v.couleur, v.energie,
               m.libelle AS marque_libelle,
               en.libelle AS energie_libelle,
               vd.latitude AS map_lat_depart, vd.longitude AS map_lon_depart,
               va.latitude AS map_lat_arrivee, va.longitude AS map_lon_arrivee
        FROM covoiturage c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
        LEFT JOIN marque m ON v.marque_id = m.marque_id
        LEFT JOIN energie en ON v.energie_id = en.energie_id
        LEFT JOIN ville vd ON vd.ville_id = c.ville_depart_id
        LEFT JOIN ville va ON va.ville_id = c.ville_arrivee_id
        WHERE c.covoiturage_id = :id
    ");
    $query->execute(['id' => $covoiturage_id]);
    $covoiturage = $query->fetch(PDO::FETCH_ASSOC);

    if (!$covoiturage) {
        $error_message = "Covoiturage introuvable.";
    }
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des détails : " . $e->getMessage();
}

// Récupérer les Etape(s) du covoiturage
try {
    $query_etapes = $pdo->prepare("
        SELECT v.nom
        FROM etape e
        JOIN ville v ON v.ville_id = e.ville_id
        WHERE e.covoiturage_id = :id
        ORDER BY e.ordre ASC
    ");
    $query_etapes->execute(['id' => $covoiturage_id]);
    $etapes_trajet = $query_etapes->fetchAll(PDO::FETCH_COLUMN);

    // Garder uniquement les Etape(s) intermédiaire(s)
    $depart_nom = strtolower(trim((string)($covoiturage['lieu_depart'] ?? '')));
    $arrivee_nom = strtolower(trim((string)($covoiturage['lieu_arrivee'] ?? '')));
    $etapes_intermediaires = array_values(array_filter($etapes_trajet, static function ($nom) use ($depart_nom, $arrivee_nom) {
        $nom_normalise = strtolower(trim((string)$nom));
        return $nom_normalise !== '' && $nom_normalise !== $depart_nom && $nom_normalise !== $arrivee_nom;
    }));
} catch (PDOException $e) {
    $etapes_intermediaires = [];
}

// Si covoiturage introuvable, rediriger
if (!$covoiturage) {
    header("Location: trajets.php?error=notfound");
    exit();
}

// Points GPS pour la carte du trajet (étapes en ordre, sinon départ → arrivée)
$map_points = [];
$lookupVilleCoords = static function (PDO $pdoConn, string $nom): ?array {
    $nom = trim($nom);
    if ($nom === '') {
        return null;
    }
    try {
        $st = $pdoConn->prepare("
            SELECT latitude, longitude
            FROM ville
            WHERE LOWER(TRIM(nom)) = LOWER(:nom)
              AND latitude IS NOT NULL AND longitude IS NOT NULL
            LIMIT 1
        ");
        $st->execute(['nom' => $nom]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return [(float) $row['latitude'], (float) $row['longitude']];
    } catch (PDOException $e) {
        return null;
    }
};

try {
    $qEtapesMap = $pdo->prepare("
        SELECT v.nom, v.latitude, v.longitude
        FROM etape e
        JOIN ville v ON v.ville_id = e.ville_id
        WHERE e.covoiturage_id = :id
          AND v.latitude IS NOT NULL AND v.longitude IS NOT NULL
        ORDER BY e.ordre ASC
    ");
    $qEtapesMap->execute(['id' => $covoiturage_id]);
    while ($row = $qEtapesMap->fetch(PDO::FETCH_ASSOC)) {
        $map_points[] = [
            'lat' => (float) $row['latitude'],
            'lng' => (float) $row['longitude'],
            'label' => (string) $row['nom'],
        ];
    }
} catch (PDOException $e) {
    $map_points = [];
}

if (count($map_points) < 2) {
    $map_points = [];
    $ld = isset($covoiturage['map_lat_depart'], $covoiturage['map_lon_depart'])
        && $covoiturage['map_lat_depart'] !== null && $covoiturage['map_lon_depart'] !== null
        ? [(float) $covoiturage['map_lat_depart'], (float) $covoiturage['map_lon_depart']]
        : $lookupVilleCoords($pdo, (string) ($covoiturage['lieu_depart'] ?? ''));
    $la = isset($covoiturage['map_lat_arrivee'], $covoiturage['map_lon_arrivee'])
        && $covoiturage['map_lat_arrivee'] !== null && $covoiturage['map_lon_arrivee'] !== null
        ? [(float) $covoiturage['map_lat_arrivee'], (float) $covoiturage['map_lon_arrivee']]
        : $lookupVilleCoords($pdo, (string) ($covoiturage['lieu_arrivee'] ?? ''));

    if ($ld !== null) {
        $map_points[] = [
            'lat' => $ld[0],
            'lng' => $ld[1],
            'label' => (string) ($covoiturage['lieu_depart'] ?? 'Départ'),
        ];
    }
    if ($la !== null) {
        $map_points[] = [
            'lat' => $la[0],
            'lng' => $la[1],
            'label' => (string) ($covoiturage['lieu_arrivee'] ?? 'Arrivée'),
        ];
    }
}

$map_show = count($map_points) >= 2;
$map_points_json = json_encode($map_points, JSON_UNESCAPED_UNICODE);
if ($map_points_json === false) {
    $map_points_json = '[]';
    $map_show = false;
}

// Calculer les badges pour les places
$nb_places = (int)$covoiturage['nb_place'];
$badge_class = 'badge-places badge-places--red';
if ($nb_places >= 3) {
    $badge_class = 'badge-places badge-places--green';
} elseif ($nb_places == 2) {
    $badge_class = 'badge-places badge-places--orange';
}

// Variables pour l'affichage des boutons (seulement si connecté)
$estMonCovoiturage = false;
$dejaReserve = false;
$peutReserver = false;

if ($isConnected && $user) {
    $estMonCovoiturage = (int)$covoiturage['user_id'] === (int)$user['user_id'];
    try {
        $checkReservationDisplay = $pdo->prepare("
            SELECT reservation_id
            FROM reservations
            WHERE user_id = :user_id AND covoiturage_id = :covoiturage_id AND statut != 'annulée'
            LIMIT 1
        ");
        $checkReservationDisplay->execute([
            'user_id' => $user['user_id'],
            'covoiturage_id' => $covoiturage_id,
        ]);
        $dejaReserve = (bool)$checkReservationDisplay->fetchColumn();
    } catch (PDOException $e) {
        // Si la table n'existe pas encore, on ignore l'erreur pour l'affichage
        $dejaReserve = false;
    }
    $peutReserver = !$estMonCovoiturage && !$dejaReserve && (int)$covoiturage['statut'] === 1 && $nb_places > 0;
}

// Variable pour savoir si on peut afficher le bouton réserver (même sans être connecté)
$peutAfficherBoutonReserver = (int)$covoiturage['statut'] === 1 && $nb_places > 0;
?>

<section class="hero w-100 px-4 py-5">
    <div class="w-100">
        <div class="mx-auto" style="max-width: 1200px;">
            <!-- Bouton retour -->
            <div class="mb-4">
                <a href="trajets.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Retour aux trajets
                </a>
            </div>

            <!-- Carte principale -->
            <div class="card rounded-3 shadow-sm border-0 w-100">
                <div class="card-header bg-dark text-white text-center py-3 mx-auto w-50 rounded mt-3 mb-3">
                    <h2 class="mb-0 fs-4">
                        <i class="bi bi-car-front me-2"></i>Détails du covoiturage
                    </h2>
                </div>
                <div class="card-body p-2 p-md-3">

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-3 justify-content-center">
                        <div class="col-12 col-xl-5">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h3 class="text-primary h5 mb-3">
                                        <i class="bi bi-geo-alt-fill me-2"></i>Informations du trajet
                                    </h3>
                                    <div class="row">
                                        <div class="col-md-6 col-lg-6 mb-3">
                                            <h6 class="text-muted mb-2"><i class="bi bi-geo-alt me-1"></i>Ville de départ</h6>
                                            <p class="fs-5 fw-bold"><?= htmlspecialchars($covoiturage['lieu_depart']) ?></p>
                                        </div>
                                        <div class="col-md-6 col-lg-6 mb-3">
                                            <h6 class="text-muted mb-2"><i class="bi bi-geo-alt me-1"></i>Ville d'arrivée</h6>
                                            <p class="fs-5 fw-bold"><?= htmlspecialchars($covoiturage['lieu_arrivee']) ?></p>
                                        </div>
                                        <div class="col-md-6 col-lg-6 mb-3">
                                            <h6 class="text-muted mb-2"><i class="bi bi-calendar-event me-1"></i>Date départ</h6>
                                            <p class="fs-5 mb-0"><?= date('d/m/Y', strtotime($covoiturage['date_depart'])) ?></p>
                                            <small class="fs-5">à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?></small>
                                        </div>
                                        <div class="col-md-6 col-lg-6 mb-3">
                                            <h6 class="text-muted mb-2"><i class="bi bi-calendar-check me-1"></i>Date arrivée</h6>
                                            <p class="fs-5 mb-0"><?= date('d/m/Y', strtotime($covoiturage['date_arrivee'])) ?></p>
                                            <small class="fs-5">à <?= date('H:i', strtotime($covoiturage['heure_arrivee'])) ?></small>
                                        </div>
                                        <div class="col-12 mb-1">
                                            <h6 class="text-muted mb-2"><i class="bi bi-signpost-2 me-1"></i>Étape(s) intermédiaire(s)</h6>
                                            <?php if (!empty($etapes_intermediaires)): ?>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <?php foreach ($etapes_intermediaires as $etape): ?>
                                                        <span class="fs-5 fw-bold text-dark"><?= htmlspecialchars($etape) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="mb-0 text-muted">Trajet direct, sans étape intermédiaire.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-5">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h3 class="text-primary h5 mb-3">
                                        <i class="bi bi-person-circle me-2"></i>Informations du conducteur
                                    </h3>
                                    <div class="row">
                                        <div class="col-md-6 col-lg-6 mb-3">
                                            <h6 class="text-muted mb-2">Nom complet</h6>
                                            <p class="fs-5"><?= htmlspecialchars(trim($covoiturage['prenom'] . ' ' . $covoiturage['nom'])) ?></p>
                                        </div>
                                        <?php if (!empty($covoiturage['pseudo'])): ?>
                                            <div class="col-md-6 col-lg-6 mb-3">
                                                <h6 class="text-muted mb-2">Pseudo</h6>
                                                <p class="fs-5"><?= htmlspecialchars($covoiturage['pseudo']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($covoiturage['email'])): ?>
                                            <div class="col-md-6 col-lg-6 mb-3">
                                                <h6 class="text-muted mb-2"><i class="bi bi-envelope me-1"></i>Email</h6>
                                                <p class="fs-5"><?= htmlspecialchars($covoiturage['email']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($covoiturage['telephone'])): ?>
                                            <div class="col-md-6 col-lg-6 mb-3">
                                                <h6 class="text-muted mb-2"><i class="bi bi-telephone me-1"></i>Téléphone</h6>
                                                <p class="fs-5"><?= htmlspecialchars($covoiturage['telephone']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 justify-content-center">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h3 class="text-primary h5 mb-3">
                                        <i class="bi bi-map me-2"></i>Carte du trajet
                                    </h3>
                                    <?php if ($map_show): ?>
                                        <div id="map-trajet" class="rounded border" style="height: 380px; min-height: 260px; z-index: 1;"></div>
                                        <p class="text-muted small mt-2 mb-0">
                                            Aperçu du parcours entre les villes enregistrées (ligne reliant les points&nbsp;; ce n’est pas un calcul d’itinéraire routier).
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">
                                            La carte ne peut pas s’afficher : coordonnées GPS introuvables pour ce trajet dans la base des villes.
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 justify-content-center">
                        <div class="col-12 col-xl-5">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h3 class="text-primary h5 mb-3">
                                        <i class="bi bi-car-front me-2"></i>Informations du véhicule
                                    </h3>
                                    <?php if (!empty($covoiturage['modele']) || !empty($covoiturage['marque_libelle'])): ?>
                                        <div class="row">
                                            <?php if (!empty($covoiturage['marque_libelle']) && !empty($covoiturage['modele'])): ?>
                                                <div class="col-md-6 col-lg-6 mb-3">
                                                    <h6 class="text-muted mb-2">Véhicule</h6>
                                                    <p class="fs-5"><?= htmlspecialchars($covoiturage['marque_libelle'] . ' ' . $covoiturage['modele']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($covoiturage['immatriculation'])): ?>
                                                <div class="col-md-6 col-lg-6 mb-3">
                                                    <h6 class="text-muted mb-2">Immatriculation</h6>
                                                    <p class="fs-5"><?= htmlspecialchars($covoiturage['immatriculation']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($covoiturage['couleur'])): ?>
                                                <div class="col-md-6 col-lg-6 mb-3">
                                                    <h6 class="text-muted mb-2">Couleur</h6>
                                                    <p class="fs-5"><?= htmlspecialchars($covoiturage['couleur']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($covoiturage['energie_libelle'])): ?>
                                                <div class="col-md-6 col-lg-6 mb-3">
                                                    <h6 class="text-muted mb-2">Énergie</h6>
                                                    <p class="fs-5"><?= htmlspecialchars($covoiturage['energie_libelle']) ?></p>
                                                </div>
                                            <?php elseif (!empty($covoiturage['energie'])): ?>
                                                <div class="col-md-6 col-lg-6 mb-3">
                                                    <h6 class="text-muted mb-2">Énergie</h6>
                                                    <p class="fs-5"><?= htmlspecialchars($covoiturage['energie']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">Informations véhicule non renseignées.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-5">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h3 class="text-primary h5 mb-3">
                                        <i class="bi bi-info-circle me-2"></i>Informations pratiques
                                    </h3>
                                    <div class="row">
                                        <div class="col-md-6 col-lg-6 mb-3">
                                            <h6 class="text-muted mb-2"><i class="bi bi-people me-1"></i>Places disponibles</h6>
                                            <p class="fs-5">
                                                <span class="badge <?= $badge_class ?> fs-6">
                                                    <?= $nb_places ?> place<?= $nb_places > 1 ? 's' : '' ?>
                                                </span>
                                                <?php if ($nb_places == 1): ?>
                                                    <small class="text-danger fw-bold d-block mt-2">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>Dernière place disponible !
                                                    </small>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6 col-lg-6 mb-3">
                                            <h6 class="text-muted mb-2"><i class="bi bi-coin me-1"></i>Crédits par personne</h6>
                                            <p class="fs-5 fw-bold text-dark"><?= number_format($covoiturage['prix_personne'], 0) ?> crédits</p>
                                        </div>
                                    </div>
                                    <?php
                                    $statut_libelle = '';
                                    $statut_class = '';
                                    switch ($covoiturage['statut']) {
                                        case 1:
                                            $statut_libelle = 'Disponible';
                                            $statut_class = 'bg-success';
                                            break;
                                        case 2:
                                            $statut_libelle = 'En attente';
                                            $statut_class = 'bg-warning text-dark';
                                            break;
                                        case 3:
                                            $statut_libelle = 'Terminé';
                                            $statut_class = 'bg-secondary';
                                            break;
                                        default:
                                            $statut_libelle = 'Inconnu';
                                            $statut_class = 'bg-dark';
                                    }
                                    ?>
                                    <div class="row">
                                        <div class="col-md-6 col-lg-6 mb-3">
                                            <h6 class="text-muted mb-2">Statut</h6>
                                            <p class="fs-5">
                                                <span class="badge <?= $statut_class ?>"><?= $statut_libelle ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="text-center mt-4">
                        <a href="<?= htmlspecialchars($href_retour_trajets, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary me-3">
                            <i class="bi bi-arrow-left me-2"></i>Retour
                        </a>
                        <?php if ($isConnected && $user): ?>
                            <!-- Utilisateur connecté -->
                            <?php if ($peutReserver): ?>
                                <form method="POST" action="" class="d-inline">
                                    <?php csrfField(); ?> <!-- ← ajout token CSRF -->
                                    <input type="hidden" name="covoiturage_id" value="<?= htmlspecialchars((string) $covoiturage_id) ?>">
                                    <button type="submit" name="reserver" class="btn btn-reserver btn-primary" onclick="return confirm('Êtes-vous sûr de vouloir réserver une place pour ce covoiturage ?');">
                                        <i class="bi bi-check-circle me-2"></i>Réserver une place
                                    </button>
                                </form>
                            <?php elseif ($dejaReserve): ?>
                                <button class="btn btn-success" disabled>
                                    <i class="bi bi-check-circle me-2"></i>Déjà réservé
                                </button>
                            <?php elseif ($estMonCovoiturage): ?>
                                <button class="btn btn-outline-secondary" disabled>
                                    <i class="bi bi-info-circle me-2"></i>Votre covoiturage
                                </button>
                            <?php elseif ((int)$covoiturage['statut'] !== 1): ?>
                                <button class="btn btn-outline-secondary" disabled>
                                    <i class="bi bi-slash-circle me-2"></i>Non disponible
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary" disabled>
                                    <i class="bi bi-dash-circle me-2"></i>Complet
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Utilisateur non connecté -->
                            <?php if ($peutAfficherBoutonReserver): ?>
                                <a href="/login.php?redirect=detail_covoiturage.php&id=<?= $covoiturage_id ?>" class="btn btn-primary">
                                    <i class="bi bi-person-plus me-2"></i>Réserver une place
                                </a>
                            <?php elseif ((int)$covoiturage['statut'] !== 1): ?>
                                <button class="btn btn-outline-secondary" disabled>
                                    <i class="bi bi-slash-circle me-2"></i>Non disponible
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary" disabled>
                                    <i class="bi bi-dash-circle me-2"></i>Complet
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($map_show): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        (function() {
            var pts = <?= $map_points_json ?>;
            var el = document.getElementById('map-trajet');
            if (!el || !pts || pts.length < 2 || typeof L === 'undefined') {
                return;
            }
            var latlngs = pts.map(function(p) {
                return [p.lat, p.lng];
            });
            var map = L.map(el, {
                scrollWheelZoom: false
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            var bounds = L.latLngBounds(latlngs);
            var sw = bounds.getSouthWest();
            var ne = bounds.getNorthEast();
            if (sw.lat === ne.lat && sw.lng === ne.lng) {
                map.setView(latlngs[0], 11);
            } else {
                map.fitBounds(bounds, {
                    padding: [48, 48]
                });
            }

            L.polyline(latlngs, {
                color: '#0d6efd',
                weight: 4,
                opacity: 0.9
            }).addTo(map);
            pts.forEach(function(p, i) {
                var title = p.label || ('Point ' + (i + 1));
                L.marker([p.lat, p.lng]).addTo(map).bindPopup(title);
            });
        })();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . "/../../templates/footer.php";
?>