<?php

/**
 * Connexion PDO partagée — délègue au singleton Database (couche Core).
 */

require_once __DIR__ . '/config.php';

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoloadPath)) {
    die('❌ Dépendances manquantes : exécutez <code>composer install</code> à la racine du projet.');
}
require_once $autoloadPath;

use Ecoride\Ecf\Core\Database;

$pdo = Database::getInstance();
