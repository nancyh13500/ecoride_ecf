<?php
// Démarrer la session AVANT tout output
ob_start();
session_start();

require_once __DIR__ . '/vendor/autoload.php';

use Ecoride\Ecf\Core\Database;
use Ecoride\Ecf\Core\Session;
use Ecoride\Ecf\Models\User;

// Test de la connexion Database
try {
    $pdo = Database::getInstance();
    echo "✅ Connexion Database OK<br>";
} catch (Exception $e) {
    echo "❌ Erreur Database : " . $e->getMessage() . "<br>";
}

// Test de Session
try {
    $session = new Session();
    echo "✅ Session créée<br>";

    // Test du token CSRF
    $token = $session->generateCSRFToken();
    echo "✅ Token CSRF généré : " . substr($token, 0, 10) . "...<br>";

    // Test de validation
    $isValid = $session->validateCSRFToken($token);
    echo $isValid ? "✅ Validation CSRF OK<br>" : "❌ Validation CSRF échouée<br>";
} catch (Exception $e) {
    echo "❌ Erreur Session : " . $e->getMessage() . "<br>";
}

// Test du modèle User
try {
    $userModel = new User();
    echo "✅ Modèle User créé<br>";
} catch (Exception $e) {
    echo "❌ Erreur User : " . $e->getMessage() . "<br>";
}

echo "<br>🎉 Tests terminés !";
