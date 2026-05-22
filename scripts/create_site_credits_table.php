<?php

require_once __DIR__ . '/lib/pdo.php';

try {
    // Vérifier si la table existe déjà
    $checkTable = $pdo->query("SHOW TABLES LIKE 'site_credits'");

    if ($checkTable->rowCount() > 0) {
        echo "<h2 style='color: green;'>✅ La table 'site_credits' existe déjà !</h2>";
        echo "<p>Vous pouvez supprimer ce fichier maintenant.</p>";

        // Afficher le contenu actuel
        $stmt = $pdo->query("SELECT * FROM site_credits");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<h3>Contenu actuel :</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        // Créer la table
        $sql = "
        CREATE TABLE IF NOT EXISTS `site_credits` (
            `site_credits_id` int NOT NULL AUTO_INCREMENT,
            `total_credits` int NOT NULL DEFAULT 0,
            `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`site_credits_id`)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
        ";

        $pdo->exec($sql);

        // Insérer un enregistrement initial
        $insertSql = "INSERT INTO `site_credits` (`total_credits`) VALUES (0)";
        $pdo->exec($insertSql);

        echo "<h2 style='color: green;'>✅ Table 'site_credits' créée avec succès !</h2>";
        echo "<p>Vous pouvez maintenant supprimer ce fichier.</p>";

        // Afficher le contenu
        $stmt = $pdo->query("SELECT * FROM site_credits");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<h3>Contenu :</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Erreur :</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Détails :</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
