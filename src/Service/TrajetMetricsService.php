<?php

declare(strict_types=1);

namespace Ecoride\Ecf\Service;

use PDO;
use PDOException;

/**
 * Calcule distance, durée estimée et CO₂ économisé pour un covoiturage.
 * Utilise OSRM en priorité, avec repli sur la formule haversine.
 */
class TrajetMetricsService
{
    /** Émissions moyennes d'une voiture solo (kg CO₂ / km) — base « trajet évité » par passager */
    private const CO2_SOLO_KG_PAR_KM = 0.12;

    /** Facteur routier appliqué à la distance à vol d'oiseau (repli haversine) */
    private const FACTEUR_ROUTE_HAVERSINE = 1.3;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{distance_km: float, duree_minutes: int, co2_economise_kg: float}
     */
    public function calculateForCovoiturage(int $covoiturageId): array
    {
        $villeIds = $this->getOrderedVilleIds($covoiturageId);
        $coordinates = $this->getCoordinatesForVilleIds($villeIds);

        if (count($coordinates) < 2) {
            return [
                'distance_km' => 0.0,
                'duree_minutes' => 0,
                'co2_economise_kg' => 0.0,
            ];
        }

        $osrmMetrics = $this->fetchOsrmMetrics($coordinates);
        if ($osrmMetrics !== null) {
            return $this->buildResult(
                $osrmMetrics['distance_km'],
                $osrmMetrics['duree_minutes']
            );
        }

        $haversineKm = $this->calculateHaversineDistanceKm($coordinates) * self::FACTEUR_ROUTE_HAVERSINE;
        $estimatedMinutes = (int) max(1, round(($haversineKm / 80) * 60));

        return $this->buildResult($haversineKm, $estimatedMinutes);
    }

    public function updateCovoiturageMetrics(int $covoiturageId): bool
    {
        $metrics = $this->calculateForCovoiturage($covoiturageId);

        if ($metrics['distance_km'] <= 0) {
            return false;
        }

        require_once dirname(__DIR__, 2) . '/lib/duree_trajet.php';
        ensureDureeTrajetColumns($this->pdo);

        $stmt = $this->pdo->prepare("
            UPDATE covoiturage
            SET distance_km = :distance_km,
                co2_economise_kg = :co2_economise_kg,
                duree = :duree,
                duree_estimee = :duree_estimee
            WHERE covoiturage_id = :covoiturage_id
        ");

        return $stmt->execute([
            'distance_km' => round($metrics['distance_km'], 2),
            'co2_economise_kg' => round($metrics['co2_economise_kg'], 2),
            'duree' => $metrics['duree_minutes'],
            'duree_estimee' => $metrics['duree_minutes'],
            'covoiturage_id' => $covoiturageId,
        ]);
    }

    /**
     * @return int[]
     */
    public function getOrderedVilleIds(int $covoiturageId): array
    {
        $villeIds = [];

        try {
            $stmt = $this->pdo->prepare("
                SELECT ville_id
                FROM etape
                WHERE covoiturage_id = :covoiturage_id
                ORDER BY ordre ASC
            ");
            $stmt->execute(['covoiturage_id' => $covoiturageId]);
            $villeIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (PDOException) {
            $villeIds = [];
        }

        if (count($villeIds) >= 2) {
            return array_values(array_unique($villeIds));
        }

        $stmt = $this->pdo->prepare("
            SELECT ville_depart_id, ville_arrivee_id
            FROM covoiturage
            WHERE covoiturage_id = :covoiturage_id
            LIMIT 1
        ");
        $stmt->execute(['covoiturage_id' => $covoiturageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [];
        }

        $fallback = [];
        if (!empty($row['ville_depart_id'])) {
            $fallback[] = (int) $row['ville_depart_id'];
        }
        if (!empty($row['ville_arrivee_id'])) {
            $fallback[] = (int) $row['ville_arrivee_id'];
        }

        return $fallback;
    }

    /**
     * @param int[] $villeIds
     * @return array<int, array{lat: float, lng: float}>
     */
    private function getCoordinatesForVilleIds(array $villeIds): array
    {
        if ($villeIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($villeIds), '?'));
        $stmt = $this->pdo->prepare("
            SELECT ville_id, latitude, longitude
            FROM ville
            WHERE ville_id IN ({$placeholders})
              AND latitude IS NOT NULL
              AND longitude IS NOT NULL
        ");
        $stmt->execute($villeIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['ville_id']] = [
                'lat' => (float) $row['latitude'],
                'lng' => (float) $row['longitude'],
            ];
        }

        $coordinates = [];
        foreach ($villeIds as $villeId) {
            if (isset($byId[$villeId])) {
                $coordinates[] = $byId[$villeId];
            }
        }

        return $coordinates;
    }

    /**
     * @param array<int, array{lat: float, lng: float}> $coordinates
     * @return array{distance_km: float, duree_minutes: int}|null
     */
    private function fetchOsrmMetrics(array $coordinates): ?array
    {
        $parts = array_map(
            static fn(array $point): string => $point['lng'] . ',' . $point['lat'],
            $coordinates
        );
        $url = 'https://router.project-osrm.org/route/v1/driving/'
            . implode(';', $parts)
            . '?overview=false';

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'header' => "User-Agent: EcoRide-ECF/1.0\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['code'] ?? '') !== 'Ok') {
            return null;
        }

        $route = $data['routes'][0] ?? null;
        if (!is_array($route)) {
            return null;
        }

        $distanceMeters = (float) ($route['distance'] ?? 0);
        $durationSeconds = (float) ($route['duration'] ?? 0);

        if ($distanceMeters <= 0) {
            return null;
        }

        return [
            'distance_km' => $distanceMeters / 1000,
            'duree_minutes' => (int) max(1, round($durationSeconds / 60)),
        ];
    }

    /**
     * @param array<int, array{lat: float, lng: float}> $coordinates
     */
    private function calculateHaversineDistanceKm(array $coordinates): float
    {
        $total = 0.0;

        for ($i = 1, $count = count($coordinates); $i < $count; $i++) {
            $total += $this->haversineKm(
                $coordinates[$i - 1]['lat'],
                $coordinates[$i - 1]['lng'],
                $coordinates[$i]['lat'],
                $coordinates[$i]['lng']
            );
        }

        return $total;
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @return array{distance_km: float, duree_minutes: int, co2_economise_kg: float}
     */
    private function buildResult(float $distanceKm, int $durationMinutes): array
    {
        return [
            'distance_km' => round($distanceKm, 2),
            'duree_minutes' => max(1, $durationMinutes),
            'co2_economise_kg' => round($distanceKm * self::CO2_SOLO_KG_PAR_KM, 2),
        ];
    }
}
