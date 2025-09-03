<?php
try {
    $pdo = new PDO(
        "mysql:host=db;dbname=ecoride;charset=utf8mb4",
        "ecoride_user",
        "ecoride_pass"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Erreur de connexion : " . $e->getMessage());
}
