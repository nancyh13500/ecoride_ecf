<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour les fonctions de session
 * 
 * Ces tests vérifient :
 * - La génération de tokens CSRF
 * - La validation de tokens CSRF
 * - La détection de connexion utilisateur
 */
class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        // Nettoyer les buffers de sortie
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Démarrer une session de test
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Nettoyer la session avant chaque test
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Nettoyer les buffers de sortie
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Nettoyer la session après chaque test
        $_SESSION = [];
    }

    /**
     * Test : generateCSRFToken() génère un token valide
     */
    public function testGenerateCSRFTokenGeneratesValidToken(): void
    {
        // Inclure le fichier de session
        require_once __DIR__ . '/../../lib/session.php';

        $token = generateCSRFToken();

        // Vérifier que le token est une chaîne non vide
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Vérifier que le token fait 64 caractères (32 bytes en hexadécimal)
        $this->assertEquals(64, strlen($token));

        // Vérifier que le token est stocké en session
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    /**
     * Test : generateCSRFToken() retourne le même token si déjà généré
     */
    public function testGenerateCSRFTokenReturnsSameToken(): void
    {
        require_once __DIR__ . '/../../lib/session.php';

        $token1 = generateCSRFToken();
        $token2 = generateCSRFToken();

        // Le token doit être le même si déjà généré
        $this->assertEquals($token1, $token2);
    }

    /**
     * Test : validateCSRFToken() valide un token correct
     */
    public function testValidateCSRFTokenWithValidToken(): void
    {
        require_once __DIR__ . '/../../lib/session.php';

        $token = generateCSRFToken();
        $result = validateCSRFToken($token);

        $this->assertTrue($result);
    }

    /**
     * Test : validateCSRFToken() rejette un token incorrect
     */
    public function testValidateCSRFTokenWithInvalidToken(): void
    {
        require_once __DIR__ . '/../../lib/session.php';

        generateCSRFToken(); // Générer un token
        $fakeToken = 'fake_token_123456789012345678901234567890123456789012345678901234567890';

        $result = validateCSRFToken($fakeToken);

        $this->assertFalse($result);
    }

    /**
     * Test : validateCSRFToken() retourne false si aucun token en session
     */
    public function testValidateCSRFTokenWithoutSessionToken(): void
    {
        require_once __DIR__ . '/../../lib/session.php';

        // Ne pas générer de token
        $result = validateCSRFToken('any_token');

        $this->assertFalse($result);
    }

    /**
     * Test : isUserConnected() retourne true si utilisateur connecté
     */
    public function testIsUserConnectedWhenUserIsConnected(): void
    {
        require_once __DIR__ . '/../../lib/session.php';

        $_SESSION['user'] = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'nom' => 'Test'
        ];

        $result = isUserConnected();

        $this->assertTrue($result);
    }

    /**
     * Test : isUserConnected() retourne false si utilisateur non connecté
     */
    public function testIsUserConnectedWhenUserIsNotConnected(): void
    {
        require_once __DIR__ . '/../../lib/session.php';

        // Ne pas définir $_SESSION['user']
        $result = isUserConnected();

        $this->assertFalse($result);
    }
}
