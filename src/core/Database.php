<?php

namespace Ecoride\Ecf\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Empêche l'instanciation directe (pattern Singleton)
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                require_once __DIR__ . '/../../lib/config.php';
                self::$instance = new PDO(DB_DSN, DB_USER, DB_PASS, DB_OPTIONS);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("❌ Erreur de connexion : " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
