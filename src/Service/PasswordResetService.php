<?php

namespace Ecoride\Ecf\Service;

use Ecoride\Ecf\Models\User;
use PDO;

class PasswordResetService
{
    private const TOKEN_VALIDITY_HOURS = 1;

    private PDO $pdo;
    private User $userModel;
    private MailerService $mailer;

    public function __construct(?PDO $pdo = null, ?User $userModel = null, ?MailerService $mailer = null)
    {
        $this->pdo = $pdo ?? \Ecoride\Ecf\Core\Database::getInstance();
        $this->userModel = $userModel ?? new User();
        $this->mailer = $mailer ?? new MailerService();
    }

    /**
     * Demande une réinitialisation de mot de passe.
     * Retourne toujours true pour ne pas révéler si l'email existe.
     */
    public function requestReset(string $email): bool
    {
        $email = trim(strtolower($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            return true;
        }

        if (isset($user['suspended']) && (int)$user['suspended'] === 1) {
            return true;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = (new \DateTimeImmutable('+' . self::TOKEN_VALIDITY_HOURS . ' hour'))->format('Y-m-d H:i:s');

        $this->pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id')
            ->execute(['user_id' => $user['user_id']]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            'user_id' => $user['user_id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $resetUrl = getAppUrl() . '/reinitialiser_mot_de_passe.php?token=' . urlencode($token);

        $this->mailer->sendPasswordResetEmail([
            'email' => $user['email'],
            'name' => trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')),
            'reset_url' => $resetUrl,
            'expires_hours' => self::TOKEN_VALIDITY_HOURS,
        ]);

        return true;
    }

    public function findValidToken(string $token): ?array
    {
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $tokenHash = hash('sha256', $token);

        $stmt = $this->pdo->prepare(
            'SELECT prt.*, u.email, u.prenom, u.nom
             FROM password_reset_tokens prt
             INNER JOIN user u ON u.user_id = prt.user_id
             WHERE prt.token_hash = :token_hash AND prt.expires_at > NOW()'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function resetPassword(string $token, string $password): bool
    {
        $tokenData = $this->findValidToken($token);

        if (!$tokenData) {
            return false;
        }

        if (strlen($password) < 6) {
            return false;
        }

        if (!$this->userModel->updatePassword((int)$tokenData['user_id'], $password)) {
            return false;
        }

        $this->pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id')
            ->execute(['user_id' => $tokenData['user_id']]);

        return true;
    }
}
