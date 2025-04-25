<?php require_once __DIR__ . "/templates/header.php";
?>

<!-- Hero Section -->
<section class="hero">
    <div class="background-img"></div>
    <div class="content px-4 py-5 my-5 text-center">
        <h1 class="fw-bold">Trouvez un covoiturage</h1>
        <div class="col-lg-6 mx-auto">
            <p class="lead mb-4 ">La solution accessible et durable pour tous.</p>
            <div class="search-bar row">
                <div class="search-field col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                        <input type="text" name="depart" class="form-control border-start-0 text-center" placeholder="Ville de départ" required>
                    </div>
                </div>
                <div class="search-field col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt text-primary"></i></span>
                        <input type="text" name="arrivee" class="form-control border-start-0 text-center" placeholder="Ville d'arrivée" required>
                    </div>
                </div>
                <div class="search-field col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar text-primary"></i></span>
                        <input type="date" name="date" class="form-control border-start-0 text-center" required>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button type="button" class="btn btn-primary w-50">Lancer la recherche<i class="bi bi-search ms-2"></i></button>
            </div>
        </div>
    </div>
</section>

<!-- Results Section -->
<section class="results bg-light py-5">
    <div class="container">
        <div class="result-header text-center mb-5">
            <div class="bg-dark text-white p-4 rounded">
                <h2>Résultats pour : Martigues → Marseille</h2>
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
                    <label class="form-label price-min">Prix minimum (€)</label>
                    <input type="number" class="form-control" placeholder="Prix min">
                </div>
                <div class="col-md-2 text-center">
                    <label class="form-label price-max">Prix maximum (€)</label>
                    <input type="number" class="form-control" placeholder="Prix max">
                </div>
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

        <h5 class="mb-4">Résultat(s) trouvé(s) :</h5>
        <p class="text-center">Aucun résultats trouvés pour ce trajet</p>

        <h5 class="mt-4 mb-4">Suggestions du moment :</h5>
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card-trajet rounded-top">
                    <div class="card-header bg-dark text-white text-center py-3">
                        <h4 class="mb-0">Trajet</h4>
                    </div>
                    <div class="card-body bg-white text-center">
                        <img src="/assets/img/profil.jpg" alt="Profil" class="rounded-circle mb-3" style="width: 90px">
                        <h5 class="card-title mb-3">Martigues → Marseille</h5>
                        <p class="card-text">Le 25 avril 2025</p>
                        <p class="card-text">Chauffeur : David</p>
                        <p class="card-text">Places restantes : 1</p>
                        <p class="card-text">Tarif : 20 €</p>
                        <button class="btn btn-primary mb-4">Voir le trajet</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/templates/footer.php";
?>