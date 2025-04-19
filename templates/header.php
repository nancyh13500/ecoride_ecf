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
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- section Header -->
    <section class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="/index.php">
                    <img src="/assets/logo/logo.png" alt="logo ecoride" width="120" />
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link active" aria-current="page" href="/index.php">Accueil</a></li>
                        <li class="nav-item"><a class="nav-link" href="trajets.php">Trajets</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Publier une annonce</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                    </ul>
                    <div class="btn-login me-3">
                        <a href="/login.php" type="button" class="btn bg-white btn-outline-secondary">Se connecter</a>
                    </div>
                    <div class="btn-inscription">
                        <a href="/inscription.php" type="button" class="btn btn-secondary text-dark">S'inscrire</a>
                    </div>
                </div>
            </div>
        </nav>
    </section>