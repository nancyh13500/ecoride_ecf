<?php
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '.ecoride',
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
