<?php
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

// Récupérer les rôles disponibles depuis la base de données
$roles = [];
try {
    $stmt = $pdo->prepare("SELECT role_id, libelle FROM role ORDER BY role_id");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la table role n'existe pas, utiliser des rôles par défaut
    $roles = [
        ['role_id' => 1, 'libelle' => 'Administrateur'],
        ['role_id' => 2, 'libelle' => 'Employé'],
        ['role_id' => 3, 'libelle' => 'Utilisateur']
    ];
}

// Messages de succès/erreur
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';

// Récupérer les crédits à jour depuis la base (la session peut ne pas être synchronisée)
$credits = 0;
try {
    $stmtCredits = $pdo->prepare("SELECT credits FROM user WHERE user_id = :id");
    $stmtCredits->execute(['id' => $_SESSION['user']['user_id']]);
    $credits = (int) ($stmtCredits->fetchColumn() ?? 0);
} catch (PDOException $e) {
    $credits = (int) ($_SESSION['user']['credits'] ?? 0);
}

// Nettoyer les messages de session
unset($_SESSION['success'], $_SESSION['error']);

require_once __DIR__ . "/../templates/header.php";
?>

<section class="hero count-section py-5">
    <div class="container">

        <nav aria-label="breadcrumb" class="ps-3 pt-3 mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item "><a href="/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mon compte</li>
            </ol>
        </nav>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                        <a href="/pages/user_count.php" class="list-group-item list-group-item-action <?= !isset($_GET['create_employee']) ? 'active' : '' ?>">
                            <i class="bi bi-person-circle me-2"></i>Mes informations
                        </a>
                        <a href="/pages/mes_trajets.php" class="list-group-item list-group-item-action">
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
                            <a href="/pages/user_count.php?create_employee=1" class="list-group-item list-group-item-action <?= isset($_GET['create_employee']) ? 'active' : '' ?>">
                                <i class="bi bi-person-plus me-2"></i>Créer un employé
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9">
                <!-- Mes crédits -->
                <div class="card mb-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-coin me-2"></i>Mes crédits</h5>
                        <span class="badge credits-badge bg-success me-3"><?= htmlspecialchars((string)$credits) ?></span>
                    </div>
                </div>
                <!-- Section création d'employé (visible seulement pour les administrateurs) -->
                <?php if (($_SESSION['user']['role_id'] ?? 3) == 1): ?>
                    <div class="<?= isset($_GET['create_employee']) ? '' : 'collapse' ?>" id="creer-employe-section" <?= isset($_GET['create_employee']) ? 'style="display: block !important;"' : '' ?>>
                        <div class="card mb-4">
                            <div class="card-header bg-light text-dark">
                                <h4 class="mb-0">
                                    <i class="bi bi-person-plus me-2"></i>
                                    Créer un compte employé
                                </h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="/lib/create_employee.php" enctype="multipart/form-data">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="emp_nom" class="form-label">Nom</label>
                                            <input type="text" class="form-control" id="emp_nom" name="nom" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="emp_prenom" class="form-label">Prénom</label>
                                            <input type="text" class="form-control" id="emp_prenom" name="prenom" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="emp_email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="emp_email" name="email" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="emp_telephone" class="form-label">Téléphone</label>
                                            <input type="tel" class="form-control" id="emp_telephone" name="telephone">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="emp_password" class="form-label">Mot de passe</label>
                                            <input type="password" class="form-control" id="emp_password" name="password" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="emp_pseudo" class="form-label">Pseudo</label>
                                            <input type="text" class="form-control" id="emp_pseudo" name="pseudo" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="emp_adresse" class="form-label">Adresse</label>
                                            <input type="text" class="form-control" id="emp_adresse" name="adresse">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="emp_date_naissance" class="form-label">Date de naissance</label>
                                            <input type="date" class="form-control" id="emp_date_naissance" name="date_naissance">
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-person-plus me-2"></i>Créer le compte employé
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section Mes informations personnelles (masquée lors de la création d'employé) -->
                <?php if (!isset($_GET['create_employee'])): ?>
                    <div class="card">
                        <div class="card-header bg-light">
                            <h4 class="mb-0">Mes informations personnelles</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="/lib/update_user.php" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">Nom</label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($_SESSION['user']['nom']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">Prénom</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($_SESSION['user']['prenom']) ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_SESSION['user']['email']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telephone" class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($_SESSION['user']['telephone']) ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="adresse" class="form-label">Adresse</label>
                                        <input type="text" class="form-control" id="adresse" name="adresse" value="<?= htmlspecialchars($_SESSION['user']['adresse']) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="cp" class="form-label">CP</label>
                                        <input type="text" class="form-control" id="cp" name="cp" value="">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="ville" class="form-label">Ville</label>
                                        <input type="text" class="form-control" id="ville" name="ville" value="">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="date_naissance" class="form-label">Date de naissance</label>
                                        <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($_SESSION['user']['date_naissance']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="pseudo" class="form-label">Pseudo</label>
                                        <input type="text" class="form-control" id="pseudo" name="pseudo" value="<?= htmlspecialchars($_SESSION['user']['pseudo'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="role_covoiturage" class="form-label">Je suis...</label>
                                        <select class="form-select" id="role_covoiturage" name="role_covoiturage" required>
                                            <option value="Passager" <?= (($_SESSION['user']['role_covoiturage'] ?? '') === 'Passager') ? 'selected' : '' ?>>Passager</option>
                                            <option value="Chauffeur" <?= (($_SESSION['user']['role_covoiturage'] ?? '') === 'Chauffeur') ? 'selected' : '' ?>>Chauffeur</option>
                                            <option value="Les deux" <?= (($_SESSION['user']['role_covoiturage'] ?? '') === 'Les deux') ? 'selected' : '' ?>>Les deux</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <?php if (($_SESSION['user']['role_id'] ?? 3) == 1): ?>
                                        <div class="col-md-8">
                                            <label for="role_id" class="form-label">Rôle utilisateur</label>
                                            <select class="form-select" id="role_id" name="role_id" required>
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?= $role['role_id'] ?>" <?= (($_SESSION['user']['role_id'] ?? 3) == $role['role_id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($role['libelle']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted text-center">
                                                <i class="bi bi-info-circle"></i>
                                                Administrateur : Accès complet | Employé : Avis et covoiturages | Utilisateur : Accès standard
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div class="col-md-8">
                                            <label class="form-label">Rôle utilisateur</label>
                                            <div class="form-control bg-light">
                                                <?php
                                                $current_role_id = $_SESSION['user']['role_id'] ?? 3;
                                                $current_role = array_filter($roles, function ($role) use ($current_role_id) {
                                                    return $role['role_id'] == $current_role_id;
                                                });
                                                $current_role = reset($current_role);
                                                echo htmlspecialchars($current_role['libelle'] ?? 'Utilisateur');
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="bi bi-shield-check me-2"></i>
                                                    Rôle actuel
                                                </h6>
                                                <p class="card-text mb-0">
                                                    <?php
                                                    $current_role_id = $_SESSION['user']['role_id'] ?? 3;
                                                    $current_role = array_filter($roles, function ($role) use ($current_role_id) {
                                                        return $role['role_id'] == $current_role_id;
                                                    });
                                                    $current_role = reset($current_role);
                                                    ?>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($current_role['libelle'] ?? 'Utilisateur') ?></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="photo" class="form-label">Photo de profil</label>
                                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                        <small class="text-muted">Laissez vide pour ne pas changer le mot de passe</small>
                                    </div>
                                </div>

                                <!-- Encart pour afficher la photo - visible seulement si une photo existe -->
                                <?php if (!empty($_SESSION['user']['photo'])): ?>
                                    <div class="row mb-3" id="photo-display-section">
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0 text-center">Photo de profil</h6>
                                                </div>
                                                <div class="card-body text-center">
                                                    <!-- Photo actuelle -->
                                                    <img id="current-photo" src="data:image/jpeg;base64,<?= base64_encode($_SESSION['user']['photo']) ?>"
                                                        alt="Photo de profil" class="img-fluid rounded" style="max-width: 100px; max-height: 100px;">
                                                    <div class="mt-2">
                                                        <button type="button" id="delete-photo-btn" class="btn btn-sm btn-outline-danger">
                                                            Supprimer la photo
                                                        </button>
                                                    </div>

                                                    <!-- Prévisualisation de la nouvelle photo -->
                                                    <div id="photo-preview" class="mt-3" style="display: none;">
                                                        <h6>Nouvelle photo</h6>
                                                        <img id="preview-image" src="" alt="Prévisualisation" class="img-fluid rounded" style="max-width: 100px; max-height: 100px;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Placeholder pour la prévisualisation quand aucune photo n'existe -->
                                <div id="photo-preview-section" class="row mb-3" style="display: none;">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0 text-center">Nouvelle photo</h6>
                                            </div>
                                            <div class="card-body text-center">
                                                <img id="preview-image-new" src="" alt="Prévisualisation" class="img-fluid rounded" style="max-width: 100px; max-height: 100px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center text-md-end">
                                    <button type="submit" class="btn btn-primary">Mettre à jour mes informations</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script src="/assets/js/photo-preview.js"></script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>