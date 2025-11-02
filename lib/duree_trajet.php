<?php
require_once __DIR__ . '/mongo.php';

/**
 * Démarre l'enregistrement d'un trajet dans MongoDB
 * @param int $userId ID de l'utilisateur
 * @param int $covoiturageId ID du covoiturage
 * @return string|null ID MongoDB du document créé ou null en cas d'erreur
 */
function demarrerTrajetMongo(int $userId, int $covoiturageId): ?string
{
    try {
        $col = getMongoCollection();

        $doc = [
            'user_id' => $userId,
            'covoiturage_id' => $covoiturageId,
            'start_time' => new MongoDB\BSON\UTCDateTime((new DateTime())->getTimestamp() * 1000),
            'stop_time' => null,
            'duration_seconds' => null,
            'status' => 'running',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];

        $result = $col->insertOne($doc);
        return (string)$result->getInsertedId();
    } catch (Exception $e) {
        error_log("Erreur démarrage trajet MongoDB: " . $e->getMessage());
        return null;
    }
}

/**
 * Arrête un trajet et calcule la durée
 * @param int $userId ID de l'utilisateur
 * @param int $covoiturageId ID du covoiturage
 * @return array|null ['duration_seconds' => int, 'duration_minutes' => int, 'mongo_id' => string] ou null
 */
function arreterTrajetMongo(int $userId, int $covoiturageId): ?array
{
    try {
        $col = getMongoCollection();

        // Trouver le dernier trajet "running" pour ce covoiturage et cet utilisateur
        $running = $col->findOne(
            [
                'user_id' => $userId,
                'covoiturage_id' => $covoiturageId,
                'status' => 'running'
            ],
            ['sort' => ['created_at' => -1]]
        );

        if (!$running) {
            error_log("Aucun trajet en cours trouvé pour user_id={$userId}, covoiturage_id={$covoiturageId}");
            return null;
        }

        $now = new DateTime();
        $nowUtc = new MongoDB\BSON\UTCDateTime($now->getTimestamp() * 1000);

        // Calcul de la durée en secondes
        $startTime = $running['start_time']->toDateTime();
        $durationSeconds = max(0, $now->getTimestamp() - $startTime->getTimestamp());
        $durationMinutes = (int)floor($durationSeconds / 60);

        // Mise à jour du document
        $col->updateOne(
            ['_id' => $running['_id']],
            [
                '$set' => [
                    'stop_time' => $nowUtc,
                    'duration_seconds' => $durationSeconds,
                    'duration_minutes' => $durationMinutes,
                    'status' => 'stopped',
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]
            ]
        );

        return [
            'duration_seconds' => $durationSeconds,
            'duration_minutes' => $durationMinutes,
            'mongo_id' => (string)$running['_id']
        ];
    } catch (Exception $e) {
        error_log("Erreur arrêt trajet MongoDB: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère l'historique des durées pour un utilisateur
 * @param int $userId ID de l'utilisateur
 * @param int|null $limit Nombre de résultats (défaut: 50)
 * @return array Liste des trajets avec leurs durées
 */
function getHistoriqueDurees(int $userId, ?int $limit = 50): array
{
    try {
        $col = getMongoCollection();

        $cursor = $col->find(
            ['user_id' => $userId, 'status' => 'stopped'],
            [
                'sort' => ['stop_time' => -1],
                'limit' => $limit
            ]
        );

        $results = [];
        foreach ($cursor as $doc) {
            $results[] = [
                'covoiturage_id' => $doc['covoiturage_id'],
                'start_time' => $doc['start_time']->toDateTime()->format('Y-m-d H:i:s'),
                'stop_time' => $doc['stop_time']->toDateTime()->format('Y-m-d H:i:s'),
                'duration_seconds' => $doc['duration_seconds'],
                'duration_minutes' => $doc['duration_minutes']
            ];
        }

        return $results;
    } catch (Exception $e) {
        error_log("Erreur récupération historique MongoDB: " . $e->getMessage());
        return [];
    }
}
