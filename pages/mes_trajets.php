<?php require_once __DIR__ . "/../templates/header.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/session.php";

// Récupération des marques depuis la base de données
$stmt = $pdo->prepare("SELECT marque_id, libelle FROM marque ORDER BY libelle");
$stmt->execute();
$marques = $stmt->fetchAll(PDO::FETCH_ASSOC);
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