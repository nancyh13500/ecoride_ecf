<?php

/**
 * Bootstrap pour les tests PHPUnit
 * Configure l'environnement de test
 */

// Définir le chemin de base du projet
define('TEST_ROOT', __DIR__ . '/..');

// Charger l'autoloader de Composer
require_once TEST_ROOT . '/vendor/autoload.php';

// Définir l'environnement de test
define('TEST_ENV', true);

// Désactiver les sorties HTML pendant les tests
ob_start();
