<?php

/**
 * Bootstrap applicatif — point d'entrée commun pour les pages et scripts.
 * Charge l'autoload, la configuration et les services de base (session, PDO).
 */

declare(strict_types=1);

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

require_once PROJECT_ROOT . '/vendor/autoload.php';
require_once PROJECT_ROOT . '/lib/config.php';
require_once PROJECT_ROOT . '/lib/session.php';

use Ecoride\Ecf\Core\Database;

/** @var PDO $pdo Connexion MySQL partagée (compatibilité avec l'existant) */
$pdo = Database::getInstance();
