<?php
ob_start();
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'httponly' => true
]);

session_start();

function isUserConnected(): bool
{
    return isset($_SESSION['user']);
}

function requireLogin(): void
{
    if (!isUserConnected()) {
        $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
        header('Location: /login.php');
        exit();
    }
}
