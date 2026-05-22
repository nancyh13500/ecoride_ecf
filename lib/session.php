<?php

/**
 * Façade procédurale de compatibilité — délègue à Ecoride\Ecf\Core\Session.
 */

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoloadPath)) {
    die('❌ Dépendances manquantes : exécutez <code>composer install</code> à la racine du projet.');
}
require_once $autoloadPath;

use Ecoride\Ecf\Core\Session;

function ecoride_session(): Session
{
    static $instance = null;
    if ($instance === null) {
        $instance = new Session();
    }
    return $instance;
}

function isUserConnected(): bool
{
    return ecoride_session()->isUserConnected();
}

function requireLogin(): void
{
    ecoride_session()->requireLogin();
}

function generateCSRFToken(): string
{
    return ecoride_session()->generateCSRFToken();
}

function validateCSRFToken(string $token): bool
{
    return ecoride_session()->validateCSRFToken($token);
}

function verifyCSRFToken(string $tokenName = 'csrf_token'): void
{
    ecoride_session()->verifyCSRFToken($tokenName);
}

function csrfField(string $tokenName = 'csrf_token'): void
{
    ecoride_session()->csrfField($tokenName);
}

// Démarre la session dès l'inclusion (comportement attendu par header.php et les pages)
ecoride_session();
