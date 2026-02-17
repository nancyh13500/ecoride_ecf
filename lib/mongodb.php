<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use MongoDB\Client;
use MongoDB\Exception\Exception as MongoDBException;

// Fonction helper pour récupérer les variables d'environnement MongoDB
function getMongoEnvVar($key, $default = '')
{
    // Priorité : $_ENV > getenv() > $default
    if (isset($_ENV[$key]) && !empty($_ENV[$key])) {
        return $_ENV[$key];
    }
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// Configuration MongoDB selon l'environnement
$isDocker = getMongoEnvVar('DOCKER_ENV') || file_exists('/.dockerenv');

if ($isDocker) {
    // Configuration Docker
    define('MONGO_HOST', getMongoEnvVar('MONGO_HOST', 'mongodb'));
    define('MONGO_PORT', getMongoEnvVar('MONGO_PORT', '27017'));
    define('MONGO_DB', getMongoEnvVar('MONGO_DB', 'ecoride'));
    define('MONGO_USER', getMongoEnvVar('MONGO_USER', 'mongodb_user'));
    define('MONGO_PASS', getMongoEnvVar('MONGO_PASS', 'mongodb_pass'));
} else {
    // Configuration production ou locale (non-Docker)
    // Les variables d'environnement sont prioritaires
    define('MONGO_HOST', getMongoEnvVar('MONGO_HOST', 'localhost'));
    define('MONGO_PORT', getMongoEnvVar('MONGO_PORT', '27017'));
    define('MONGO_DB', getMongoEnvVar('MONGO_DB', 'ecoride'));
    define('MONGO_USER', getMongoEnvVar('MONGO_USER', ''));
    define('MONGO_PASS', getMongoEnvVar('MONGO_PASS', ''));
}

// Connexion MongoDB
$mongoClient = null;
$mongoDB = null;

try {
    // Construction de la chaîne de connexion
    if (!empty(MONGO_USER) && !empty(MONGO_PASS)) {
        $connectionString = sprintf(
            'mongodb://%s:%s@%s:%s/%s?authSource=admin',
            MONGO_USER,
            MONGO_PASS,
            MONGO_HOST,
            MONGO_PORT,
            MONGO_DB
        );
    } else {
        $connectionString = sprintf(
            'mongodb://%s:%s/%s',
            MONGO_HOST,
            MONGO_PORT,
            MONGO_DB
        );
    }

    $mongoClient = new Client($connectionString);
    $mongoDB = $mongoClient->selectDatabase(MONGO_DB);

    // Test de connexion
    $mongoDB->command(['ping' => 1]);
} catch (MongoDBException $e) {
    error_log("Erreur de connexion MongoDB : " . $e->getMessage());
    // Ne pas bloquer l'application si MongoDB n'est pas disponible
    $mongoClient = null;
    $mongoDB = null;
}

/**
 * Récupère la collection MongoDB pour les avis
 * @return MongoDB\Collection|null
 */
function getAvisCollection()
{
    global $mongoDB;
    if ($mongoDB === null) {
        return null;
    }
    // Collection hiérarchique : avis
    return $mongoDB->selectCollection('avis');
}
