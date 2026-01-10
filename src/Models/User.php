<?php

namespace Ecoride\Ecf\Models;

use Ecoride\Ecf\Core\Database;
use PDO;

class User
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function verifyLogin(string $email, string $password): bool|array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE email = :email");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Refuser l'accès si le compte est suspendu
            if (isset($user['suspended']) && (int)$user['suspended'] === 1) {
                return false;
            }
            return $user;
        }
        return false;
    }

    public function findById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE user_id = :id");
        $stmt->execute(['id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    // ... existing code ...
    public function register(array $userData): bool
    {
        $stmt = $this->pdo->prepare("
        INSERT INTO user (nom, prenom, email, password, telephone, adresse, date_naissance, photo, pseudo, role_id, role_covoiturage) 
        VALUES (:nom, :prenom, :email, :password, :telephone, :adresse, :date_naissance, :photo, :pseudo, :role_id, :role_covoiturage)
    ");

        return $stmt->execute([
            'nom' => $userData['nom'],
            'prenom' => $userData['prenom'],
            'email' => $userData['email'],
            'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
            'telephone' => $userData['telephone'] ?? '',
            'adresse' => $userData['adresse'] ?? '',
            'date_naissance' => $userData['date_naissance'] ?? '1970-01-01',
            'photo' => '',
            'pseudo' => ($userData['prenom'] ?? '') . ' ' . ($userData['nom'] ?? ''),
            'role_id' => 3, // Utilisateur par défaut
            'role_covoiturage' => $userData['role_covoiturage'] ?? 'Passager'
        ]);
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
}
