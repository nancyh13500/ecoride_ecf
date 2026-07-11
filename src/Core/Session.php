<?php

namespace Ecoride\Ecf\Core;

class Session
{
    private const REMEMBER_LIFETIME = 2592000; // 30 jours

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                $this->startSession();
            } elseif (!isset($_SESSION) || !is_array($_SESSION)) {
                $_SESSION = [];
            }
        } elseif ($this->isRememberMeActive()) {
            $this->refreshSessionCookie(self::REMEMBER_LIFETIME);
        }
    }

    public function isUserConnected(): bool
    {
        return isset($_SESSION['user']);
    }

    public function requireLogin(): void
    {
        if (!$this->isUserConnected()) {
            $_SESSION['error'] = 'Vous devez être connecté pour accéder à cette page.';
            header('Location: /login.php');
            exit();
        }
    }

    /**
     * Applique la durée de session choisie à la connexion.
     */
    public function persistLogin(bool $rememberMe): void
    {
        $_SESSION['remember_me'] = $rememberMe;
        session_regenerate_id(true);

        if ($rememberMe) {
            $this->refreshSessionCookie(self::REMEMBER_LIFETIME);
            return;
        }

        unset($_SESSION['remember_me']);
        $this->refreshSessionCookie(0);
    }

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        $this->clearSessionCookie();
        session_destroy();
    }

    public function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCSRFToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function verifyCSRFToken(string $tokenName = 'csrf_token'): void
    {
        $token = $_POST[$tokenName] ?? $_GET[$tokenName] ?? '';

        if (empty($token) || !$this->validateCSRFToken($token)) {
            $_SESSION['error'] = 'Token CSRF invalide. Veuillez réessayer.';
            header('Location: /index.php');
            exit();
        }
    }

    public function csrfField(string $tokenName = 'csrf_token'): void
    {
        $token = $this->generateCSRFToken();
        echo '<input type="hidden" name="' . htmlspecialchars($tokenName) . '" value="' . htmlspecialchars($token) . '">';
    }

    private function startSession(): void
    {
        session_set_cookie_params($this->cookieParams(0));
        session_start();

        if ($this->isRememberMeActive()) {
            $this->refreshSessionCookie(self::REMEMBER_LIFETIME);
        }
    }

    private function isRememberMeActive(): bool
    {
        return !empty($_SESSION['remember_me']);
    }

    private function refreshSessionCookie(int $lifetime): void
    {
        if (headers_sent() || session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        setcookie(session_name(), session_id(), $this->cookieOptions($lifetime));
    }

    private function clearSessionCookie(): void
    {
        if (headers_sent()) {
            return;
        }

        setcookie(session_name(), '', $this->cookieOptions(0, true));
    }

    private function cookieParams(int $lifetime): array
    {
        return [
            'lifetime' => $lifetime,
            'path' => '/',
            'httponly' => true,
            'secure' => $this->isSecureConnection(),
            'samesite' => 'Lax',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cookieOptions(int $lifetime, bool $delete = false): array
    {
        return [
            'expires' => $delete ? time() - 3600 : ($lifetime > 0 ? time() + $lifetime : 0),
            'path' => '/',
            'httponly' => true,
            'secure' => $this->isSecureConnection(),
            'samesite' => 'Lax',
        ];
    }

    private function isSecureConnection(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
