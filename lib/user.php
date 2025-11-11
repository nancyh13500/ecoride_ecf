<?php

function verifyUserLoginPassword(PDO $pdo, string $email, string $password): bool|array
{
    $userQuery = $pdo->prepare("SELECT * FROM user WHERE email = :email");
    $userQuery->bindValue(':email', $email, PDO::PARAM_STR);
    $userQuery->execute();
    //fetch() nous permet de récupérer une seule ligne
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Refuser l'accès si le compte est suspendu (si la colonne existe)
        if (isset($user['suspended']) && (int)$user['suspended'] === 1) {
            return false;
        }
        // verif ok
        return $user;
    } else {
        // email ou mdp incorrect: on retourne false
        return false;
    }
}
