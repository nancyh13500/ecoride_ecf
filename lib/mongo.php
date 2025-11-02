<?php
// Vérifier si Composer est installé
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    error_log("ERREUR: vendor/autoload.php introuvable. Exécutez: composer install");
    throw new RuntimeException("Composer dependencies not installed. Run: composer install");
}
require_once $autoloadPath;

use MongoDB\Client;

/**
 * Crée et retourne un client MongoDB
 * @return Client
 */
function getMongoClient(): Client
{
    // URI de connexion MongoDB
    // Dans Docker: utilise le nom du service "mongodb"
    // En local WAMP: utilise localhost
    if (getenv('DOCKER_ENV') == '1') {
        $uri = getenv('MONGODB_URI') ?: 'mongodb://mongodb:27017';
    } else {
        $uri = getenv('MONGODB_URI') ?: 'mongodb://127.0.0.1:27017';
    }

    try {
        $client = new Client($uri);
        // Test de connexion
        $client->selectDatabase('admin')->command(['ping' => 1]);
        return $client;
    } catch (Exception $e) {
        error_log("Erreur connexion MongoDB: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Retourne une collection MongoDB
 * @param string $db Nom de la base de données (défaut: ecoride)
 * @param string $collection Nom de la collection (défaut: trajet_durees)
 * @return \MongoDB\Collection
 */
function getMongoCollection(string $db = 'ecoride', string $collection = 'trajet_durees'): \MongoDB\Collection
{
    $client = getMongoClient();
    return $client->selectCollection($db, $collection);
}
