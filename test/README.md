# Tests Unitaires - Ecoride

## 📋 Qu'est-ce qu'un test unitaire ?

Un **test unitaire** est un test qui vérifie qu'une **fonction isolée** fonctionne correctement. C'est comme tester chaque pièce d'une voiture individuellement avant de monter la voiture complète.

### Exemple concret

Imagine la fonction `verifyUserLoginPassword()` :

- **Test 1** : Avec un bon email et mot de passe → doit retourner l'utilisateur ✅
- **Test 2** : Avec un mauvais mot de passe → doit retourner `false` ✅
- **Test 3** : Avec un email inexistant → doit retourner `false` ✅

## 🎯 Pourquoi faire des tests unitaires ?

1. **Détecter les bugs** avant qu'ils n'arrivent en production
2. **Documenter** comment le code doit fonctionner
3. **Refactoriser** en toute sécurité (changer le code sans casser)
4. **Gagner du temps** : les tests automatisent la vérification

## 🚀 Installation

### 1. Installer PHPUnit via Composer

```bash
composer require --dev phpunit/phpunit
```

### 2. Vérifier l'installation

```bash
vendor/bin/phpunit --version
```

## 📁 Structure des tests

```
test/
├── bootstrap.php          # Configuration initiale
├── Unit/                  # Tests unitaires (fonctions isolées)
│   ├── SessionTest.php   # Tests des fonctions de session
│   └── UserTest.php      # Tests des fonctions utilisateur
└── Integration/          # Tests d'intégration (plusieurs composants)
    └── (à venir)
```

## ▶️ Exécuter les tests

### Tous les tests

```bash
vendor/bin/phpunit
```

### Un fichier de test spécifique

```bash
vendor/bin/phpunit test/Unit/SessionTest.php
```

### Un test spécifique

```bash
vendor/bin/phpunit --filter testGenerateCSRFTokenGeneratesValidToken
```

### Avec couverture de code

```bash
vendor/bin/phpunit --coverage-html coverage/
```

## 📝 Exemples de tests dans le projet

### Test 1 : Génération de token CSRF

```php
public function testGenerateCSRFTokenGeneratesValidToken(): void
{
    $token = generateCSRFToken();

    // Vérifier que le token est valide
    $this->assertIsString($token);
    $this->assertEquals(64, strlen($token));
}
```

### Test 2 : Validation de mot de passe

```php
public function testVerifyUserLoginPasswordWithValidCredentials(): void
{
    // Simuler une base de données
    $user = verifyUserLoginPassword($pdoMock, 'test@example.com', 'password123');

    // Vérifier le résultat
    $this->assertIsArray($user);
    $this->assertEquals('test@example.com', $user['email']);
}
```

## 🔍 Comprendre les assertions

Les **assertions** sont des vérifications dans les tests :

- `$this->assertTrue($result)` → Vérifie que c'est `true`
- `$this->assertFalse($result)` → Vérifie que c'est `false`
- `$this->assertEquals($expected, $actual)` → Vérifie l'égalité
- `$this->assertIsString($value)` → Vérifie que c'est une chaîne
- `$this->assertIsArray($value)` → Vérifie que c'est un tableau

## 🎓 Bonnes pratiques

1. **Un test = une fonctionnalité** : Chaque test vérifie une seule chose
2. **Nommage clair** : `testVerifyUserLoginPasswordWithValidCredentials()` explique ce qui est testé
3. **Isolation** : Chaque test est indépendant (utilise `setUp()` et `tearDown()`)
4. **Mocks** : Utilise des mocks pour simuler la base de données

## 📊 Résultat des tests

Quand tu exécutes les tests, tu obtiens :

```
PHPUnit 10.5.0 by Sebastian Bergmann and contributors.

..                                                                  2 / 2 (100%)

Time: 00:00.123, Memory: 4.00 MB

OK (2 tests, 4 assertions)
```

- `.` = Test réussi
- `F` = Test échoué
- `E` = Erreur

## 🐛 Déboguer un test qui échoue

Si un test échoue, PHPUnit te montre :

```
1) SessionTest::testValidateCSRFTokenWithInvalidToken
Failed asserting that false is true.

Expected: true
Actual:   false
```

Cela signifie que le test s'attendait à `true` mais a reçu `false`. Il faut vérifier la logique de la fonction.

## ✅ Checklist pour le titre RNCP

- [x] Tests unitaires avec PHPUnit
- [x] Tests de fonctions métier (authentification, sessions)
- [x] Utilisation de mocks pour isoler les tests
- [x] Structure de tests organisée
- [ ] Tests d'intégration (à ajouter)
- [ ] Couverture de code > 70% (objectif)

## 📚 Ressources

- [Documentation PHPUnit](https://phpunit.de/documentation.html)
- [Guide des assertions PHPUnit](https://phpunit.readthedocs.io/en/10.5/assertions.html)
