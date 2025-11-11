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
        header('Location: ../login.php');
        exit();
    }
}
/** génère ou récupère un token CSRF pour la session
 * @return string */

function generateCSRFToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** valide un token csrf 
@param string
@return bool
 */

function validateCSRFToken(string $token): bool
{
    if (!isset($_SESSION['csrf_token']))
        return false;
    // comparaison sécurisée
    return hash_equals($_SESSION['csrf_token'], $token);
}


/** vérifie et valide le token csrf depuis post ou get
@param string
@return void */

function verufyCSRFToken(string $tokenName = 'csrf_token'): void
{
    $token = $_POST[$tokenName] ?? $_GET[$tokenName] ?? '';

    if (empty($token) || !validateCSRFToken($token)) {
        $_SESSION['error'] = "Token CSRF invalide. Veuillez résessayer.";
        header('location: /index.php');
        exit();
    }
}

/**affiche le champ hidden avec le token
@param string
@return void*/

function csrfField(string $tokenName = 'csrf_token'): void
{
    $token = gerenrateCSRFToken();
    echo '<input type="hidden" name="' . $tokenName . '" value="' . $token . '">';
}
