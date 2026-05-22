<?php
/**
 * Script de test des connexions MySQL et MongoDB
 * Usage: php test-connections.php
 */

echo "========================================\n";
echo "Test des connexions EcoRide\n";
echo "========================================\n\n";

// Test MySQL
echo "[1/2] Test de la connexion MySQL...\n";
try {
    require_once __DIR__ . '/../lib/pdo.php';
    echo "✅ MySQL : Connexion réussie\n";
    echo "   - Host: " . DB_HOST . "\n";
    echo "   - Database: " . DB_NAME . "\n";
    echo "   - User: " . DB_USER . "\n";
    
    // Test d'une requête simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   - Nombre d'utilisateurs: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "❌ MySQL : Erreur - " . $e->getMessage() . "\n";
}

echo "\n";

// Test MongoDB
echo "[2/2] Test de la connexion MongoDB...\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/../lib/mongodb.php';
    
    $collection = getAvisCollection();
    if ($collection !== null) {
        echo "✅ MongoDB : Connexion réussie\n";
        echo "   - Collection: " . $collection->getCollectionName() . "\n";
        
        // Compter les documents
        $count = $collection->countDocuments([]);
        echo "   - Nombre d'avis: " . $count . "\n";
    } else {
        echo "❌ MongoDB : Collection est null (MongoDB non disponible)\n";
    }
} catch (Exception $e) {
    echo "❌ MongoDB : Erreur - " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "Tests terminés\n";
echo "========================================\n";

