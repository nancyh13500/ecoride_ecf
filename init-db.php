<?php
/**
 * Script d'initialisation de la base de données
 * Vérifie et crée les tables si elles n'existent pas
 */

require_once __DIR__ . '/lib/config.php';

function initDatabase() {
    try {
        // Connexion à MySQL sans spécifier la base de données
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        // Créer la base de données si elle n'existe pas
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE " . DB_NAME);

        // Lire le fichier SQL
        $sqlFile = __DIR__ . '/ecoride.sql';
        
        if (!file_exists($sqlFile)) {
            error_log("⚠️ Fichier SQL non trouvé: $sqlFile");
            return false;
        }

        $sql = file_get_contents($sqlFile);
        
        // Supprimer les commentaires et les lignes vides
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Diviser en requêtes individuelles
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^(SET|START|COMMIT|USE)/i', $stmt);
            }
        );

        // Exécuter chaque requête
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Ignorer les erreurs de table déjà existante
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate') === false) {
                        error_log("Erreur SQL: " . $e->getMessage());
                    }
                }
            }
        }

        return true;
    } catch (PDOException $e) {
        error_log("❌ Erreur d'initialisation de la base de données: " . $e->getMessage());
        return false;
    }
}

// Exécuter l'initialisation
if (php_sapi_name() === 'cli' || isset($_GET['init'])) {
    echo "🔄 Initialisation de la base de données...\n";
    if (initDatabase()) {
        echo "✅ Base de données initialisée avec succès!\n";
    } else {
        echo "❌ Erreur lors de l'initialisation de la base de données.\n";
    }
}

