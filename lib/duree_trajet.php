<?php

declare(strict_types=1);

/**
 * Utilitaires pour la durée des trajets (estimée OSRM ou mesurée au démarrage/arrêt).
 */

/**
 * Ajoute les colonnes nécessaires au suivi du temps réel si absentes.
 */
function ensureDureeTrajetColumns(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $columns = [
        'debut_trajet_at' => 'DATETIME DEFAULT NULL',
        'duree_estimee' => 'INT DEFAULT NULL',
    ];

    foreach ($columns as $column => $definition) {
        try {
            $pdo->query("SELECT {$column} FROM covoiturage LIMIT 1");
        } catch (PDOException) {
            try {
                $pdo->exec("ALTER TABLE covoiturage ADD COLUMN {$column} {$definition}");
            } catch (PDOException) {
                // Colonne déjà ajoutée ou droits insuffisants
            }
        }
    }

    // Rétrocompatibilité : recopier duree OSRM vers duree_estimee si vide
    try {
        $pdo->exec("
            UPDATE covoiturage
            SET duree_estimee = duree
            WHERE duree_estimee IS NULL AND duree IS NOT NULL AND duree > 0
        ");
    } catch (PDOException) {
        // Ignorer si la colonne n'existe pas encore
    }
}

/**
 * Formate une durée en minutes lisible (ex. 1h 25min).
 */
function formaterDureeMinutes(int $minutes): string
{
    if ($minutes < 60) {
        return $minutes . 'min';
    }

    $heures = intdiv($minutes, 60);
    $mins = $minutes % 60;

    return $mins > 0 ? "{$heures}h {$mins}min" : "{$heures}h";
}

/**
 * Retourne la durée estimée en minutes (colonne dédiée ou repli sur duree).
 *
 * @param array<string, mixed> $trajet
 */
function getDureeEstimeeMinutes(array $trajet): ?int
{
    if (isset($trajet['duree_estimee']) && $trajet['duree_estimee'] !== null && (int) $trajet['duree_estimee'] > 0) {
        return (int) $trajet['duree_estimee'];
    }

    if ((int) ($trajet['statut'] ?? 0) !== 3 && !empty($trajet['duree'])) {
        return (int) $trajet['duree'];
    }

    return null;
}

/**
 * Enregistre le début réel d'un trajet (statut en cours).
 */
function demarrerChronometreTrajet(PDO $pdo, int $trajetId, int $userId): bool
{
    ensureDureeTrajetColumns($pdo);

    $stmt = $pdo->prepare("
        UPDATE covoiturage
        SET statut = 2,
            debut_trajet_at = NOW()
        WHERE covoiturage_id = :id AND user_id = :user_id AND statut = 1
    ");

    return $stmt->execute([
        'id' => $trajetId,
        'user_id' => $userId,
    ]);
}

/**
 * Enregistre la durée réelle d'un trajet terminé par le chauffeur.
 */
function enregistrerDureeTrajet(PDO $pdo, int $trajetId, int $userId, int $dureeMinutes): bool
{
    if ($trajetId <= 0 || $userId <= 0 || $dureeMinutes <= 0) {
        return false;
    }

    ensureDureeTrajetColumns($pdo);

    $stmt = $pdo->prepare("
        UPDATE covoiturage
        SET duree = :duree,
            debut_trajet_at = NULL
        WHERE covoiturage_id = :id AND user_id = :user_id
    ");

    return $stmt->execute([
        'duree' => $dureeMinutes,
        'id' => $trajetId,
        'user_id' => $userId,
    ]);
}
