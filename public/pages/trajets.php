<?php
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../../lib/session.php";
require_once __DIR__ . "/../../lib/pdo.php";
require_once __DIR__ . "/../../lib/mongodb.php";

/**
 * Moyennes des notes MongoDB par chauffeur (avis validés liés à un covoiturage).
 *
 * @return array<int,float> user_id chauffeur => moyenne
 */
function trajets_moyennes_notes_par_chauffeur(PDO $pdo): array
{
    try {
        $coll = getAvisCollection();
        if ($coll === null) {
            return [];
        }

        $cursor = $coll->find(
            [
                'statut' => 'valide',
                '$or' => [
                    ['chauffeur_id' => ['$exists' => true, '$ne' => null]],
                    ['covoiturage_id' => ['$exists' => true, '$ne' => null]],
                ],
            ],
            [
                'projection' => [
                    'note' => 1,
                    'covoiturage_id' => 1,
                    'chauffeur_id' => 1,
                ],
            ]
        );

        $notesParChauffeurDirect = [];
        $notesParCovoiturage = [];
        foreach ($cursor as $doc) {
            $note = (float) ($doc['note'] ?? 0);
            if ($note <= 0) {
                continue;
            }

            $chauffeurId = (int) ($doc['chauffeur_id'] ?? 0);
            if ($chauffeurId > 0) {
                if (!isset($notesParChauffeurDirect[$chauffeurId])) {
                    $notesParChauffeurDirect[$chauffeurId] = [];
                }
                $notesParChauffeurDirect[$chauffeurId][] = $note;
                continue;
            }

            $cid = (int) ($doc['covoiturage_id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            if (!isset($notesParCovoiturage[$cid])) {
                $notesParCovoiturage[$cid] = [];
            }
            $notesParCovoiturage[$cid][] = $note;
        }

        $notesParChauffeur = $notesParChauffeurDirect;

        if ($notesParCovoiturage !== []) {
            $covoiturageIds = array_keys($notesParCovoiturage);
            $placeholders = implode(',', array_fill(0, count($covoiturageIds), '?'));
            $stmt = $pdo->prepare("
                SELECT covoiturage_id, user_id
                FROM covoiturage
                WHERE covoiturage_id IN ({$placeholders})
            ");
            $stmt->execute($covoiturageIds);

            $chauffeurParCovoiturage = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $chauffeurParCovoiturage[(int) $row['covoiturage_id']] = (int) $row['user_id'];
            }

            foreach ($notesParCovoiturage as $cid => $notes) {
                $chauffeurId = $chauffeurParCovoiturage[$cid] ?? 0;
                if ($chauffeurId <= 0) {
                    continue;
                }
                if (!isset($notesParChauffeur[$chauffeurId])) {
                    $notesParChauffeur[$chauffeurId] = [];
                }
                array_push($notesParChauffeur[$chauffeurId], ...$notes);
            }
        }

        $out = [];
        foreach ($notesParChauffeur as $chauffeurId => $notes) {
            if ($notes === []) {
                continue;
            }
            $out[$chauffeurId] = round(array_sum($notes) / count($notes), 2);
        }

        return $out;
    } catch (Throwable $e) {
        error_log('trajets_moyennes_notes_par_chauffeur: ' . $e->getMessage());

        return [];
    }
}

/** energie_id « Electrique » dans la table energie (ecoride.sql). */
const TRAJETS_ENERGIE_ELECTRIQUE_ID = 3;

/**
 * IDs energie considérés comme électriques (libellé contenant « lectrique »).
 *
 * @return int[]
 */
function trajets_ids_energie_electrique(PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $stmt = $pdo->query("SELECT energie_id FROM energie WHERE LOWER(TRIM(libelle)) LIKE '%lectrique%'");
        $cache = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException) {
        $cache = [];
    }
    if ($cache === []) {
        $cache = [TRAJETS_ENERGIE_ELECTRIQUE_ID];
    }

    return $cache;
}

/**
 * Vérifie si le véhicule lié au trajet est électrique.
 */
function trajet_est_vehicule_electrique(array $row, PDO $pdo): bool
{
    if (in_array((int) ($row['energie_id'] ?? 0), trajets_ids_energie_electrique($pdo), true)) {
        return true;
    }

    $libelle = strtolower(trim((string) ($row['energie_libelle'] ?? $row['energie'] ?? '')));

    return $libelle !== '' && str_contains($libelle, 'lectrique');
}

/**
 * Condition SQL : voiture électrique (energie_id ou libellé).
 */
function trajets_sql_condition_voiture_electrique(PDO $pdo): string
{
    $ids = implode(',', trajets_ids_energie_electrique($pdo));

    return "(
        v.energie_id IN ({$ids})
        OR LOWER(COALESCE(e.libelle, v.energie, '')) LIKE '%lectrique%'
    )";
}

