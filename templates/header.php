<?php
require_once __DIR__ . "/../lib/session.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">


</head>

<body>
    <!-- section Header -->

    <nav class="navbar navbar-expand-lg bg-light position-fixed" id="navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/index.php">
                <img src="/assets/logo/logo.png" alt="logo ecoride" width="80" />
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="/index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="/pages/trajets.php">Trajets</a></li>
                    <li class="nav-item"><a class="nav-link" href="/pages/publish.php">Publier une annonce</a></li>
                    <li class="nav-item"><a class="nav-link" href="/pages/contact.php">Contact</a></li>
                </ul>
                <div class="col-md-3 d-flex align-items-center">
                    <?php if (isset($_SESSION['user'])) { ?>
                        <a href="/logout.php" type="button" class="btn bg-dark text-white btn-outline-secondary me-2">Déconnexion</a>
                        <span class="text-black ms-2">Bienvenue <?= htmlspecialchars($_SESSION['user']['prenom']) ?></span>
                    <?php } else { ?>
                        <a href="/login.php" type="button" class="btn bg-white btn-outline-secondary">Se connecter</a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </nav>