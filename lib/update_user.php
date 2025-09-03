<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/pdo.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Récupérer les données du formulaire
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $telephone = $_POST['telephone'];
        $adresse = trim($_POST['adresse'] ?? '');
        $cp = trim($_POST['cp'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        // Concaténer adresse complète si CP/Ville fournis
        $adresseComplete = $adresse;
        if ($cp !== '' || $ville !== '') {
            $adresseComplete = trim($adresse . ', ' . $cp . ' ' . $ville);
        }
        $date_naissance = $_POST['date_naissance'];
        $pseudo = $_POST['pseudo'];
        $role_covoiturage = $_POST['role_covoiturage'];
        $user_id = $_SESSION['user']['user_id'];

        // Préparer la requête de base
        $sql = "UPDATE user SET 
                nom = :nom,
                prenom = :prenom,
                email = :email,
                telephone = :telephone,
                adresse = :adresse,
                date_naissance = :date_naissance,
                pseudo = :pseudo,
                role_covoiturage = :role_covoiturage";

        // Gérer le mot de passe si fourni
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql .= ", password = :password";
        }

        // Gérer la photo si fournie
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $photo = file_get_contents($_FILES['photo']['tmp_name']);
                $sql .= ", photo = :photo";
            }
        }

        // Gérer la suppression de la photo
        if (isset($_POST['delete_photo']) && $_POST['delete_photo'] == '1') {
            $sql .= ", photo = NULL";
        }

        $sql .= " WHERE user_id = :user_id";

        // Préparer et exécuter la requête
        $query = $pdo->prepare($sql);
        $params = [
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':telephone' => $telephone,
            ':adresse' => $adresseComplete,
            ':date_naissance' => $date_naissance,
            ':pseudo' => $pseudo,
            ':role_covoiturage' => $role_covoiturage,
            ':user_id' => $user_id
        ];

        // Ajouter le mot de passe aux paramètres si fourni
        if (!empty($_POST['password'])) {
            $params[':password'] = $password;
        }

        // Ajouter la photo aux paramètres si fournie
        if (isset($photo)) {
            $params[':photo'] = $photo;
        }

        $query->execute($params);

        // Mettre à jour la session avec les nouvelles informations
        $sessionUpdate = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone,
            'adresse' => $adresse,
            'date_naissance' => $date_naissance,
            'pseudo' => $pseudo,
            'role_covoiturage' => $role_covoiturage
        ];

        // Ajouter la photo à la session si fournie
        if (isset($photo)) {
            $sessionUpdate['photo'] = $photo;
        }

        // Supprimer la photo de la session si demandé
        if (isset($_POST['delete_photo']) && $_POST['delete_photo'] == '1') {
            $sessionUpdate['photo'] = null;
        }

        $_SESSION['user'] = array_merge($_SESSION['user'], $sessionUpdate);

        // Rediriger avec un message de succès
        $_SESSION['success'] = "Vos informations ont été mises à jour avec succès.";
        header("Location: /pages/user_count.php");
        exit();
    } catch (PDOException $e) {
        // Gestion des erreurs
        // Si l'erreur est une contrainte de colonne inconnue
        if ($e->getCode() == '42S22') {
            try {
                // Ajouter la colonne
                $pdo->exec("ALTER TABLE `user` ADD `role_covoiturage` VARCHAR(20) NOT NULL DEFAULT 'Passager'");
                // Relancer l'update
                $query->execute($params);
                // Mettre à jour la session
                $_SESSION['user']['role_covoiturage'] = $role_covoiturage;
                // Rediriger avec succès
                $_SESSION['success'] = "Vos informations ont été mises à jour avec succès.";
                header("Location: /pages/user_count.php");
                exit();
            } catch (PDOException $e2) {
                $_SESSION['error'] = "Erreur lors de la création de la colonne : " . $e2->getMessage();
                header("Location: /pages/user_count.php");
                exit();
            }
        }
        $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de vos informations : " . $e->getMessage();
        header("Location: /pages/user_count.php");
        exit();
    }
} else {
    // Si le formulaire n'a pas été soumis, rediriger vers la page du compte
    header("Location: /pages/user_count.php");
    exit();
}