/**
 * Jointures SQL voiture + energie pour les trajets écologiques.
 */
function trajets_sql_voiture_electrique(): string
{
    return "
            INNER JOIN voiture v ON c.voiture_id = v.voiture_id
            LEFT JOIN energie e ON e.energie_id = v.energie_id
    ";
}

// Gérer le démarrage du trajet depuis cette page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_trajet_from_hero'])) {
    if (isUserConnected()) {
        $user = $_SESSION['user'];
        $trajet_id = intval($_POST['start_trajet_from_hero']);

        try {
            $query = $pdo->prepare("UPDATE covoiturage SET statut = 2 WHERE covoiturage_id = :id AND user_id = :user_id");
            $query->execute(['id' => $trajet_id, 'user_id' => $user['user_id']]);
            header("Location: mes_trajets.php?started=1");
            exit();
        } catch (PDOException $e) {
            $error_message = "Erreur lors du démarrage du trajet : " . $e->getMessage();
        }
    }
}

// Récupérer les villes disponibles depuis la base de données
$villes_depart = [];
$villes_arrivee = [];
$villes_etape = [];

try {
    // Récupérer les villes de départ
    $query_depart = $pdo->prepare("SELECT DISTINCT lieu_depart FROM covoiturage WHERE statut = 1 ORDER BY lieu_depart ASC");
    $query_depart->execute();
    $villes_depart = $query_depart->fetchAll(PDO::FETCH_COLUMN);

    // Récupérer les villes d'arrivée
    $query_arrivee = $pdo->prepare("SELECT DISTINCT lieu_arrivee FROM covoiturage WHERE statut = 1 ORDER BY lieu_arrivee ASC");
    $query_arrivee->execute();
    $villes_arrivee = $query_arrivee->fetchAll(PDO::FETCH_COLUMN);

    // Récupérer les villes d'étape depuis la table ville
    $query_etape = $pdo->prepare("SELECT DISTINCT nom FROM ville ORDER BY nom ASC");
    $query_etape->execute();
    $villes_etape = $query_etape->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // En cas d'erreur, on continue avec des listes vides
    $villes_depart = [];
    $villes_arrivee = [];
    $villes_etape = [];
}

// Traitement de la recherche depuis le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_trajet'])) {
    $depart = $_POST['depart'] ?? '';
    $arrivee = $_POST['arrivee'] ?? '';
    $date = $_POST['date'] ?? '';
    $etape = $_POST['etape'] ?? '';

    // Redirection vers la même page avec les paramètres de recherche
    $params = http_build_query([
        'depart' => $depart,
        'arrivee' => $arrivee,
        'date' => $date,
        'etape' => $etape
    ]);

    header("Location: trajets.php?$params");
    exit();
}

// Récupérer les paramètres de recherche depuis l'URL
$search_depart = $_GET['depart'] ?? '';
$search_arrivee = $_GET['arrivee'] ?? '';
$search_date = $_GET['date'] ?? '';
$search_etape = $_GET['etape'] ?? '';

$filter_credit_min = null;
if (isset($_GET['credit_min']) && $_GET['credit_min'] !== '') {
    $tmp = (float)$_GET['credit_min'];
    if ($tmp > 0) {
        $filter_credit_min = $tmp;
    }
}
$filter_note_min = null;
if (isset($_GET['note_min']) && $_GET['note_min'] !== '') {
    $n = (int)$_GET['note_min'];
    if ($n >= 1 && $n <= 5) {
        $filter_note_min = $n;
    }
}

/** Paramètres GET à repasser vers detail_covoiturage.php pour restaurer la recherche au « Retour » */
$trajets_retour_query = [];
if ($search_depart !== '') {
    $trajets_retour_query['depart'] = $search_depart;
}
if ($search_arrivee !== '') {
    $trajets_retour_query['arrivee'] = $search_arrivee;
}
if ($search_date !== '') {
    $trajets_retour_query['date'] = $search_date;
}
if ($search_etape !== '') {
    $trajets_retour_query['etape'] = $search_etape;
}
if ($filter_credit_min !== null) {
    $trajets_retour_query['credit_min'] = (string)$filter_credit_min;
}
if ($filter_note_min !== null) {
    $trajets_retour_query['note_min'] = (string)$filter_note_min;
}
$filter_eco = isset($_GET['eco']) && (string) $_GET['eco'] === '1';
if ($filter_eco) {
    $trajets_retour_query['eco'] = '1';
}

