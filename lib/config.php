<?php
// Configuration de la base de données selon l'environnement
$isDocker = getenv('DOCKER_ENV') || file_exists('/.dockerenv');

if ($isDocker) {
    // Configuration Docker
    define('DB_HOST', 'db');
    define('DB_NAME', 'ecoride');
    define('DB_USER', 'ecoride_user');
    define('DB_PASS', 'ecoride_pass');
} else {
    // Configuration locale (WAMP/XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ecoride');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// Paramètres de connexion PDO
define('DB_DSN', "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4");
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
]);
