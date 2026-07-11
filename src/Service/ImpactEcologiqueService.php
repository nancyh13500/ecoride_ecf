<?php

declare(strict_types=1);

namespace Ecoride\Ecf\Service;

use PDO;
use PDOException;

/**
 * Agrège les indicateurs écologiques affichés sur l'accueil et le back-office.
 */
class ImpactEcologiqueService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{
     *     trajets_termines: int,
     *     km_partages: float,
     *     co2_evite_kg: float,
     *     trajets_ecologiques: int,
     *     passagers_transportes: int,
     *     utilisateurs_inscrits: int
     * }
     */
    public function getStatistiquesGlobales(): array
    {
        return [
            'trajets_termines' => $this->countTrajetsTermines(),
            'km_partages' => $this->sumKmPartages(),
            'co2_evite_kg' => $this->sumCo2Evite(),
            'trajets_ecologiques' => $this->countTrajetsVehiculeElectrique(),
            'passagers_transportes' => $this->countPassagersTransportes(),
            'utilisateurs_inscrits' => $this->countUtilisateurs(),
        ];
    }

    private function countTrajetsTermines(): int
    {
        try {
            return (int) $this->pdo->query("SELECT COUNT(*) FROM covoiturage WHERE statut = 3")->fetchColumn();
        } catch (PDOException) {
            return 0;
        }
    }

    private function sumKmPartages(): float
    {
        try {
            $value = $this->pdo->query("
                SELECT COALESCE(SUM(distance_km), 0)
                FROM covoiturage
                WHERE statut = 3 AND distance_km IS NOT NULL AND distance_km > 0
            ")->fetchColumn();

            return round((float) $value, 1);
        } catch (PDOException) {
            return 0.0;
        }
    }

    private function sumCo2Evite(): float
    {
        $table = $this->resolveReservationsTable();
        if ($table === null) {
            return $this->sumCo2EviteSansPassagers();
        }

        try {
            $sql = "
                SELECT COALESCE(SUM(
                    c.co2_economise_kg * GREATEST(COALESCE(r.nb_passagers, 1), 1)
                ), 0) AS total
                FROM covoiturage c
                LEFT JOIN (
                    SELECT covoiturage_id, SUM(nb_places_reservees) AS nb_passagers
                    FROM {$table}
                    WHERE statut = 'confirmée'
                    GROUP BY covoiturage_id
                ) r ON r.covoiturage_id = c.covoiturage_id
                WHERE c.statut = 3
                  AND c.co2_economise_kg IS NOT NULL
                  AND c.co2_economise_kg > 0
            ";
            $value = $this->pdo->query($sql)->fetchColumn();

            return round((float) $value, 2);
        } catch (PDOException) {
            return $this->sumCo2EviteSansPassagers();
        }
    }

    private function sumCo2EviteSansPassagers(): float
    {
        try {
            $value = $this->pdo->query("
                SELECT COALESCE(SUM(co2_economise_kg), 0)
                FROM covoiturage
                WHERE statut = 3 AND co2_economise_kg IS NOT NULL AND co2_economise_kg > 0
            ")->fetchColumn();

            return round((float) $value, 2);
        } catch (PDOException) {
            return 0.0;
        }
    }

    private function countTrajetsVehiculeElectrique(): int
    {
        try {
            return (int) $this->pdo->query("
                SELECT COUNT(*)
                FROM covoiturage c
                JOIN voiture v ON v.voiture_id = c.voiture_id
                LEFT JOIN energie e ON e.energie_id = v.energie_id
                WHERE LOWER(COALESCE(e.libelle, v.energie, '')) LIKE '%lectrique%'
            ")->fetchColumn();
        } catch (PDOException) {
            return 0;
        }
    }

    private function countPassagersTransportes(): int
    {
        $table = $this->resolveReservationsTable();
        if ($table === null) {
            return 0;
        }

        try {
            return (int) $this->pdo->query("
                SELECT COALESCE(SUM(r.nb_places_reservees), 0)
                FROM {$table} r
                JOIN covoiturage c ON c.covoiturage_id = r.covoiturage_id
                WHERE r.statut = 'confirmée' AND c.statut = 3
            ")->fetchColumn();
        } catch (PDOException) {
            return 0;
        }
    }

    private function countUtilisateurs(): int
    {
        try {
            return (int) $this->pdo->query("
                SELECT COUNT(*) FROM user WHERE role_id = 3
            ")->fetchColumn();
        } catch (PDOException) {
            return 0;
        }
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
}
