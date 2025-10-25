<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/pdo.php";

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 1) {
    $_SESSION['error'] = "Accès refusé. Cette fonctionnalité est réservée aux administrateurs.";
    header("Location: /pages/user_count.php");
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Récupérer les données du formulaire
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $date_naissance = $_POST['date_naissance'] ?? '0000-00-00';
        $pseudo = trim($_POST['pseudo']);
        $role_covoiturage = $_POST['role_covoiturage'];
        $password = $_POST['password'];

        // Validation des données
        $errors = [];

        if (empty($nom)) $errors[] = "Le nom est requis.";
        if (empty($prenom)) $errors[] = "Le prénom est requis.";
        if (empty($email)) $errors[] = "L'email est requis.";
        if (empty($pseudo)) $errors[] = "Le pseudo est requis.";
        if (empty($password)) $errors[] = "Le mot de passe est requis.";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email n'est pas valide.";
        }

        if (strlen($password) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        }

        // Vérifier si l'email existe déjà
        $checkEmail = $pdo->prepare("SELECT user_id FROM user WHERE email = :email");
        $checkEmail->execute(['email' => $email]);
        if ($checkEmail->fetch()) {
            $errors[] = "Cet email est déjà utilisé par un autre utilisateur.";
        }

        // Vérifier si le pseudo existe déjà
        $checkPseudo = $pdo->prepare("SELECT user_id FROM user WHERE pseudo = :pseudo");
        $checkPseudo->execute(['pseudo' => $pseudo]);
        if ($checkPseudo->fetch()) {
            $errors[] = "Ce pseudo est déjà utilisé par un autre utilisateur.";
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: /pages/user_count.php");
            exit();
        }

        // Hasher le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Préparer la requête d'insertion
        $sql = "INSERT INTO user (nom, prenom, email, password, telephone, adresse, date_naissance, pseudo, role_id, role_covoiturage";

        // Gérer la photo si fournie
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $photo = file_get_contents($_FILES['photo']['tmp_name']);
                $sql .= ", photo";
            }
        }

        $sql .= ") VALUES (:nom, :prenom, :email, :password, :telephone, :adresse, :date_naissance, :pseudo, :role_id, :role_covoiturage";

        if ($photo !== null) {
            $sql .= ", :photo";
        }

        $sql .= ")";

        // Préparer et exécuter la requête
        $query = $pdo->prepare($sql);
        $params = [
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':telephone' => $telephone,
            ':adresse' => $adresse,
            ':date_naissance' => $date_naissance,
            ':pseudo' => $pseudo,
            ':role_id' => 2, // Rôle employé
            ':role_covoiturage' => $role_covoiturage
        ];

        if ($photo !== null) {
            $params[':photo'] = $photo;
        }

        $query->execute($params);

        // Récupérer l'ID du nouvel utilisateur créé
        $newUserId = $pdo->lastInsertId();

        // Message de succès
        $_SESSION['success'] = "Le compte employé pour {$prenom} {$nom} a été créé avec succès ! (ID: {$newUserId})";

        // Rediriger vers la page du compte
        header("Location: /pages/user_count.php");
        exit();
    } catch (PDOException $e) {
        // Gestion des erreurs
        $_SESSION['error'] = "Erreur lors de la création du compte employé : " . $e->getMessage();
        header("Location: /pages/user_count.php");
        exit();
    }
} else {
    // Si le formulaire n'a pas été soumis, rediriger vers la page du compte
    header("Location: /pages/user_count.php");
    exit();
}