// Rechercher les covoiturages selon les critères de recherche, écologique ou filtres crédit/note
$covoiturages_recherche = [];
$has_search_criteria = !empty($search_depart) && !empty($search_arrivee);
$has_affinage_filters = ($filter_credit_min !== null || $filter_note_min !== null);
$show_unfiltered_results = $has_search_criteria || $filter_eco;
$show_results_section = $show_unfiltered_results || $has_affinage_filters;
$browse_par_filtres_only = $has_affinage_filters && !$show_unfiltered_results;

// Bouton Filtrer : actif si recherche en cours et/ou critères de filtre dans l’URL
$filter_form_submit_ok = $has_search_criteria
    || $filter_credit_min !== null
    || $filter_note_min !== null
    || $filter_eco;

$has_active_filters = $filter_eco || $has_affinage_filters;
$filtres_actifs_libelles = [];
if ($filter_eco) {
    $filtres_actifs_libelles[] = 'voitures électriques et trajets associés';
}
if ($filter_credit_min !== null) {
    $filtres_actifs_libelles[] = 'crédit minimum : ' . number_format($filter_credit_min, 0, ',', ' ') . ' C';
}
if ($filter_note_min !== null) {
    $filtres_actifs_libelles[] = 'note minimale chauffeur : ' . $filter_note_min . ' étoile(s) et plus';
}

if ($show_results_section) {
    try {
        $join_voiture = $filter_eco
            ? trajets_sql_voiture_electrique()
            : "
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            LEFT JOIN energie e ON e.energie_id = v.energie_id
        ";

        $query_sql = "
            SELECT c.*, u.nom, u.prenom, v.modele, v.energie, v.energie_id,
                   m.libelle AS marque_libelle, e.libelle AS energie_libelle
            FROM covoiturage c
            LEFT JOIN user u ON c.user_id = u.user_id
            {$join_voiture}
            LEFT JOIN marque m ON v.marque_id = m.marque_id
            WHERE c.statut = 1 AND c.nb_place > 0
        ";

        if ($filter_eco) {
            $query_sql .= ' AND ' . trajets_sql_condition_voiture_electrique($pdo);
            // Trajets récents encore publiés (évite d'exclure un Tesla daté de la veille)
            $query_sql .= ' AND c.date_depart >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
        } else {
            $query_sql .= ' AND c.date_depart >= CURDATE()';
        }

        $params = [];

        if ($has_search_criteria) {
            $query_sql .= "
                AND c.lieu_depart LIKE :depart
                AND c.lieu_arrivee LIKE :arrivee
            ";
            $params['depart'] = '%' . $search_depart . '%';
            $params['arrivee'] = '%' . $search_arrivee . '%';
        }

        // Filtre optionnel sur une ville d'étape (départ/arrivée compris via table etape)
        if ($has_search_criteria && !empty($search_etape)) {
            $query_sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM etape e
                    JOIN ville v_etape ON v_etape.ville_id = e.ville_id
                    WHERE e.covoiturage_id = c.covoiturage_id
                    AND v_etape.nom LIKE :etape
                )
            ";
            $params['etape'] = '%' . $search_etape . '%';
        }

        if ($has_search_criteria && !empty($search_date)) {
            $query_sql .= " AND c.date_depart = :date_search";
            $params['date_search'] = $search_date;
        }

        if (!$has_search_criteria && ($filter_eco || $has_affinage_filters)) {
            $query_sql .= ' ORDER BY c.date_depart ASC, c.heure_depart ASC';
        }

        $query_search = $pdo->prepare($query_sql);
        $query_search->execute($params);

        $covoiturages_recherche = $query_search->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('trajets.php recherche: ' . $e->getMessage());
        $covoiturages_recherche = [];
    }
}

/**
 * Résultats après application des filtres crédit / note (sous-ensemble des résultats de recherche).
 * Le filtre écologique s’applique déjà sur $covoiturages_recherche (requête SQL).
 */
$covoiturages_filtres = [];
if ($show_results_section && $has_affinage_filters && !empty($covoiturages_recherche)) {
    $covoiturages_filtres = $covoiturages_recherche;
    if ($filter_credit_min !== null) {
        $seuil_credit = (float)$filter_credit_min;
        $covoiturages_filtres = array_values(array_filter(
            $covoiturages_filtres,
            static function (array $row) use ($seuil_credit): bool {
                return (float)($row['prix_personne'] ?? 0) >= $seuil_credit - 1e-9;
            }
        ));
    }
    if ($filter_note_min !== null && $covoiturages_filtres !== []) {
        $notes_par_chauffeur = trajets_moyennes_notes_par_chauffeur($pdo);
        $seuil = (float) $filter_note_min;
        $covoiturages_filtres = array_values(array_filter(
            $covoiturages_filtres,
            static function (array $row) use ($notes_par_chauffeur, $seuil): bool {
                $chauffeurId = (int) ($row['user_id'] ?? 0);
                if ($chauffeurId <= 0) {
                    return false;
                }
                if (!isset($notes_par_chauffeur[$chauffeurId])) {
                    return false;
                }

                return $notes_par_chauffeur[$chauffeurId] >= $seuil - 1e-9;
            }
        ));
    }
}

