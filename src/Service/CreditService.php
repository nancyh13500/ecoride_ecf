<?php

declare(strict_types=1);

namespace Ecoride\Ecf\Service;

use PDO;
use PDOException;
use RuntimeException;

class CreditService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function ensureTable(): void
    {
        if ($this->tableExists('credit_transactions')) {
            return;
        }

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `credit_transactions` (
                `transaction_id` int NOT NULL AUTO_INCREMENT,
                `user_id` int NOT NULL,
                `montant` int NOT NULL,
                `solde_apres` int NOT NULL,
                `type_operation` varchar(50) NOT NULL,
                `reference_id` int DEFAULT NULL,
                `description` varchar(255) DEFAULT NULL,
                `date_transaction` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`transaction_id`),
                KEY `idx_credit_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    private function tableExists(string $table): bool
    {
        try {
            $this->pdo->query("SELECT 1 FROM {$table} LIMIT 1");

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function getBalance(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT credits FROM user WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $balance = $stmt->fetchColumn();

        return $balance !== false ? (int) $balance : 0;
    }

    public function hasSufficientCredits(int $userId, int $amount): bool
    {
        return $this->getBalance($userId) >= $amount;
    }

    /**
     * Débite des crédits (montant positif en entrée).
     *
     * @throws RuntimeException
     */
    public function debit(
        int $userId,
        int $amount,
        string $typeOperation,
        ?int $referenceId = null,
        ?string $description = null
    ): int {
        if ($amount <= 0) {
            throw new RuntimeException('Le montant à débiter doit être strictement positif.');
        }

        $this->ensureTable();

        $lockStmt = $this->pdo->prepare('SELECT credits FROM user WHERE user_id = :user_id FOR UPDATE');
        $lockStmt->execute(['user_id' => $userId]);
        $currentBalance = $lockStmt->fetchColumn();

        if ($currentBalance === false) {
            throw new RuntimeException('Utilisateur introuvable.');
        }

        $currentBalance = (int) $currentBalance;
        if ($currentBalance < $amount) {
            throw new RuntimeException(
                "Crédits insuffisants (solde : {$currentBalance}, requis : {$amount})."
            );
        }

        $newBalance = $currentBalance - $amount;

        $updateStmt = $this->pdo->prepare(
            'UPDATE user SET credits = :credits WHERE user_id = :user_id'
        );
        $updateStmt->execute([
            'credits' => $newBalance,
            'user_id' => $userId,
        ]);

        $this->logTransaction($userId, -$amount, $newBalance, $typeOperation, $referenceId, $description);

        return $newBalance;
    }

    /**
     * Crédite un compte (montant positif en entrée).
     *
     * @throws RuntimeException
     */
    public function credit(
        int $userId,
        int $amount,
        string $typeOperation,
        ?int $referenceId = null,
        ?string $description = null
    ): int {
        if ($amount <= 0) {
            throw new RuntimeException('Le montant à créditer doit être strictement positif.');
        }

        $this->ensureTable();

        $lockStmt = $this->pdo->prepare('SELECT credits FROM user WHERE user_id = :user_id FOR UPDATE');
        $lockStmt->execute(['user_id' => $userId]);
        $currentBalance = $lockStmt->fetchColumn();

        if ($currentBalance === false) {
            throw new RuntimeException('Utilisateur introuvable.');
        }

        $currentBalance = (int) $currentBalance;
        $newBalance = $currentBalance + $amount;

        $updateStmt = $this->pdo->prepare(
            'UPDATE user SET credits = :credits WHERE user_id = :user_id'
        );
        $updateStmt->execute([
            'credits' => $newBalance,
            'user_id' => $userId,
        ]);

        $this->logTransaction($userId, $amount, $newBalance, $typeOperation, $referenceId, $description);

        return $newBalance;
    }

    private function logTransaction(
        int $userId,
        int $signedAmount,
        int $balanceAfter,
        string $typeOperation,
        ?int $referenceId,
        ?string $description
    ): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO credit_transactions (user_id, montant, solde_apres, type_operation, reference_id, description)
            VALUES (:user_id, :montant, :solde_apres, :type_operation, :reference_id, :description)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'montant' => $signedAmount,
            'solde_apres' => $balanceAfter,
            'type_operation' => $typeOperation,
            'reference_id' => $referenceId,
            'description' => $description,
        ]);
    }
}
