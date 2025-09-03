<?php
require_once __DIR__ . "/templates/header.php";
require_once __DIR__ . "/lib/pdo.php";
require_once __DIR__ . "/lib/user.php";

// $hash = password_hash('test', PASSWORD_DEFAULT);
// var_dump($hash);


$errors = [];

if (isset($_POST['loginUser'])) {
    $user = verifyUserLoginPassword($pdo, $_POST['email'], $_POST['password']);

    if ($user) {
        // on va le connecter => session
        $_SESSION['user'] = $user;
        header('location: index.php');
        exit();
    } else {
        // afficher une erreur
        $errors[] = "Identifiants incorrects. Veuillez réessayer.";
    }
}

// Traitement de l'enregistrement
if (isset($_POST['registerUser'])) {
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_SPECIAL_CHARS);
    $adresse = filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_SPECIAL_CHARS);
    $cp = filter_input(INPUT_POST, 'cp', FILTER_SANITIZE_SPECIAL_CHARS);
    $ville = filter_input(INPUT_POST, 'ville', FILTER_SANITIZE_SPECIAL_CHARS);
    $date_naissance = filter_input(INPUT_POST, 'date_naissance', FILTER_SANITIZE_SPECIAL_CHARS);

    // Validation des données
    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($confirmPassword)) {
        $errors[] = "Les champs nom, prénom, email et mot de passe sont obligatoires.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    // Vérifier si l'email existe déjà
    $emailQuery = $pdo->prepare("SELECT COUNT(*) FROM user WHERE email = :email");
    $emailQuery->bindValue(':email', $email, PDO::PARAM_STR);
    $emailQuery->execute();
    if ($emailQuery->fetchColumn() > 0) {
        $errors[] = "Cette adresse email est déjà utilisée.";
    }

    // Si pas d'erreurs, on procède à l'enregistrement
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $registerQuery = $pdo->prepare("INSERT INTO user (nom, prenom, email, password, telephone, adresse, date_naissance, photo, pseudo, role_id) 
                               VALUES (:nom, :prenom, :email, :password, :telephone, :adresse, :date_naissance, :photo, :pseudo, :role_id)");

        $registerQuery->bindValue(':nom', $nom, PDO::PARAM_STR);
        $registerQuery->bindValue(':prenom', $prenom, PDO::PARAM_STR);
        $registerQuery->bindValue(':email', $email, PDO::PARAM_STR);
        $registerQuery->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $registerQuery->bindValue(':telephone', $telephone, PDO::PARAM_STR);
        $registerQuery->bindValue(':adresse', $adresse . ', ' . $cp . ' ' . $ville, PDO::PARAM_STR);
        $registerQuery->bindValue(':date_naissance', $date_naissance, PDO::PARAM_STR);
        $registerQuery->bindValue(':photo', '', PDO::PARAM_STR);
        $registerQuery->bindValue(':pseudo', $prenom . ' ' . $nom, PDO::PARAM_STR);
        $registerQuery->bindValue(':role_id', 3, PDO::PARAM_INT); // 3 = Utilisateur par défaut

        if ($registerQuery->execute()) {
            $_SESSION['success'] = "Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.";
            header('location: index.php');
            exit();
        } else {
            $errors[] = "Une erreur est survenue lors de l'enregistrement.";
        }
    }
}

