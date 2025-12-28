<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests unitaires pour les fonctions utilisateur
 * 
 * Ces tests utilisent des mocks pour simuler la base de données
 * sans avoir besoin d'une vraie connexion MySQL
 */
class UserTest extends TestCase
{
    /**
     * Mock de PDO pour simuler la base de données
     */
    private MockObject $pdoMock;
    private MockObject $stmtMock;

    protected function setUp(): void
    {
        // Créer un mock PDO
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
    }

    /**
     * Test : verifyUserLoginPassword() retourne l'utilisateur avec bon email/mot de passe
     */
    public function testVerifyUserLoginPasswordWithValidCredentials(): void
    {
        require_once __DIR__ . '/../../lib/user.php';

        // Données utilisateur simulées
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $userData = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'password' => $hashedPassword,
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'role_id' => 3
        ];

        // Configurer le mock pour simuler une requête réussie
        $this->stmtMock->expects($this->once())
            ->method('bindValue')
            ->with($this->equalTo(':email'), $this->equalTo('test@example.com'));

        $this->stmtMock->expects($this->once())
            ->method('execute');

        $this->stmtMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($userData);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM user WHERE email = :email'))
            ->willReturn($this->stmtMock);

        // Exécuter la fonction
        $result = verifyUserLoginPassword(
            $this->pdoMock,
            'test@example.com',
            'password123'
        );

        // Vérifier le résultat
        $this->assertIsArray($result);
        $this->assertEquals($userData, $result);
    }

    /**
     * Test : verifyUserLoginPassword() retourne false avec mauvais mot de passe
     */
    public function testVerifyUserLoginPasswordWithWrongPassword(): void
    {
        require_once __DIR__ . '/../../lib/user.php';

        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $userData = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'password' => $hashedPassword,
            'nom' => 'Dupont',
            'prenom' => 'Jean'
        ];

        $this->stmtMock->expects($this->once())
            ->method('execute');

        $this->stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn($userData);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        // Tester avec un mauvais mot de passe
        $result = verifyUserLoginPassword(
            $this->pdoMock,
            'test@example.com',
            'wrong_password'
        );

        $this->assertFalse($result);
    }

    /**
     * Test : verifyUserLoginPassword() retourne false avec email inexistant
     */
    public function testVerifyUserLoginPasswordWithNonExistentEmail(): void
    {
        require_once __DIR__ . '/../../lib/user.php';

        $this->stmtMock->expects($this->once())
            ->method('execute');

        $this->stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false); // Aucun utilisateur trouvé

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $result = verifyUserLoginPassword(
            $this->pdoMock,
            'nonexistent@example.com',
            'password123'
        );

        $this->assertFalse($result);
    }

    /**
     * Test : verifyUserLoginPassword() retourne false si compte suspendu
     */
    public function testVerifyUserLoginPasswordWithSuspendedAccount(): void
    {
        require_once __DIR__ . '/../../lib/user.php';

        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $userData = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'password' => $hashedPassword,
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'suspended' => 1 // Compte suspendu
        ];

        $this->stmtMock->expects($this->once())
            ->method('execute');

        $this->stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn($userData);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $result = verifyUserLoginPassword(
            $this->pdoMock,
            'test@example.com',
            'password123'
        );

        // Doit retourner false car le compte est suspendu
        $this->assertFalse($result);
    }
}
