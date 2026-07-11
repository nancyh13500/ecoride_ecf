<?php

declare(strict_types=1);

namespace Ecoride\Ecf\Service;

use PDO;
use PDOException;

class DashboardService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<string, int>
     */
    public function getStats(int $userId): array
    {
        return [
            'trajets_a_venir_chauffeur' => count($this->getUpcomingTripsAsDriver($userId, 100)),
            'trajets_a_venir_passager' => count($this->getUpcomingTripsAsPassenger($userId, 100)),
            'avis_en_attente' => $this->countPendingReviews($userId),
            'reservations_en_attente' => $this->countPendingReservationsAsDriver($userId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingTripsAsDriver(int $userId, int $limit = 5): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT covoiturage_id, lieu_depart, lieu_arrivee, date_depart, heure_depart,
                       nb_place, prix_personne, statut, distance_km, co2_economise_kg
                FROM covoiturage
                WHERE user_id = :user_id
                  AND statut IN (1, 2)
                  AND (date_depart > CURDATE() OR (date_depart = CURDATE() AND heure_depart >= CURTIME()))
                ORDER BY date_depart ASC, heure_depart ASC
                LIMIT :limit
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingTripsAsPassenger(int $userId, int $limit = 5): array
    {
        $table = $this->resolveReservationsTable();
        if ($table === null) {
            return [];
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT c.covoiturage_id, c.lieu_depart, c.lieu_arrivee, c.date_depart, c.heure_depart,
                       c.prix_personne, c.statut AS trajet_statut,
                       r.reservation_id, r.statut AS reservation_statut, r.prix_total
                FROM {$table} r
                JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
                WHERE r.user_id = :user_id
                  AND r.statut != 'annulée'
                  AND c.statut IN (1, 2)
                  AND (c.date_depart > CURDATE() OR (c.date_depart = CURDATE() AND c.heure_depart >= CURTIME()))
                ORDER BY c.date_depart ASC, c.heure_depart ASC
                LIMIT :limit
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCreditHistory(int $userId, int $limit = 10): array
    {
        try {
            if (!$this->tableExists('credit_transactions')) {
                return [];
            }

            $stmt = $this->pdo->prepare("
                SELECT transaction_id, montant, solde_apres, type_operation,
                       reference_id, description, date_transaction
                FROM credit_transactions
                WHERE user_id = :user_id
                ORDER BY date_transaction DESC, transaction_id DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }

    public function countPendingReviews(int $userId): int
    {
        return count($this->getTripsPendingReview($userId, 100));
    }

    /**
     * Trajets terminés sans avis déposé par l'utilisateur.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTripsPendingReview(int $userId, int $limit = 5): array
    {
        $completed = $this->getCompletedTripsForUser($userId);
        if ($completed === []) {
            return [];
        }

        $reviewedIds = $this->getReviewedCovoiturageIds($userId);
        $pending = array_values(array_filter(
            $completed,
            static fn(array $trip): bool => !isset($reviewedIds[(int) $trip['covoiturage_id']])
        ));

        return array_slice($pending, 0, $limit);
    }

    public function countPendingReservationsAsDriver(int $userId): int
    {
        $table = $this->resolveReservationsTable();
        if ($table === null) {
            return 0;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) AS total
                FROM {$table} r
                JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
                WHERE c.user_id = :user_id
                  AND r.statut = 'En attente'
            ");
            $stmt->execute(['user_id' => $userId]);

            return (int) ($stmt->fetchColumn() ?: 0);
        } catch (PDOException) {
            return 0;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCompletedTripsForUser(int $userId): array
    {
        $trips = [];
        $seen = [];

        try {
            $driverStmt = $this->pdo->prepare("
                SELECT covoiturage_id, lieu_depart, lieu_arrivee, date_depart, heure_depart, 'Chauffeur' AS role
                FROM covoiturage
                WHERE user_id = :user_id AND statut = 3
                ORDER BY date_depart DESC, heure_depart DESC
                LIMIT 30
            ");
            $driverStmt->execute(['user_id' => $userId]);
            foreach ($driverStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $id = (int) $row['covoiturage_id'];
                $seen[$id] = true;
                $trips[] = $row;
            }
        } catch (PDOException) {
            // Ignorer
        }

        $table = $this->resolveReservationsTable();
        if ($table !== null) {
            try {
                $passengerStmt = $this->pdo->prepare("
                    SELECT c.covoiturage_id, c.lieu_depart, c.lieu_arrivee, c.date_depart, c.heure_depart,
                           'Passager' AS role
                    FROM {$table} r
                    JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
                    WHERE r.user_id = :user_id
                      AND r.statut = 'confirmée'
                      AND c.statut = 3
                    ORDER BY c.date_depart DESC, c.heure_depart DESC
                    LIMIT 30
                ");
                $passengerStmt->execute(['user_id' => $userId]);
                foreach ($passengerStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $id = (int) $row['covoiturage_id'];
                    if (!isset($seen[$id])) {
                        $trips[] = $row;
                    }
                }
            } catch (PDOException) {
                // Ignorer
            }
        }

        return $trips;
    }

    /**
     * @return array<int, true>
     */
    private function getReviewedCovoiturageIds(int $userId): array
    {
        $reviewed = [];

        if (!function_exists('getAvisCollection')) {
            require_once dirname(__DIR__, 2) . '/lib/mongodb.php';
        }

        $collection = getAvisCollection();
        if ($collection === null) {
            return $reviewed;
        }

        try {
            $cursor = $collection->find(['user_id' => $userId]);
            foreach ($cursor as $doc) {
                if (isset($doc['covoiturage_id'])) {
                    $reviewed[(int) $doc['covoiturage_id']] = true;
                }
            }
        } catch (\Throwable) {
            return $reviewed;
        }

        return $reviewed;
    }

    private function resolveReservationsTable(): ?string
    {
        foreach (['reservations', 'reservation'] as $tableName) {
            try {
                $this->pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");

                return $tableName;
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function tableExists(string $table): bool
    {
        try {
            $this->pdo->query("SELECT 1 FROM {$table} LIMIT 1");

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
