# 🚗 EcoRide - Plateforme de Covoiturage Écologique

## 🌍 À propos du projet

EcoRide est une plateforme web de covoiturage française qui met en avant une démarche écologique en favorisant les déplacements en voitures électriques et en optimisant le partage des trajets. L'application permet aux utilisateurs de proposer et réserver des trajets en covoiturage tout en privilégiant les véhicules respectueux de l'environnement.

## ✨ Fonctionnalités principales

### 👤 Gestion des utilisateurs

- **Inscription et connexion** avec gestion des sessions
- **Profil utilisateur** avec photo de profil et informations personnelles
- **Rôles utilisateur** : Passager, Chauffeur, ou Les deux
- **Gestion des voitures** personnelles avec marque, modèle, énergie

### 🚗 Gestion des trajets

- **Création de trajets** avec lieu de départ/arrivée, date, heure, prix
- **Recherche de trajets** disponibles
- **Gestion du statut** : En attente, En cours, Terminé
- **Actions sur les trajets** : Démarrer, Terminer, Supprimer

### 🚙 Gestion des voitures

- **Ajout de voitures** personnelles
- **Catégorisation** par marque et type d'énergie
- **Gestion du parc automobile** de l'utilisateur

### 📞 Contact et informations

- **Page de contact** pour les utilisateurs
- **Mentions légales** conformes à la réglementation

## 🛠️ Technologies utilisées

- **Backend** : PHP 8+
- **Base de données** : MySQL
- **Frontend** : HTML5, CSS3, JavaScript, Bootstrap 5
- **Serveur** : WAMP/XAMPP compatible
- **Gestion des sessions** : PHP Sessions

## 📋 Prérequis

Avant d'utiliser l'application, assurez-vous d'avoir installé :

- **Git** pour le versioning
- **PHP 8+** pour l'exécution du code
- **MySQL** pour la base de données
- **Serveur local** : WAMP, XAMPP, ou équivalent
- **Navigateur web moderne** : Chrome, Firefox, Safari, Edge

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone [URL_DU_REPO]
cd ecoride_ecf
```

### 2. Configuration de la base de données

1. Créez une base de données MySQL nommée `ecoride`
2. Importez le fichier `ecoride.sql` dans votre base de données
3. Configurez les paramètres de connexion dans `lib/pdo.php`

### 3. Configuration du serveur

1. Placez le projet dans le dossier `www` de votre serveur local
2. Assurez-vous que PHP et MySQL sont démarrés
3. Accédez à l'application via `http://localhost/ecoride_ecf`

## 📁 Structure du projet

```
ecoride_ecf/
├── assets/                 # Ressources statiques
│   ├── css/               # Feuilles de style
│   ├── js/                # Scripts JavaScript
│   ├── img/               # Images du site
│   └── logo/              # Logos de l'application
├── Documentation/         # Diagrammes et documentation
├── lib/                   # Bibliothèques PHP
│   ├── pdo.php           # Connexion à la base de données
│   ├── session.php       # Gestion des sessions
│   ├── user.php          # Fonctions utilisateur
│   └── update_user.php   # Mise à jour du profil
├── pages/                 # Pages de l'application
│   ├── user_count.php    # Gestion du compte utilisateur
│   ├── mes_trajets.php   # Gestion des trajets
│   ├── mes_voitures.php  # Gestion des voitures
│   ├── mes_reservations.php # Réservations
│   ├── covoiturage.php   # Recherche de trajets
│   ├── contact.php       # Page de contact
│   └── mentions_legales.php # Mentions légales
├── templates/             # Templates réutilisables
│   ├── header.php        # En-tête du site
│   └── footer.php        # Pied de page
├── index.php             # Page d'accueil
├── login.php             # Page de connexion
├── logout.php            # Déconnexion
├── ecoride.sql           # Structure de la base de données
└── README.md             # Ce fichier
```

## 🗄️ Structure de la base de données

### Tables principales :

- **`user`** : Informations des utilisateurs
- **`voiture`** : Véhicules des utilisateurs
- **`covoiturage`** : Trajets proposés
- **`marque`** : Marques de voitures
- **`energie`** : Types d'énergie (électrique, essence, etc.)

## 👥 Utilisation

### Inscription et connexion

1. Accédez à la page d'accueil
2. Cliquez sur "Se connecter" ou "S'inscrire"
3. Remplissez le formulaire avec vos informations
4. Choisissez votre rôle (Passager/Chauffeur/Les deux)

### Ajouter une voiture

1. Connectez-vous à votre compte
2. Allez dans "Mes voitures"
3. Remplissez le formulaire avec les informations de votre véhicule
4. Précisez le type d'énergie (électrique recommandé)

### Proposer un trajet

1. Allez dans "Mes trajets"
2. Remplissez le formulaire avec les détails du trajet
3. Sélectionnez votre voiture
4. Cliquez sur "Ajouter mon trajet"

### Gérer un trajet

- **Démarrer** : Cliquez sur "Démarrer le covoiturage"
- **Terminer** : Cliquez sur "Terminer" une fois le trajet terminé
- **Supprimer** : Utilisez la case à cocher et "Supprimer la sélection"

## 🔧 Configuration

### Base de données

Modifiez `lib/pdo.php` avec vos paramètres :

```php
$host = 'localhost';
$dbname = 'ecoride';
$username = 'votre_utilisateur';
$password = 'votre_mot_de_passe';
```

### Sessions

Les sessions sont gérées automatiquement par `lib/session.php`

## 🐛 Dépannage

### Problèmes courants :

1. **Erreur de connexion à la base** : Vérifiez les paramètres dans `lib/pdo.php`
2. **Pages blanches** : Vérifiez que PHP est activé et les erreurs affichées
3. **Problèmes de session** : Vérifiez que les cookies sont activés

### Logs d'erreur

Consultez les logs de votre serveur web pour plus de détails sur les erreurs.

## 📝 Licence

Ce projet est développé dans le cadre d'un ECF (Épreuve de Contrôle Final) en 2025.

## 👨‍💻 Développement

### Ajout de nouvelles fonctionnalités

1. Créez vos fichiers PHP dans le dossier approprié
2. Ajoutez les requêtes SQL nécessaires
3. Mettez à jour la documentation
4. Testez sur différents navigateurs

### Bonnes pratiques

- Respectez la structure des dossiers
- Utilisez les templates header/footer
- Validez les données utilisateur
- Gérez les erreurs de base de données

## 📞 Support

Pour toute question ou problème :

- Consultez la documentation dans le dossier `Documentation/`
- Vérifiez les logs d'erreur
- Contactez l'équipe de développement

---

**EcoRide** - Favorisons ensemble la mobilité durable ! 🌱