// Récupérer un covoiturage disponible pour l'affichage dans la hero (pour tous les utilisateurs)
$covoiturage_hero = null;
try {
    $query_hero = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, v.modele, m.libelle AS marque_libelle
        FROM covoiturage c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
        LEFT JOIN marque m ON v.marque_id = m.marque_id
        WHERE c.statut = 1 AND c.nb_place > 0 AND c.date_depart >= CURDATE()
        ORDER BY c.date_depart ASC, c.heure_depart ASC
        LIMIT 1
    ");
    $query_hero->execute();
    $covoiturage_hero = $query_hero->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En cas d'erreur, on continue sans afficher de covoiturage
    $covoiturage_hero = null;
}

// Récupérer les trajets en attente pour la section suggestion
$covoiturages_suggestion = [];
$debug_suggestion = [];
$etapes_by_covoiturage = [];

try {
    // Debug: Vérifier tous les statuts disponibles
    $debug_query = $pdo->prepare("SELECT statut, COUNT(*) as count FROM covoiturage GROUP BY statut");
    $debug_query->execute();
    $debug_suggestion = $debug_query->fetchAll(PDO::FETCH_ASSOC);

    // Requête principale pour les suggestions
    $query_suggestion = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, v.modele, m.libelle AS marque_libelle
        FROM covoiturage c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
        LEFT JOIN marque m ON v.marque_id = m.marque_id
        WHERE c.statut = 2 AND c.date_depart >= CURDATE()
        ORDER BY c.date_depart ASC, c.heure_depart ASC
        LIMIT 6
    ");
    $query_suggestion->execute();
    $covoiturages_suggestion = $query_suggestion->fetchAll(PDO::FETCH_ASSOC);

    // Si pas de trajets en attente, récupérer des trajets disponibles pour test
    if (empty($covoiturages_suggestion)) {
        $query_test = $pdo->prepare("
            SELECT c.*, u.nom, u.prenom, v.modele, m.libelle AS marque_libelle
            FROM covoiturage c
            LEFT JOIN user u ON c.user_id = u.user_id
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            LEFT JOIN marque m ON v.marque_id = m.marque_id
            WHERE c.statut = 1 AND c.date_depart >= CURDATE()
            ORDER BY c.date_depart ASC, c.heure_depart ASC
            LIMIT 3
        ");
        $query_test->execute();
        $covoiturages_suggestion = $query_test->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // En cas d'erreur, on continue avec une liste vide
    $covoiturages_suggestion = [];
    $debug_suggestion = [];
}

// Récupérer les étapes programmées pour les trajets affichés (résultats + suggestions)
try {
    $covoiturage_ids = [];
    foreach ($covoiturages_recherche as $trajet) {
        if (!empty($trajet['covoiturage_id'])) {
            $covoiturage_ids[] = (int)$trajet['covoiturage_id'];
        }
    }
    foreach ($covoiturages_suggestion as $trajet) {
        if (!empty($trajet['covoiturage_id'])) {
            $covoiturage_ids[] = (int)$trajet['covoiturage_id'];
        }
    }
    $covoiturage_ids = array_values(array_unique($covoiturage_ids));

    if (!empty($covoiturage_ids)) {
        $placeholders = implode(',', array_fill(0, count($covoiturage_ids), '?'));
        $query_etapes = $pdo->prepare("
            SELECT e.covoiturage_id, e.ordre, v.nom
            FROM etape e
            JOIN ville v ON v.ville_id = e.ville_id
            WHERE e.covoiturage_id IN ($placeholders)
            ORDER BY e.covoiturage_id ASC, e.ordre ASC
        ");
        $query_etapes->execute($covoiturage_ids);
        $etapes = $query_etapes->fetchAll(PDO::FETCH_ASSOC);

        foreach ($etapes as $etape) {
            $cid = (int)$etape['covoiturage_id'];
            if (!isset($etapes_by_covoiturage[$cid])) {
                $etapes_by_covoiturage[$cid] = [];
            }
            $etapes_by_covoiturage[$cid][] = $etape['nom'];
        }
    }
} catch (PDOException $e) {
    $etapes_by_covoiturage = [];
}

$pageTitle = 'Trajets';
require_once __DIR__ . "/../../templates/header.php";

?>

<!--section search -->
<section class="hero">
    <div class="background-img"></div>
    <div class="content px-4 py-5 my-5 text-center">
        <h1 class="fw-bold">Trouvez un covoiturage</h1>
        <p class="lead mb-4">La solution accessible et durable pour tous.</p>
        <div class="col-lg-6 mx-auto">
            <form method="POST" action="" aria-labelledby="search-form-title-trajets">
                <h2 id="search-form-title-trajets" class="visually-hidden">Rechercher un covoiturage</h2>
                <div class="search-bar row">
                    <div class="search-field col-md-4">
                        <label for="trajets-depart" class="form-label visually-hidden">Ville de départ</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" aria-hidden="true"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                            <input type="text" id="trajets-depart" name="depart" class="form-control border-start-0 text-center" placeholder="Ville de départ" list="villes-depart" value="<?= htmlspecialchars($search_depart, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" required>
                            <datalist id="villes-depart">
                                <?php foreach ($villes_depart as $ville): ?>
                                    <option value="<?= htmlspecialchars($ville) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="search-field col-md-4">
                        <label for="trajets-arrivee" class="form-label visually-hidden">Ville d'arrivée</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" aria-hidden="true"><i class="bi bi-geo-alt text-primary"></i></span>
                            <input type="text" id="trajets-arrivee" name="arrivee" class="form-control border-start-0 text-center" placeholder="Ville d'arrivée" list="villes-arrivee" value="<?= htmlspecialchars($search_arrivee, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" required>
                            <datalist id="villes-arrivee">
                                <?php foreach ($villes_arrivee as $ville): ?>
                                    <option value="<?= htmlspecialchars($ville) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="search-field col-md-4">
                        <label for="trajets-date" class="form-label visually-hidden">Date du trajet</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" aria-hidden="true"><i class="bi bi-calendar text-primary"></i></span>
                            <input type="date" id="trajets-date" name="date" class="form-control border-start-0 text-center" value="<?= htmlspecialchars($search_date, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <button type="submit" name="search_trajet" class="btn btn-primary w-50">
                        Lancer la recherche
                        <i class="bi bi-search ms-2" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
<!--end section search -->

<section id="results" class="results bg-light py-5">
    <div class="container">
        <div class="result-header text-center mb-5">
            <div class="bg-dark text-white p-4 rounded-3">
                <?php if (!empty($search_depart) && !empty($search_arrivee)): ?>
                    <h2>Résultats pour : <?= htmlspecialchars($search_depart) ?> → <?= htmlspecialchars($search_arrivee) ?></h2>
                    <?php if (!empty($search_date)): ?>
                        <p class="mb-0">Date : <?= date('d/m/Y', strtotime($search_date)) ?></p>
                    <?php endif; ?>
                <?php elseif ($filter_eco): ?>
                    <h2>Trajets en voiture électrique</h2>
                    <p class="mb-0">Covoiturages proposés avec des véhicules électriques</p>
                <?php elseif ($browse_par_filtres_only): ?>
                    <h2>Trajets filtrés</h2>
                    <p class="mb-0">Selon vos critères de crédit et/ou de note chauffeur</p>
                <?php else: ?>
                    <h2>Découvrez les trajets disponibles</h2>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filters : GET pour conserver la recherche + credit_min / note_min -->
        <div class="filters mb-4">
            <form method="get" action="trajets.php" id="formFiltresTrajets"
                data-has-search="<?= $has_search_criteria ? '1' : '0' ?>">
                <?php if ($has_search_criteria): ?>
                    <input type="hidden" name="depart" value="<?= htmlspecialchars($search_depart, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="arrivee" value="<?= htmlspecialchars($search_arrivee, ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($search_date !== ''): ?>
                        <input type="hidden" name="date" value="<?= htmlspecialchars($search_date, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <?php if ($search_etape !== ''): ?>
                        <input type="hidden" name="etape" value="<?= htmlspecialchars($search_etape, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-center small mb-3">
                        Cochez <strong>voyage écologique</strong> pour voir tous les trajets en véhicule électrique, ou indiquez un tarif / une note, ou lancez une recherche par départ et arrivée.
                    </p>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-2 text-center">
                        <div class="form-check d-flex flex-column justify-content-center align-items-center">
                            <label class="form-check-label-eco mb-2" for="ecoTrip">Voyage écologique</label>
                            <input class="form-check-input mt-3 border-dark" type="checkbox"
                                name="eco" value="1" id="ecoTrip"
                                <?= $filter_eco ? 'checked' : '' ?>
                                aria-describedby="ecoTripHelp">
                            <small id="ecoTripHelp" class="text-dark mt-1">Véhicules électriques</small>
                        </div>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="form-label credit-min" for="credit_min">Crédit minimum (C)</label>
                        <input type="number" name="credit_min" id="credit_min" min="0" step="1"
                            class="form-control filter-price" placeholder="Crédit min"
                            value="<?= $filter_credit_min !== null ? htmlspecialchars((string)$filter_credit_min, ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                    <div class="col-md-3 text-center">
                        <label class="form-label note" for="note_min">Note minimale chauffeur</label>
                        <select name="note_min" id="note_min" class="form-select" aria-describedby="noteMinHelp">
                            <option value="" <?= $filter_note_min === null ? 'selected' : '' ?>>Toutes les notes</option>
                            <option value="5" <?= $filter_note_min === 5 ? 'selected' : '' ?>>5 étoiles</option>
                            <option value="4" <?= $filter_note_min === 4 ? 'selected' : '' ?>>4 étoiles et plus</option>
                            <option value="3" <?= $filter_note_min === 3 ? 'selected' : '' ?>>3 étoiles et plus</option>
                        </select>
                        <small id="noteMinHelp" class="text-muted d-block mt-1">Moyenne des avis sur le conducteur</small>
                    </div>
                    <div class="col-md-3 d-flex justify-content-center align-items-center">
                        <p id="filtre-aide" class="visually-hidden">Saisissez un tarif minimum, choisissez une note, cochez voyage écologique, ou effectuez une recherche par départ et arrivée pour activer le filtre.</p>
                        <button type="submit" id="btnFiltrerTrajets" class="btn btn-filtre text-dark btn-secondary w-50"
                            aria-describedby="filtre-aide"
                            <?= !$filter_form_submit_ok ? ' disabled' : '' ?>>Filtrer</button>
                    </div>
                </div>
            </form>
            <script>
                (function() {
                    var form = document.getElementById('formFiltresTrajets');
                    var btn = document.getElementById('btnFiltrerTrajets');
                    var credit = document.getElementById('credit_min');
                    var note = document.getElementById('note_min');
                    var eco = document.getElementById('ecoTrip');
                    if (!form || !btn || !credit || !note || !eco) return;
                    var hasSearch = form.getAttribute('data-has-search') === '1';

                    function sync() {
                        var c = parseFloat(String(credit.value).replace(',', '.'));
                        var creditOk = String(credit.value).trim() !== '' && !isNaN(c) && c > 0;
                        var noteOk = String(note.value).trim() !== '';
                        var ecoOk = eco.checked;
                        var ok = hasSearch || creditOk || noteOk || ecoOk;
                        btn.disabled = !ok;
                        if (!ok) {
                            btn.setAttribute('aria-disabled', 'true');
                        } else {
                            btn.removeAttribute('aria-disabled');
                        }
                    }
                    credit.addEventListener('input', sync);
                    note.addEventListener('change', sync);
                    eco.addEventListener('change', sync);
                    sync();
                })();
            </script>
        </div>

        <!-- Résultats de recherche (indépendants des filtres crédit / note) -->
        <?php if ($show_results_section): ?>
            <?php if ($show_unfiltered_results): ?>
            <section id="section-resultats-recherche" class="search-results-trajets mb-5" aria-labelledby="titre-resultats-recherche">
                <header class="bg-white border rounded-3 shadow-sm px-4 py-3 mb-4">
                    <h3 id="titre-resultats-recherche" class="h5 mb-2 text-center text-dark">
                        <i class="bi bi-<?= $has_search_criteria ? 'search' : 'lightning-charge' ?> text-primary me-2" aria-hidden="true"></i>
                        <?= $has_search_criteria ? 'Résultats de recherche' : 'Trajets en voiture électrique' ?>
                    </h3>
                    <p class="text-center small mb-0 text-muted">
                        <?php if ($has_search_criteria): ?>
                            Selon départ, arrivée<?= !empty($search_date) ? ', date' : '' ?><?= !empty($search_etape) ? ', étape' : '' ?><?= $filter_eco ? ' — uniquement les trajets en voiture électrique' : '' ?> — sans critère de crédit ni de note.
                        <?php else: ?>
                            Covoiturages à venir proposés avec une voiture électrique enregistrée sur la plateforme.
                        <?php endif; ?>
                    </p>
                    <?php if ($filter_eco): ?>
                        <p class="text-center small mt-2 mb-0">
                            <span class="badge bg-success"><i class="bi bi-lightning-charge me-1"></i>Voyage écologique actif</span>
                        </p>
                    <?php endif; ?>
                    <?php if ($has_search_criteria): ?>
                        <p class="text-center small mt-2 mb-0">
                            <strong><?= htmlspecialchars($search_depart) ?></strong>
                            → <strong><?= htmlspecialchars($search_arrivee) ?></strong>
                            <?php if (!empty($search_date)): ?>
                                · <?= date('d/m/Y', strtotime($search_date)) ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </header>

                <h4 class="text-center mb-3">
                    <i class="bi bi-check2-square text-success me-2"></i>
                    <?= count($covoiturages_recherche) ?> résultat<?= count($covoiturages_recherche) !== 1 ? 's' : '' ?>
                </h4>
                <?php if (!empty($covoiturages_recherche)): ?>
                    <div class="search-results-row row row-cols-1 row-cols-lg-3 g-3 pb-3 px-1 mx-0" role="list">
                        <?php
                        foreach ($covoiturages_recherche as $covoiturage) {
                            $col_class = 'col search-result-card';
                            $card_extra_class = '';
                            require __DIR__ . '/carte_trajet_resultat.php';
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <div class="alert bg-dark text-white text-center" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Aucun trajet trouvé</strong><br>
                        <?php if ($has_search_criteria): ?>
                            Aucun covoiturage ne correspond à votre recherche pour le trajet <strong><?= htmlspecialchars($search_depart) ?> → <?= htmlspecialchars($search_arrivee) ?></strong>
                            <?php if (!empty($search_date)): ?>
                                le <?= date('d/m/Y', strtotime($search_date)) ?>
                            <?php endif; ?>
                            <?php if ($filter_eco): ?>
                                <br>Aucun véhicule électrique disponible pour ce trajet.
                            <?php endif; ?>
                        <?php else: ?>
                            Aucun covoiturage en voiture électrique n’est disponible pour le moment.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <!-- Filtres : sous-ensemble des résultats de recherche uniquement -->
            <?php if ($has_affinage_filters): ?>
            <section id="section-resultats-filtres" class="filter-search-results mb-5" aria-labelledby="titre-resultats-filtres">
                <header class="bg-white border rounded-3 shadow-sm px-4 py-3 mb-4">
                    <h3 id="titre-resultats-filtres" class="h5 mb-2 text-center text-dark">
                        <i class="bi bi-funnel-fill text-primary me-2" aria-hidden="true"></i>
                        <?= $browse_par_filtres_only ? 'Résultats filtrés' : 'Affinage par filtres' ?>
                    </h3>
                    <p class="text-center small mb-0 text-muted">
                        <?php if ($browse_par_filtres_only): ?>
                            Trajets disponibles filtrés selon la <strong>note moyenne du chauffeur</strong> et/ou le <strong>crédit minimum</strong>.
                        <?php else: ?>
                            Les critères <strong>crédit minimum</strong> et <strong>note minimale chauffeur</strong> (formulaire ci-dessus) affinent la liste ci-dessous.
                            <?php if ($filter_eco): ?>
                                Le <strong>voyage écologique</strong> est déjà appliqué sur les résultats de recherche.
                            <?php endif; ?>
                        <?php endif; ?>
                    </p>
                </header>

                <?php if (empty($covoiturages_filtres)): ?>
                    <div class="alert alert-warning text-center mb-0" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php if ($browse_par_filtres_only): ?>
                            Aucun trajet disponible ne correspond à ces critères (note moyenne du chauffeur ou crédit minimum).
                        <?php else: ?>
                            Aucun trajet des résultats de recherche ne correspond à ces filtres.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center mb-2 small">
                        <span class="badge bg-dark-subtle text-dark border">
                            <i class="bi bi-sliders me-1"></i>Filtres actifs
                        </span>
                        <?= htmlspecialchars(implode(' · ', $filtres_actifs_libelles)) ?>
                    </p>
                    <h4 class="text-center mb-3">
                        <i class="bi bi-funnel me-2 text-primary"></i>
                        <?= count($covoiturages_filtres) ?> trajet<?= count($covoiturages_filtres) !== 1 ? 's' : '' ?> après filtrage
                    </h4>
                    <div class="filter-results-row row row-cols-1 row-cols-lg-3 g-3 pb-3 px-1 mx-0" role="list">
                        <?php
                        foreach ($covoiturages_filtres as $covoiturage) {
                            $col_class = 'col filter-result-card';
                            $card_extra_class = 'filter-results';
                            require __DIR__ . '/carte_trajet_resultat.php';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Section Suggestions - Trajets en attente (après les résultats) -->
        <?php if (!empty($covoiturages_suggestion)): ?>
            <div class="suggestions-section border-top pt-5 mt-4">
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="text-center mb-4">
                            <i class="bi bi-lightbulb text-warning me-2"></i>
                            Suggestions de trajets
                        </h3>
                        <p class="text-center text-muted mb-4">
                            <?php
                            // Vérifier si on a des vrais trajets en attente ou des trajets de test
                            $has_real_pending = false;
                            foreach ($covoiturages_suggestion as $covoiturage) {
                                if ($covoiturage['statut'] == 2) {
                                    $has_real_pending = true;
                                    break;
                                }
                            }
                            if ($has_real_pending) {
                                echo "Découvrez ces trajets en attente qui pourraient vous intéresser";
                            } else {
                                echo "Découvrez ces trajets disponibles qui pourraient vous intéresser";
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="row">
                    <?php foreach ($covoiturages_suggestion as $covoiturage): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 suggestion-card border-light">
                                <div class="card-header bg-dark text-white text-center">
                                    <h6 class="mb-0">
                                        <?php if ($covoiturage['statut'] == 2): ?>
                                            <i class="bi bi-clock-history me-2"></i>Trajet en attente
                                        <?php else: ?>
                                            <i class="bi bi-car-front me-2"></i>Trajet disponible
                                        <?php endif; ?>
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <h6 class="text-primary mb-2">
                                        <i class="bi bi-geo-alt-fill me-1"></i>Trajet
                                    </h6>
                                    <p class="mb-2">
                                        <strong><?= htmlspecialchars($covoiturage['lieu_depart']) ?></strong>
                                        → <strong><?= htmlspecialchars($covoiturage['lieu_arrivee']) ?></strong>
                                    </p>

                                    <h6 class="text-primary mb-2">
                                        <i class="bi bi-calendar-event me-1"></i>Départ
                                    </h6>
                                    <p class="mb-2">
                                        <?= date('d/m/Y', strtotime($covoiturage['date_depart'])) ?>
                                        à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?>
                                    </p>

                                    <h6 class="text-primary mb-2">
                                        <i class="bi bi-person-circle me-1"></i>Conducteur
                                    </h6>
                                    <p class="mb-2">
                                        <?= htmlspecialchars($covoiturage['prenom'] . ' ' . $covoiturage['nom']) ?>
                                    </p>

                                    <?php
                                    $etapes_trajet = $etapes_by_covoiturage[(int)$covoiturage['covoiturage_id']] ?? [];
                                    $depart_nom = strtolower(trim((string)$covoiturage['lieu_depart']));
                                    $arrivee_nom = strtolower(trim((string)$covoiturage['lieu_arrivee']));
                                    $etapes_intermediaires = array_values(array_filter($etapes_trajet, static function ($nom) use ($depart_nom, $arrivee_nom) {
                                        $nom_normalise = strtolower(trim((string)$nom));
                                        return $nom_normalise !== '' && $nom_normalise !== $depart_nom && $nom_normalise !== $arrivee_nom;
                                    }));
                                    ?>
                                    <?php if (!empty($etapes_intermediaires)): ?>
                                        <h6 class="text-primary mb-2">
                                            <i class="bi bi-signpost-2 me-1"></i>Etape(s) programmée(s)
                                        </h6>
                                        <p class="mb-2 small text-muted">
                                            <?= htmlspecialchars(implode(' → ', $etapes_intermediaires)) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($covoiturage['modele']) && !empty($covoiturage['marque_libelle'])): ?>
                                        <h6 class="text-primary mb-2">
                                            <i class="bi bi-car-front me-1"></i>Véhicule
                                        </h6>
                                        <p class="mb-2">
                                            <?= htmlspecialchars($covoiturage['marque_libelle'] . ' ' . $covoiturage['modele']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="row mt-3 text-center">
                                        <div class="col-6">
                                            <?php
                                            $nb_places = $covoiturage['nb_place'];
                                            $badge_class = 'badge-places badge-places--red';
                                            if ($nb_places >= 3) {
                                                $badge_class = 'badge-places badge-places--green';
                                            } elseif ($nb_places == 2) {
                                                $badge_class = 'badge-places badge-places--orange';
                                            }
                                            ?>
                                            <?php if ($nb_places == 1): ?>
                                                <div class="text-center mb-1">
                                                    <small class="text-danger fw-bold">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>Dernière place !!!
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            <span class="badge <?= $badge_class ?>">
                                                <i class="bi bi-people me-1"></i>
                                                <?= $nb_places ?> place<?= $nb_places > 1 ? 's' : '' ?>
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-coin me-1"></i>
                                                <?= number_format($covoiturage['prix_personne'], 0) ?> crédits
                                            </span>
                                        </div>
                                    </div>

                                    <!-- CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ -->
                                    <!--
                                    <?php if (!empty($covoiturage['duree'])): ?>
                                        <div class="text-center mt-2">
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-stopwatch me-1"></i>
                                                <?= $covoiturage['duree'] ?> min
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    -->
                                </div>
                                <div class="card-footer text-center">
                                    <?php
                                    $suggestion_detail_params = array_merge(
                                        ['id' => (int)$covoiturage['covoiturage_id']],
                                        $trajets_retour_query
                                    );
                                    ?>
                                    <a href="detail_covoiturage.php?<?= htmlspecialchars(http_build_query($suggestion_detail_params), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">
                                        <i class="bi bi-eye me-1"></i>Voir le détail
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-4">
                    <a href="suggestions.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right me-2"></i>Voir toutes les suggestions
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . "/../../templates/footer.php";
?>