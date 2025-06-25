<?php
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit();
}

require_once __DIR__ . "/../templates/header.php";
?>

<section class="hero count-section py-5">
    <div class="container">

        <nav aria-label="breadcrumb" class="ps-2 pt-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item "><a href="/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mon compte</li>
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
                        <a href="/pages/user_count.php" class="list-group-item list-group-item-action active">Mes informations</a>
                        <a href="/pages/mes_trajets.php" class="list-group-item list-group-item-action">Mes trajets</a>
                        <a href="/pages/mes_reservations.php" class="list-group-item list-group-item-action">Mes réservations</a>
                        <a href="/pages/mes_voitures.php" class="list-group-item list-group-item-action">Mes voitures</a>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9">
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
                                    <label for="adresse" class="form-label">CP</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" value="<?= htmlspecialchars($_SESSION['user']['adresse']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="adresse" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" value="<?= htmlspecialchars($_SESSION['user']['adresse']) ?>">
                                </div>
                            </div>

                            <div class="row mb-3">

                                <div class="col-md-4">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($_SESSION['user']['date_naissance']) ?>">
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

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Mettre à jour mes informations</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>