?>
<section class="hero px-4 py-5">
    <div class="background-login"></div>
    <div class="container login-register mt-5">
        <?php
        foreach ($errors as $error) { ?>
            <div class="alert alert-danger" role="alert">
                <?= $error; ?>
            </div>
        <?php }
        ?>
        <div class="row justify-content-center mb-4">
            <div class="col-md-4">

                <div class="card border-dark">
                    <ul class="nav nav-pills nav-justified" id="ex1" role="tablist">
                        <li class="nav-item ms-3 mt-3 mb-3" role="presentation">
                            <a class="nav-link active" id="tab-login" data-bs-toggle="pill" href="#pills-login" role="tab" aria-controls="pills-login" aria-selected="true">Se connecter</a>
                        </li>
                        <li class="nav-item me-3 mt-3 mb-3" role="presentation">
                            <a class="nav-link" id="tab-register" data-bs-toggle="pill" href="#pills-register" role="tab" aria-controls="pills-register" aria-selected="false">S'enregistrer</a>
                        </li>
                    </ul>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="login tab-pane fade show active" id="pills-login" role="tabpanel" aria-labelledby="tab-login">
                                <form action="" method="post">
                                    <div class="form-outline mb-4">
                                        <input type="email" id="loginUser" name="email" class="form-control border-dark bg-light" placeholder="Email" required>
                                        <label class="form-label" for="loginUser"></label>
                                    </div>

                                    <div class="form-outline mb-4">
                                        <input type="password" id="loginPassword" name="password" class="form-control border-dark bg-light" placeholder="Mot de passe" required>
                                        <label class="form-label" for="loginPassword"></label>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-6 d-flex justify-content-center align-items-center">
                                            <div class="form-check-login">
                                                <input class="form-check-input-login border-dark" type="checkbox" value="" id="loginCheck">
                                                <label class="form-check-label-login" for="loginCheck">Rester connecté</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6 d-flex justify-content-center">
                                            <a href="#">Mot de passe oublié</a>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-connect btn-secondary text-dark btn-block mb-4 w-100" name="loginUser">Se connecter</button>
                                </form>
                            </div>

                            <div class="register tab-pane fade" id="pills-register" role="tabpanel" aria-labelledby="tab-register">
                                <form action="" method="post">
                                    <div class="row">
                                        <div class="col-md-6 form-outline mb-4">
                                            <input type="text" id="registerName" name="nom" class="form-control border-dark bg-light" placeholder="Nom" required>
                                            <label class="form-label" for="registerName"></label>
                                        </div>
                                        <div class="col-md-6 form-outline mb-4">
                                            <input type="text" id="registerPrenom" name="prenom" class="form-control border-dark bg-light" placeholder="Prénom" required>
                                            <label class="form-label" for="registerPrenom"></label>
                                        </div>
                                    </div>

                                    <div class="form-outline mb-4">
                                        <input type="email" id="registerEmail" name="email" class="form-control border-dark bg-light" placeholder="Email" required>
                                        <label class="form-label" for="registerEmail"></label>
                                    </div>

                                    <div class="form-outline mb-4">
                                        <input type="password" id="registerPassword" name="password" class="form-control border-dark bg-light" placeholder="Mot de passe" required>
                                        <label class="form-label" for="registerPassword"></label>
                                    </div>

                                    <div class="form-outline mb-4">
                                        <input type="password" id="registerRepeatPassword" name="confirmPassword" class="form-control border-dark bg-light" placeholder="Confirmer mot de passe" required>
                                        <label class="form-label" for="registerRepeatPassword"></label>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 form-outline mb-4">
                                            <input type="tel" id="registerPhone" name="telephone" class="form-control border-dark bg-light" placeholder="Téléphone">
                                            <label class="form-label" for="registerPhone"></label>
                                        </div>
                                        <div class="col-md-6 form-outline mb-4">
                                            <input type="date" id="registerBirth" name="date_naissance" class="form-control border-dark bg-light">
                                            <label class="form-label" for="registerBirth"></label>
                                        </div>
                                    </div>

                                    <div class="form-outline mb-4">
                                        <input type="text" id="registerAddress" name="adresse" class="form-control border-dark bg-light" placeholder="Adresse">
                                        <label class="form-label" for="registerAddress"></label>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 form-outline mb-4">
                                            <input type="text" id="registerCP" name="cp" class="form-control border-dark bg-light" placeholder="Code postal">
                                            <label class="form-label" for="registerCP"></label>
                                        </div>
                                        <div class="col-md-6 form-outline mb-4">
                                            <input type="text" id="registerCity" name="ville" class="form-control border-dark bg-light" placeholder="Ville">
                                            <label class="form-label" for="registerCity"></label>
                                        </div>
                                    </div>

                                    <div class="row check-accept align-items-center">
                                        <div class="form-check col-md-12 d-flex justify-content-center mb-4">
                                            <input class="form-check-input me-2 border-dark" type="checkbox" value="" id="registerCheck" required>
                                            <label class="form-check-label-register" for="registerCheck">
                                                J'accepte les conditions d'utilisation
                                            </label>
                                        </div>
                                    </div>

                                    <button type="submit" name="registerUser" class="btn btn-connect btn-secondary btn-block mt-3 mb-3 w-100">S'inscrire</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/templates/footer.php";
?>