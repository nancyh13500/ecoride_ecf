<?php

declare(strict_types=1);

use Ecoride\Ecf\Service\TrajetMetricsService;
use PHPUnit\Framework\TestCase;

/**
 * Tests d'intégration TrajetMetricsService (calcul haversine / mise à jour BDD).
 */
class TrajetMetricsServiceIntegrationTest extends TestCase
{
    private PDO $pdo;
    private TrajetMetricsService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE ville (
                ville_id INTEGER PRIMARY KEY,
                nom TEXT NOT NULL,
                latitude REAL,
                longitude REAL
            );
            CREATE TABLE covoiturage (
                covoiturage_id INTEGER PRIMARY KEY,
                ville_depart_id INTEGER,
                ville_arrivee_id INTEGER,
                distance_km REAL,
                co2_economise_kg REAL,
                duree INTEGER
            );
            CREATE TABLE etape (
                etape_id INTEGER PRIMARY KEY AUTOINCREMENT,
                covoiturage_id INTEGER,
                ville_id INTEGER,
                ordre INTEGER
            );
        ");

        // Paris → Lyon (approx.)
        $this->pdo->exec("
            INSERT INTO ville (ville_id, nom, latitude, longitude) VALUES
            (1, 'Paris', 48.8566, 2.3522),
            (2, 'Lyon', 45.7640, 4.8357);
            INSERT INTO covoiturage (covoiturage_id, ville_depart_id, ville_arrivee_id)
            VALUES (1, 1, 2);
            INSERT INTO etape (covoiturage_id, ville_id, ordre) VALUES
            (1, 1, 1),
            (1, 2, 2);
        ");

        $this->service = new TrajetMetricsService($this->pdo);
    }

    public function testGetOrderedVilleIdsFromEtapes(): void
    {
        $ids = $this->service->getOrderedVilleIds(1);
        $this->assertSame([1, 2], $ids);
    }

    public function testCalculateForCovoiturageReturnsPositiveMetrics(): void
    {
        $metrics = $this->service->calculateForCovoiturage(1);

        $this->assertGreaterThan(0, $metrics['distance_km']);
        $this->assertGreaterThan(0, $metrics['duree_minutes']);
        $this->assertGreaterThan(0, $metrics['co2_economise_kg']);
    }

    public function testUpdateCovoiturageMetricsPersistsValues(): void
    {
        $updated = $this->service->updateCovoiturageMetrics(1);
        $this->assertTrue($updated);

        $stmt = $this->pdo->query('SELECT distance_km, co2_economise_kg, duree FROM covoiturage WHERE covoiturage_id = 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($row['distance_km']);
        $this->assertNotNull($row['co2_economise_kg']);
        $this->assertNotNull($row['duree']);
        $this->assertGreaterThan(300, (float) $row['distance_km']);
    }
}
