<?php

/**
 * Façade procédurale de compatibilité — délègue à Ecoride\Ecf\Models\User.
 *
 * @param PDO $pdo Conservé pour compatibilité avec les tests existants (non utilisé).
 */

use Ecoride\Ecf\Models\User;

function verifyUserLoginPassword(PDO $pdo, string $email, string $password): bool|array
{
    return (new User())->verifyLogin($email, $password);
}
