<?php
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";

requireLogin();

// Données utilisateur
$currentUserId = (int)$_SESSION['user']['user_id'];

// 1) Trajets publiés par l'utilisateur (ex: terminés)
$trajetsPublies = [];
try {
    $stmt = $pdo->prepare(
        "SELECT c.*
         FROM covoiturage c
         WHERE c.user_id = :uid
         ORDER BY c.date_depart DESC, c.covoiturage_id DESC"
    );
    $stmt->execute([':uid' => $currentUserId]);
    $trajetsPublies = $stmt->fetchAll();
} catch (Throwable $e) {
    $trajetsPublies = [];
}

// 2) Trajets réservés par l'utilisateur (en tant que passager)
// Le dump SQL fourni ne contient pas de table de réservation. On vérifie dynamiquement
// l'existence d'une table potentielle 'reservation' ou 'reservations' avant de requêter.
$trajetsReserves = [];
$reservationSupport = null; // 'reservation' | 'reservations' | null
try {
    $check = $pdo->query("SELECT DATABASE() AS db");
    $dbName = $check->fetchColumn();

    $tableCheck = $pdo->prepare(
        "SELECT table_name FROM information_schema.tables
         WHERE table_schema = :db AND table_name IN ('reservation','reservations')"
    );
    $tableCheck->execute([':db' => $dbName]);
    $reservationSupport = $tableCheck->fetchColumn() ?: null;

    if ($reservationSupport) {
        // Hypothèse de schéma minimal: (reservation_id, user_id, covoiturage_id, ...)
        $sql = "SELECT c.*
                FROM {$reservationSupport} r
                JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
                WHERE r.user_id = :uid
                ORDER BY c.date_depart DESC, c.covoiturage_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $currentUserId]);
        $trajetsReserves = $stmt->fetchAll();
    }
} catch (Throwable $e) {
    $trajetsReserves = [];
}

require_once __DIR__ . "/../templates/header.php";
?>

<section class="hero count-section py-5">
    <div class="container">

        <nav aria-label="breadcrumb" class="ps-3 pt-3 mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item "><a href="/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mes réservations</li>
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
                        <a href="/pages/mes_reservations.php" class="list-group-item list-group-item-action active">Mes réservations</a>
                        <a href="/pages/mes_voitures.php" class="list-group-item list-group-item-action">Mes voitures</a>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0">Réservations (passager)</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($reservationSupport === null): ?>
                            <div class="alert alert-warning">
                                La fonctionnalité de réservations n'est pas encore disponible (table de réservations absente).
                            </div>
                        <?php elseif (empty($trajetsReserves)): ?>
                            <p>Aucune réservation trouvée.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($trajetsReserves as $trajet): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header bg-secondary text-white">
                                                Trajet réservé
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-1"><strong>Départ:</strong> <?= htmlspecialchars($trajet['lieu_depart'] ?? '') ?></p>
                                                <p class="mb-1"><strong>Arrivée:</strong> <?= htmlspecialchars($trajet['lieu_arrivee'] ?? '') ?></p>
                                                <p class="mb-1"><strong>Date:</strong> <?= htmlspecialchars($trajet['date_depart'] ?? '') ?></p>
                                                <p class="mb-1"><strong>Prix/personne:</strong> <?= htmlspecialchars((string)($trajet['prix_personne'] ?? '')) ?> €</p>
                                                <p class="mb-1"><strong>Places:</strong> <?= htmlspecialchars((string)($trajet['nb_place'] ?? '')) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <h4 class="mb-0">Mes trajets publiés</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($trajetsPublies)): ?>
                            <p>Aucun trajet publié.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($trajetsPublies as $trajet): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header bg-secondary text-white">
                                                Trajet publié <?= isset($trajet['statut']) ? '(statut: ' . htmlspecialchars((string)$trajet['statut']) . ')' : '' ?>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-1"><strong>Départ:</strong> <?= htmlspecialchars($trajet['lieu_depart'] ?? '') ?></p>
                                                <p class="mb-1"><strong>Arrivée:</strong> <?= htmlspecialchars($trajet['lieu_arrivee'] ?? '') ?></p>
                                                <p class="mb-1"><strong>Date:</strong> <?= htmlspecialchars($trajet['date_depart'] ?? '') ?></p>
                                                <p class="mb-1"><strong>Prix/personne:</strong> <?= htmlspecialchars((string)($trajet['prix_personne'] ?? '')) ?> €</p>
                                                <p class="mb-1"><strong>Places:</strong> <?= htmlspecialchars((string)($trajet['nb_place'] ?? '')) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>

<?php require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/session.php";

// Récupération des marques depuis la base de données
$query = $pdo->prepare("SELECT marque_id, libelle FROM marque ORDER BY libelle");
$query->execute();
$marques = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <label class="form-label immatriculation" for="form3Example1w">Immatriculation</label>
        <input type="text" id="form3Example1w" class="form-control bg-light" required>
    </div>
    <div class="col-md-4 mb-4">
        <label class="form-label first_circulation" for="form3Example1w">Date 1ère circulation</label>
        <input type="text" id="form3Example1w" class="form-control bg-light" required>
    </div>
    <div class="col-md-4 mb-4">
        <label class="form-label modele" for="form3Example1w">Modèle</label>
        <input type="text" id="form3Example1w" class="form-control bg-light" required>
    </div>
</div>



<?php require_once __DIR__ . "/../templates/footer.php";
?>