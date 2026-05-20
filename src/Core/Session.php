<?php

namespace Ecoride\Ecf\Core;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            ob_start();
            session_set_cookie_params([
                'lifetime' => 3600,
                'path' => '/',
                'httponly' => true
            ]);
            session_start();
        }
    }

    public function isUserConnected(): bool
    {
        return isset($_SESSION['user']);
    }

    public function requireLogin(): void
    {
        if (!$this->isUserConnected()) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
            header('Location: /login.php');
            exit();
        }
    }

    public function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            // Génération du token
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
            $_SESSION['error'] = "Token CSRF invalide. Veuillez réessayer.";
            header('Location: /index.php');
            exit();
        }
    }

    public function csrfField(string $tokenName = 'csrf_token'): void
    {
        $token = $this->generateCSRFToken();
        echo '<input type="hidden" name="' . htmlspecialchars($tokenName) . '" value="' . htmlspecialchars($token) . '">';
    }
}
