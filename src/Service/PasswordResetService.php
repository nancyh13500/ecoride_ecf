<?php

namespace Ecoride\Ecf\Service;

use Ecoride\Ecf\Models\User;

class PasswordResetService
{
    private const TOKEN_VALIDITY_HOURS = 1;

    private User $userModel;
    private MailerService $mailer;

    public function __construct(?User $userModel = null, ?MailerService $mailer = null)
    {
        $this->userModel = $userModel ?? new User();
        $this->mailer = $mailer ?? new MailerService();
    }

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

        $token = $this->createToken($user);
        $resetUrl = getAppUrl() . '/pages/mot_de_passe/reinitialiser.php?token=' . urlencode($token);

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
        if ($token === '') {
            return null;
        }

        $decoded = $this->base64UrlDecode($token);
        if ($decoded === false) {
            return null;
        }

        $parts = explode('|', $decoded, 3);
        if (count($parts) !== 3) {
            return null;
        }

        [$userId, $expires, $signature] = $parts;

        if (!ctype_digit($userId) || !ctype_digit($expires)) {
            return null;
        }

        if ((int)$expires < time()) {
            return null;
        }

        $user = $this->userModel->findById((int)$userId);
        if (!$user) {
            return null;
        }

        if (isset($user['suspended']) && (int)$user['suspended'] === 1) {
            return null;
        }

        $payload = $userId . '|' . $expires;
        $expectedSignature = $this->sign($payload, $user['password']);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        return [
            'user_id' => (int)$userId,
            'email' => $user['email'],
            'prenom' => $user['prenom'] ?? '',
            'nom' => $user['nom'] ?? '',
        ];
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

        return $this->userModel->updatePassword((int)$tokenData['user_id'], $password);
    }

    private function createToken(array $user): string
    {
        $expires = time() + (self::TOKEN_VALIDITY_HOURS * 3600);
        $payload = (int)$user['user_id'] . '|' . $expires;
        $signature = $this->sign($payload, $user['password']);

        return $this->base64UrlEncode($payload . '|' . $signature);
    }

    private function sign(string $payload, string $passwordHash): string
    {
        return hash_hmac('sha256', $payload . '|' . $passwordHash, getAppSecret());
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string|false
    {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? false : $decoded;
    }
}
