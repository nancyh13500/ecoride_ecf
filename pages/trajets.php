<?php
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";

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

// Rechercher les covoiturages selon les critères de recherche
$covoiturages_recherche = [];
$has_search_criteria = !empty($search_depart) && !empty($search_arrivee);

if ($has_search_criteria) {
    try {
        $query_sql = "
            SELECT c.*, u.nom, u.prenom, v.modele, m.libelle AS marque_libelle
            FROM covoiturage c
            LEFT JOIN user u ON c.user_id = u.user_id
            LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
            LEFT JOIN marque m ON v.marque_id = m.marque_id
            WHERE c.statut = 1 AND c.nb_place > 0 
            AND c.lieu_depart LIKE :depart 
            AND c.lieu_arrivee LIKE :arrivee
            AND c.date_depart >= CURDATE()
        ";

        // Filtre optionnel sur une ville d'étape (départ/arrivée compris via table etape)
        if (!empty($search_etape)) {
            $query_sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM etape e
                    JOIN ville v_etape ON v_etape.ville_id = e.ville_id
                    WHERE e.covoiturage_id = c.covoiturage_id
                    AND v_etape.nom LIKE :etape
                )
            ";
        }

        // Ajouter la condition de date si spécifiée
        if (!empty($search_date)) {
            $query_sql .= " AND c.date_depart = :date_search";
        }

        $query_search = $pdo->prepare($query_sql);
        $params = [
            'depart' => '%' . $search_depart . '%',
            'arrivee' => '%' . $search_arrivee . '%'
        ];

        if (!empty($search_date)) {
            $params['date_search'] = $search_date;
        }
        if (!empty($search_etape)) {
            $params['etape'] = '%' . $search_etape . '%';
        }

        $query_search->execute($params);

        $covoiturages_recherche = $query_search->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $covoiturages_recherche = [];
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

require_once __DIR__ . "/../templates/header.php";

?>

<!--section search -->
<section class="hero">
    <div class="background-img"></div>
    <div class="content px-4 py-5 my-5 text-center">
        <h1 class="fw-bold">Trouvez un covoiturage</h1>
        <p class="lead mb-4">La solution accessible et durable pour tous.</p>
        <div class="col-lg-6 mx-auto">
            <form method="POST" action="">
                <div class="search-bar row">
                    <div class="search-field col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                            <input type="text" name="depart" class="form-control border-start-0 text-center" placeholder="Ville de départ" list="villes-depart" required>
                            <datalist id="villes-depart">
                                <?php foreach ($villes_depart as $ville): ?>
                                    <option value="<?= htmlspecialchars($ville) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="search-field col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt text-primary"></i></span>
                            <input type="text" name="arrivee" class="form-control border-start-0 text-center" placeholder="Ville d'arrivée" list="villes-arrivee" required>
                            <datalist id="villes-arrivee">
                                <?php foreach ($villes_arrivee as $ville): ?>
                                    <option value="<?= htmlspecialchars($ville) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="search-field col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar text-primary"></i></span>
                            <input type="date" name="date" class="form-control border-start-0 text-center">
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <button type="submit" name="search_trajet" class="btn btn-primary w-50">Lancer la recherche<i class="bi bi-search ms-2"></i></button>
                </div>
            </form>
        </div>
    </div>
</section>
<!--end section search -->

<!-- Results Section -->

<div class="result-header text-center mb-5">
    <div class="bg-dark text-white p-4">
        <?php if (!empty($search_depart) && !empty($search_arrivee)): ?>
            <h2>Résultats pour : <?= htmlspecialchars($search_depart) ?> → <?= htmlspecialchars($search_arrivee) ?></h2>
            <?php if (!empty($search_date)): ?>
                <p class="mb-0">Date : <?= date('d/m/Y', strtotime($search_date)) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <h2>Découvrez les trajets disponibles</h2>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="filters mb-4">
    <div class="row">
        <div class="col-md-2 text-center">
            <div class="form-check d-flex flex-column justify-content-center align-items-center">
                <label class="form-check-label-eco mb-2" for="ecoTrip">
                    Voyage écologique
                </label>
                <input class="form-check-input mt-3 border-dark align-items-end" type="checkbox" id="ecoTrip">
            </div>
        </div>

        <div class="col-md-2 text-center">
            <label class="form-label credit-min">Crédit minimum (C)</label>
            <input type="number" class="form-control filter-price" placeholder="Crédit min">
        </div>
        <!-- CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ -->
        <!--
        <div class="col-md-2 text-center">
            <label class="form-label price-max">Durée maximum</label>
            <input type="number" class="form-control filter-duration" placeholder="Durée max">
        </div>
        -->
        <div class="col-md-3 text-center">
            <label class="form-label note">Note minimale</label>
            <select class="form-select">
                <option selected>Toutes les notes</option>
                <option value="5">5 étoiles</option>
                <option value="4">4 étoiles et plus</option>
                <option value="3">3 étoiles et plus</option>
            </select>
        </div>
        <div class="col-md-3 d-flex justify-content-center align-items-end">
            <button class="btn btn-filtre text-dark btn-secondary w-50">Filtrer</button>
        </div>
    </div>
</div>

<section id="results" class="results bg-light py-5">
    <div class="container">
        <!-- Section des résultats de recherche -->
        <?php if ($has_search_criteria): ?>
            <div class="search-results-section mb-5">
                <h3 class="text-center mb-4">
                    <i class="bi bi-check2-square text-success me-2"></i></i>
                    Résultat(s) trouvé(s) :
                </h3>
                <?php if (!empty($covoiturages_recherche)): ?>
                    <div class="row">
                        <?php foreach ($covoiturages_recherche as $covoiturage): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-dark text-white text-center border-light">
                                        <h6 class="mb-0"><i class="bi bi-car-front me-2"></i>Trajet disponible</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <h6 class="text-primary mb-2"><i class="bi bi-geo-alt-fill me-1"></i>Trajet</h6>
                                        <p class="mb-2"><strong><?= htmlspecialchars($covoiturage['lieu_depart']) ?></strong> → <strong><?= htmlspecialchars($covoiturage['lieu_arrivee']) ?></strong></p>

                                        <h6 class="text-primary mb-2"><i class="bi bi-calendar-event me-1"></i>Départ</h6>
                                        <p class="mb-2"><?= date('d/m/Y', strtotime($covoiturage['date_depart'])) ?> à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?></p>

                                        <h6 class="text-primary mb-2"><i class="bi bi-person-circle me-1"></i>Conducteur</h6>
                                        <p class="mb-2"><?= htmlspecialchars($covoiturage['prenom'] . ' ' . $covoiturage['nom']) ?></p>

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
                                            <h6 class="text-primary mb-2"><i class="bi bi-signpost-2 me-1"></i>Etape(s) programmée(s)</h6>
                                            <p class="mb-2 small text-muted"><?= htmlspecialchars(implode(' → ', $etapes_intermediaires)) ?></p>
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
                                                <span class="badge <?= $badge_class ?>"><i class="bi bi-people me-1"></i><?= $nb_places ?> place<?= $nb_places > 1 ? 's' : '' ?></span>
                                                <?php if ($nb_places == 1): ?>
                                                    <div class="text-center mb-1">
                                                        <small class="text-danger fw-bold">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>Dernière place !!!
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-6">
                                                <span class="badge bg-warning text-dark"><i class="bi bi-coin me-1"></i><?= number_format($covoiturage['prix_personne'], 0) ?> crédits</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-center">
                                        <a href="detail_covoiturage.php?id=<?= $covoiturage['covoiturage_id'] ?>" class="btn btn-secondary btn-sm">
                                            <i class="bi bi-eye me-1"></i>Voir le détail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert bg-dark text-white text-center" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Aucun trajet trouvé</strong><br>
                        Aucun covoiturage ne correspond à votre recherche pour le trajet <strong><?= htmlspecialchars($search_depart) ?> → <?= htmlspecialchars($search_arrivee) ?></strong>
                        <?php if (!empty($search_date)): ?>
                            le <?= date('d/m/Y', strtotime($search_date)) ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Section Suggestions - Trajets en attente -->
        <?php if (!empty($covoiturages_suggestion)): ?>
            <div class="suggestions-section mt-5">
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
                                    <a href="detail_covoiturage.php?id=<?= $covoiturage['covoiturage_id'] ?>" class="btn btn-secondary btn-sm">
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

<?php require_once __DIR__ . "/../templates/footer.php";
?>