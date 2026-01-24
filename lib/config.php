<?php
// Chargement des variables d'environnement depuis .env si disponible
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    // Utiliser Dotenv si disponible (composer install requis)
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        try {
            $dotenvClass = 'Dotenv\Dotenv';
            if (class_exists($dotenvClass)) {
                $dotenv = call_user_func([$dotenvClass, 'createImmutable'], __DIR__ . '/..');
                $dotenv->load();
            }
        } catch (Exception $e) {
            // Si dotenv n'est pas disponible, on continue avec getenv()
            error_log("Dotenv non disponible, utilisation de getenv() : " . $e->getMessage());
        }
    }

    // Chargement manuel du .env si dotenv n'est pas disponible ou a échoué
    if (!class_exists('Dotenv\Dotenv')) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Ignorer les commentaires
            }
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // Supprimer les guillemets si présents
                $value = trim($value, '"\'');
                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
    }
}

// Fonction helper pour récupérer les variables d'environnement
function getEnvVar($key, $default = '')
{
    // Priorité : $_ENV > getenv() > $default
    if (isset($_ENV[$key]) && !empty($_ENV[$key])) {
        return $_ENV[$key];
    }
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// Configuration de la base de données selon l'environnement
$isDocker = getEnvVar('DOCKER_ENV') || file_exists('/.dockerenv');

if ($isDocker) {
    // Configuration Docker
    define('DB_HOST', getEnvVar('DB_HOST', 'db'));
    define('DB_NAME', getEnvVar('DB_NAME', 'ecoride'));
    define('DB_USER', getEnvVar('DB_USER', 'ecoride_user'));
    define('DB_PASS', getEnvVar('DB_PASS', 'ecoride_pass'));
} else {
    // Configuration production ou locale (non-Docker)
    // Les variables d'environnement sont prioritaires
    define('DB_HOST', getEnvVar('DB_HOST', 'localhost'));
    define('DB_NAME', getEnvVar('DB_NAME', 'ecoride'));
    define('DB_USER', getEnvVar('DB_USER', 'root'));
    define('DB_PASS', getEnvVar('DB_PASS', ''));
}

// Paramètres de connexion PDO
// Ajout du port pour forcer une connexion TCP/IP (évite l'erreur "No such file or directory")
$dbPort = $isDocker ? '3306' : '3306';
define('DB_DSN', "mysql:host=" . DB_HOST . ";port=" . $dbPort . ";dbname=" . DB_NAME . ";charset=utf8mb4");
$dbOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];
// Désactiver la vérification SSL pour MySQL dans Docker (certificat auto-signé)
if ($isDocker) {
    $dbOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    $dbOptions[PDO::MYSQL_ATTR_SSL_CA] = '';
}
define('DB_OPTIONS', $dbOptions);
