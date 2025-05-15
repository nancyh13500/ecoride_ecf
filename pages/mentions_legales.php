<?php
require_once __DIR__ . "/../templates/header.php";

?>

<section class="mentions_legales bg-light py-5">
    <div class="container">
        <h1 class="text-primary mb-5">
            <i class="bi bi-file-earmark-text"></i> Mentions Légales
        </h1>

        <!-- Éditeur du site -->
        <section class="bg-white p-4 rounded shadow-sm mb-4 border-start border-primary border-4">
            <h3 class="text-secondary mb-3">
                <i class="bi bi-person-circle"></i> 1. Éditeur du site
            </h3>
            <p>
                <strong>Nom du site :</strong> EcoRide.fr<br>
                <strong>Responsable de la publication :</strong> Jean Dupont<br>
                <strong>Adresse :</strong> 123 Rue de la Route, 75000 Paris, France<br>
                <strong>Téléphone :</strong> +33 1 23 45 67 89<br>
                <strong>Email :</strong> contact@ecoride.fr<br>
                <strong>Forme juridique :</strong> SASU<br>
                <strong>SIRET :</strong> 123 456 789 00012<br>
                <strong>RCS :</strong> Paris B 123 456 789
            </p>
        </section>

        <!-- Hébergeur -->
        <section class="bg-white p-4 rounded shadow-sm mb-4 border-start border-success border-4">
            <h3 class="text-secondary mb-3">
                <i class="bi bi-server"></i> 2. Hébergeur du site
            </h3>
            <p>
                <strong>Nom :</strong> OVH<br>
                <strong>Adresse :</strong> 2 rue Kellermann, 59100 Roubaix, France<br>
                <strong>Téléphone :</strong> 1007<br>
                <strong>Site Web :</strong> <a href="https://www.ovh.com" target="_blank">www.ovh.com</a>
            </p>
        </section>

        <!-- Propriété intellectuelle -->
        <section class="bg-white p-4 rounded shadow-sm mb-4 border-start border-danger border-4">
            <h3 class="text-secondary mb-3">
                <i class="bi bi-shield-lock"></i> 3. Propriété intellectuelle
            </h3>
            <p>
                Les contenus du site (textes, images, design, etc.) sont la propriété exclusive de EcoRide.fr.
                Toute reproduction ou utilisation sans autorisation est interdite.
            </p>
        </section>

        <!-- Données personnelles -->
        <section class="bg-white p-4 rounded shadow-sm mb-4 border-start border-warning border-4">
            <h3 class="text-secondary mb-3">
                <i class="bi bi-person-badge"></i> 4. Données personnelles
            </h3>
            <p>
                Conformément au RGPD, vous disposez de droits d’accès, de rectification, de suppression, et de portabilité
                de vos données. Pour exercer ces droits : <a href="mailto:contact@ecoride.fr">contact@ecoride.fr</a>
            </p>
        </section>

        <!-- Cookies -->
        <section class="bg-white p-4 rounded shadow-sm mb-4 border-start border-info border-4">
            <h3 class="text-secondary mb-3">
                <i class="bi bi-cookie"></i> 5. Cookies
            </h3>
            <p>
                Le site utilise des cookies à des fins statistiques. Vous pouvez configurer votre navigateur pour refuser les cookies.
            </p>
        </section>

        <!-- Droit applicable -->
        <section class="bg-white p-4 rounded shadow-sm mb-4 border-start border-dark border-4">
            <h3 class="text-secondary mb-3">
                <i class="bi bi-journal-text"></i> 6. Droit applicable
            </h3>
            <p>
                Le site est soumis au droit français. En cas de litige, les tribunaux français sont seuls compétents.
            </p>
        </section>
    </div>
</section>
<?php require_once __DIR__ . "/../templates/footer.php";
?>