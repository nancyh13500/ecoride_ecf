<?php

declare(strict_types=1);

use Ecoride\Ecf\Service\CreditService;
use PHPUnit\Framework\TestCase;

/**
 * Tests d'intégration CreditService avec SQLite en mémoire.
 */
class CreditServiceIntegrationTest extends TestCase
{
    private PDO $pdo;
    private CreditService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE user (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                credits INTEGER NOT NULL DEFAULT 0
            );
            CREATE TABLE credit_transactions (
                transaction_id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                montant INTEGER NOT NULL,
                solde_apres INTEGER NOT NULL,
                type_operation TEXT NOT NULL,
                reference_id INTEGER,
                description TEXT,
                date_transaction TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $this->pdo->exec("INSERT INTO user (user_id, credits) VALUES (1, 100)");
        $this->service = new CreditService($this->pdo);
    }

    public function testGetBalanceReturnsCurrentCredits(): void
    {
        $this->assertSame(100, $this->service->getBalance(1));
    }

    public function testHasSufficientCredits(): void
    {
        $this->assertTrue($this->service->hasSufficientCredits(1, 50));
        $this->assertFalse($this->service->hasSufficientCredits(1, 150));
    }

    public function testDebitReducesBalanceAndLogsTransaction(): void
    {
        $this->pdo->beginTransaction();
        $newBalance = $this->service->debit(1, 30, 'debit_reservation', 5, 'Test réservation');
        $this->pdo->commit();

        $this->assertSame(70, $newBalance);
        $this->assertSame(70, $this->service->getBalance(1));

        $stmt = $this->pdo->query('SELECT montant, solde_apres, type_operation FROM credit_transactions');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('-30', (string) $row['montant']);
        $this->assertSame('70', (string) $row['solde_apres']);
        $this->assertSame('debit_reservation', $row['type_operation']);
    }

    public function testDebitFailsWhenInsufficientCredits(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Crédits insuffisants');

        $this->pdo->beginTransaction();
        $this->service->debit(1, 200, 'debit_reservation');
    }

    public function testCreditIncreasesBalance(): void
    {
        $this->pdo->beginTransaction();
        $newBalance = $this->service->credit(1, 25, 'credit_trajet', 3, 'Gain trajet');
        $this->pdo->commit();

        $this->assertSame(125, $newBalance);
        $this->assertSame(125, $this->service->getBalance(1));
    }
}
