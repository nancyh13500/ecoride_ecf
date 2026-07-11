<?php
require_once __DIR__ . "/../lib/session.php";

$pageTitle = $pageTitle ?? 'Accueil';
$siteName = 'EcoRide';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">


</head>

<body>
    <a href="#main-content" class="skip-link visually-hidden-focusable">Aller au contenu principal</a>

    <header>
        <nav class="navbar navbar-expand-lg bg-light position-fixed" id="navbar" aria-label="Navigation principale">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="/index.php" aria-label="EcoRide — Retour à l'accueil">
                    <img src="/assets/logo/logo.png" alt="" width="80" aria-hidden="true">
                </a>
                <button class="navbar-toggler me-4" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Ouvrir ou fermer le menu de navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link" href="/index.php">Accueil</a></li>
                        <li class="nav-item"><a class="nav-link" href="/pages/trajets.php">Trajets</a></li>
                        <?php if (isset($_SESSION['user'])) { ?>
                            <li class="nav-item"><a class="nav-link" href="/pages/covoiturage.php">Mon Covoiturage</a></li>
                            <li class="nav-item"><a class="nav-link" href="/pages/user_count.php">Mon compte</a></li>
                        <?php } else { ?>
                            <li class="nav-item"><a class="nav-link" href="/pages/publish.php">Publier une annonce</a></li>
                        <?php } ?>
                        <li class="nav-item"><a class="nav-link" href="/pages/avis.php">Vos avis</a></li>
                        <li class="nav-item"><a class="nav-link" href="/pages/contact.php">Contact</a></li>
                    </ul>
                    <div class="col-md-3 d-flex align-items-center">
                        <?php if (isset($_SESSION['user'])) { ?>
                            <a href="/logout.php" class="btn bg-dark text-white btn-outline-secondary">Déconnexion</a>
                            <span class="text-black text-center ms-3" aria-live="polite">Bienvenue <?= htmlspecialchars($_SESSION['user']['prenom']) ?></span>
                        <?php } else { ?>
                            <a href="/login.php" class="btn bg-white btn-outline-secondary">Se connecter</a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main id="main-content" tabindex="-1">
