# Installation de PHPUnit pour les tests

## 🐳 Option 1 : Avec Docker (Recommandé)

Si tu utilises Docker pour ton projet, PHPUnit sera installé automatiquement dans le conteneur.

### Étape 1 : Démarrer les conteneurs Docker
```bash
docker-compose up -d
```

### Étape 2 : Exécuter Composer dans le conteneur
```bash
docker-compose exec app composer install
```

### Étape 3 : Exécuter les tests
```bash
docker-compose exec app vendor/bin/phpunit
```

Les tests sont maintenant dans le dossier `test/` (au singulier).

---

## 💻 Option 2 : Installation locale de Composer

Si tu préfères installer Composer localement sur Windows :

### Étape 1 : Télécharger Composer
1. Va sur https://getcomposer.org/download/
2. Télécharge `Composer-Setup.exe`
3. Installe-le (il détectera automatiquement PHP)

### Étape 2 : Vérifier l'installation
```powershell
composer --version
```

### Étape 3 : Installer PHPUnit
```powershell
cd c:\wamp64\www\ecoride_ecf
composer install
```

### Étape 4 : Exécuter les tests
```powershell
vendor\bin\phpunit
```

---

## ✅ Vérification

Après l'installation, tu devrais voir :
- Un dossier `vendor/` avec PHPUnit
- Le fichier `vendor/bin/phpunit` (ou `vendor\bin\phpunit.bat` sur Windows)

## 🚀 Exécuter les tests

### Tous les tests
```bash
# Avec Docker
docker-compose exec app vendor/bin/phpunit

# Localement
vendor\bin\phpunit
```

### Un fichier spécifique
```bash
vendor\bin\phpunit test/Unit/SessionTest.php
```

### Un test spécifique
```bash
vendor\bin\phpunit --filter testGenerateCSRFToken
```

## 📊 Résultat attendu

Tu devrais voir quelque chose comme :
```
PHPUnit 10.5.0 by Sebastian Bergmann and contributors.

..                                                                  2 / 2 (100%)

Time: 00:00.123, Memory: 4.00 MB

OK (2 tests, 4 assertions)
```

## ⚠️ Problèmes courants

### "Composer n'est pas reconnu"
- Installe Composer depuis https://getcomposer.org/download/
- Ou utilise Docker : `docker-compose exec app composer install`

### "PHPUnit n'est pas trouvé"
- Vérifie que `composer install` a bien fonctionné
- Vérifie que le dossier `vendor/` existe

### "Erreur de session"
- Les tests de session peuvent nécessiter des ajustements
- Vérifie que `tests/bootstrap.php` est correct

