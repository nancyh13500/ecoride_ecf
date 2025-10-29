# 📋 CAHIER DES CHARGES - EcoRide

## Plateforme de Covoiturage Écologique

**Version :** 1.0  
**Date :** 2025  
**Contexte :** Projet ECF (Épreuve de Contrôle Final)

---

## 1. EXPRESSION DU BESOIN

### 1.1 Contexte et objectifs

**EcoRide** est une plateforme web de covoiturage qui vise à :

- Faciliter le partage de trajets entre utilisateurs
- Promouvoir une mobilité durable en favorisant les véhicules écologiques
- Réduire l'empreinte carbone des déplacements quotidiens
- Offrir une solution économique et pratique pour les utilisateurs

### 1.2 Public cible

- **Utilisateurs individuels** cherchant à réduire leurs coûts de transport
- **Chauffeurs** souhaitant partager leurs trajets et leurs frais
- **Passagers** recherchant des déplacements économiques et écologiques
- **Employeurs et collectivités** visant à développer la mobilité décarbonée au sein de leurs organisations

### 1.3 Enjeux

- **Économique** : Réduction des coûts de transport individuel
- **Écologique** : Promotion des véhicules électriques et réduction des émissions de CO₂
- **Social** : Facilitation des échanges et création de lien social
- **Technique** : Développement d'une application web moderne, sécurisée et performante

---

## 2. SPÉCIFICATIONS FONCTIONNELLES

### 2.1 Gestion des utilisateurs

#### 2.1.1 Inscription

- **Objectif** : Permettre aux utilisateurs de créer un compte
- **Données requises** :
  - Nom
  - Prénom
  - Email (unique)
  - Mot de passe (hashé avec bcrypt)
  - Téléphone
  - Adresse
  - Date de naissance
  - Photo de profil (optionnelle)
  - Pseudo (unique)
  - Rôle covoiturage : Passager / Chauffeur / Les deux
- **Fonctionnalités** :
  - Validation des données côté serveur
  - Vérification de l'unicité de l'email et du pseudo
  - Hashage sécurisé du mot de passe
  - Attribution automatique du rôle "Utilisateur" (role_id = 3)

#### 2.1.2 Connexion / Authentification

- **Objectif** : Permettre aux utilisateurs de se connecter
- **Fonctionnalités** :
  - Connexion par email et mot de passe
  - Vérification des identifiants
  - Création de session PHP sécurisée
  - Durée de session : 3600 secondes (1 heure)
  - Cookie HttpOnly pour la sécurité
  - Redirection selon le rôle après connexion

#### 2.1.3 Gestion de profil

- **Objectif** : Permettre la consultation et la modification du profil
- **Fonctionnalités** :
  - Consultation des informations personnelles
  - Modification des données (nom, prénom, email, téléphone, adresse, photo)
  - Gestion du rôle covoiturage
  - Affichage de l'historique des trajets

#### 2.1.4 Déconnexion

- **Objectif** : Permettre la déconnexion sécurisée
- **Fonctionnalités** :
  - Destruction de la session
  - Nettoyage des données utilisateur
  - Redirection vers la page d'accueil

### 2.2 Gestion des rôles et permissions

#### 2.2.1 Système de rôles

- **Administrateur** (role_id = 1) :

  - Accès au tableau de bord administrateur
  - Gestion de tous les utilisateurs
  - Consultation des statistiques globales
  - Création de comptes employés
  - Gestion des avis
  - Accès à toutes les fonctionnalités

- **Employé** (role_id = 2) :

  - Accès à l'espace employé
  - Fonctions administratives déléguées
  - Gestion des trajets et utilisateurs

- **Utilisateur** (role_id = 3) :
  - Accès aux fonctionnalités standard
  - Gestion de ses propres trajets et voitures
  - Consultation et réservation de trajets

#### 2.2.2 Vérifications de sécurité

- Contrôle d'accès sur toutes les pages sensibles
- Vérification du rôle avant certaines actions
- Messages d'erreur en cas d'accès non autorisé

### 2.3 Gestion des véhicules

#### 2.3.1 Ajout d'un véhicule

- **Objectif** : Permettre aux utilisateurs d'enregistrer leurs véhicules
- **Données requises** :
  - Modèle
  - Immatriculation (unique)
  - Type d'énergie (Essence / Diesel / Électrique)
  - Couleur
  - Date de première immatriculation
  - Marque (sélection dans une liste prédéfinie)
- **Fonctionnalités** :
  - Validation des données
  - Liaison avec l'utilisateur propriétaire
  - Affichage dans la liste des voitures de l'utilisateur

#### 2.3.2 Consultation des véhicules

- **Objectif** : Permettre la consultation de la liste des véhicules
- **Fonctionnalités** :
  - Affichage de toutes les voitures de l'utilisateur
  - Informations détaillées par véhicule
  - Utilisation des voitures dans la proposition de trajets

#### 2.3.3 Gestion des marques et énergies

- Liste de marques prédéfinies dans la base de données
- Types d'énergie : Essence, Diesel, Électrique
- Catégorisation des véhicules pour faciliter la recherche

### 2.4 Gestion des trajets

#### 2.4.1 Création de trajet

- **Objectif** : Permettre aux chauffeurs de proposer des trajets
- **Données requises** :
  - Lieu de départ
  - Lieu d'arrivée
  - Date de départ
  - Heure de départ (format HH:MM:SS)
  - Date d'arrivée
  - Heure d'arrivée
  - Nombre de places disponibles
  - Prix par personne (en crédits ou euros)
  - Véhicule utilisé
- **Fonctionnalités** :
  - Validation des données
  - Statut initial : "En attente" (statut = 1)
  - Calcul automatique de la durée du trajet (optionnel)
  - Liaison avec le chauffeur et le véhicule

#### 2.4.2 Recherche de trajets

- **Objectif** : Permettre aux passagers de trouver des trajets disponibles
- **Critères de recherche** :
  - Ville de départ (avec autocomplétion)
  - Ville d'arrivée (avec autocomplétion)
  - Date de départ (optionnelle)
- **Fonctionnalités** :
  - Recherche par critères multiples
  - Affichage des trajets disponibles uniquement (statut = 1)
  - Liste déroulante des villes disponibles (datalist HTML5)
  - Redirection vers la page de résultats

#### 2.4.3 Consultation des trajets

- **Objectif** : Afficher les trajets correspondant aux critères
- **Informations affichées** :
  - Informations du trajet (départ, arrivée, date, heure)
  - Informations du chauffeur (nom, photo, note moyenne)
  - Informations du véhicule (marque, modèle, type d'énergie)
  - Nombre de places disponibles
  - Prix par personne
  - Statut du trajet

#### 2.4.4 Détails d'un trajet

- **Objectif** : Afficher les informations complètes d'un trajet
- **Fonctionnalités** :
  - Affichage de toutes les informations du trajet
  - Informations détaillées du chauffeur
  - Informations du véhicule
  - Possibilité de réserver (si connecté et places disponibles)

#### 2.4.5 Gestion des statuts de trajet

- **Statuts disponibles** :
  - **En attente** (statut = 1) : Trajet créé, prêt à recevoir des réservations
  - **En cours** (statut = 2) : Trajet démarré par le chauffeur
  - **Terminé** (statut = 3) : Trajet terminé
- **Actions disponibles** :
  - **Démarrer** : Passer de "En attente" à "En cours"
  - **Terminer** : Passer de "En cours" à "Terminé"
  - **Supprimer** : Supprimer un trajet (avec confirmation)

#### 2.4.6 Mes trajets

- **Objectif** : Permettre aux utilisateurs de gérer leurs trajets
- **Fonctionnalités** :
  - Affichage des trajets créés par l'utilisateur
  - Filtrage par statut
  - Actions sur les trajets (démarrer, terminer, supprimer)
  - Affichage des réservations liées

### 2.5 Système de réservation

#### 2.5.1 Réservation d'un trajet

- **Objectif** : Permettre aux passagers de réserver une place
- **Prérequis** :
  - Être connecté
  - Places disponibles
  - Trajet non terminé
- **Fonctionnalités** :
  - Vérification de la disponibilité
  - Décrémentation du nombre de places disponibles
  - Enregistrement de la réservation
  - Confirmation à l'utilisateur

#### 2.5.2 Mes réservations

- **Objectif** : Permettre la consultation des réservations
- **Fonctionnalités** :
  - Liste des trajets réservés
  - Statut de chaque réservation
  - Informations du trajet et du chauffeur
  - Possibilité d'annuler une réservation

### 2.6 Système d'avis

#### 2.6.1 Déposer un avis

- **Objectif** : Permettre aux utilisateurs de noter et commenter les trajets
- **Données** :
  - Commentaire (max 50 caractères)
  - Note (sur 5 étoiles)
  - Statut de publication
- **Fonctionnalités** :
  - Validation des données
  - Liaison avec l'utilisateur auteur
  - Modération des avis (statut)

#### 2.6.2 Consultation des avis

- **Objectif** : Afficher les avis publiés
- **Fonctionnalités** :
  - Affichage des avis validés
  - Affichage de la note moyenne
  - Carrousel d'avis sur la page d'accueil
  - Liste complète des avis avec pagination

#### 2.6.3 Gestion des avis (Admin)

- **Objectif** : Permettre la modération des avis
- **Fonctionnalités** :
  - Liste de tous les avis
  - Modification du statut de publication
  - Suppression d'avis inappropriés

### 2.7 Fonctionnalités administrateur

#### 2.7.1 Tableau de bord administrateur

- **Objectif** : Fournir une vue d'ensemble de la plateforme
- **Statistiques affichées** :
  - Nombre total d'utilisateurs
  - Répartition des utilisateurs par rôle
  - Nombre total de covoiturages
  - Répartition des covoiturages par statut
  - Nombre total de voitures
  - Répartition des voitures par type d'énergie
  - Nombre total d'avis
  - Note moyenne des avis
  - Nombre total de réservations
  - Graphique d'évolution des covoiturages (30 derniers jours)

#### 2.7.2 Actions rapides

- **Fonctionnalités** :
  - Accès à l'espace employé
  - Création de comptes employés
  - Gestion des avis
  - Consultation des trajets

#### 2.7.3 Graphiques et visualisations

- Utilisation de Chart.js pour les graphiques
- Graphique linéaire de l'évolution des covoiturages
- Données mises à jour en temps réel

### 2.8 Pages informatives

#### 2.8.1 Page d'accueil

- **Objectif** : Présenter la plateforme et ses fonctionnalités
- **Sections** :
  - Barre de recherche de trajets
  - Présentation de la plateforme
  - Exemples de trajets populaires
  - Section "Proposer des places"
  - Carrousel d'avis
  - Liens vers les fonctionnalités principales

#### 2.8.2 Page de contact

- **Objectif** : Permettre aux utilisateurs de contacter l'équipe
- **Fonctionnalités** :
  - Formulaire de contact
  - Informations de contact
  - Support aux employeurs et collectivités

#### 2.8.3 Mentions légales

- **Objectif** : Conformité RGPD et légale
- **Contenu** :
  - Informations sur l'entreprise
  - Politique de confidentialité
  - Conditions d'utilisation
  - Gestion des données personnelles

---

## 3. SPÉCIFICATIONS TECHNIQUES

### 3.1 Technologies utilisées

#### 3.1.1 Backend

**PHP (Hypertext Preprocessor)**

- **Version** : PHP 8.2+
- **Justification** : Langage serveur standard pour applications web dynamiques
- **Extensions utilisées** :
  - `pdo_mysql` : Pour la connexion à MySQL via PDO
  - `mysqli` : Alternative de connexion (pour compatibilité)
  - `session` : Gestion des sessions utilisateur (natif)
  - `json` : Manipulation de données JSON (natif)
- **Fonctionnalités PHP utilisées** :
  - POO (Programmation Orientée Objet) avec PDO
  - Sessions PHP pour l'authentification
  - Fonctions de hashage : `password_hash()` avec bcrypt
  - `htmlspecialchars()` pour la sécurisation XSS
  - `header()` pour les redirections

**Architecture**

- **Pattern** : MVC simplifié (Modèle-Vue-Contrôleur)
- **Structure** :
  - **Modèle** : `lib/pdo.php` (accès données)
  - **Vue** : Templates (`templates/header.php`, `templates/footer.php`) et pages
  - **Contrôleur** : Logique métier dans les fichiers `pages/*.php`

**Fonctions de sécurité**

- Validation et nettoyage des données utilisateur
- Requêtes préparées PDO (protection contre SQL Injection)
- Hashage des mots de passe avec bcrypt
- Protection CSRF (via sessions)
- Échappement HTML pour prévenir XSS

#### 3.1.2 Base de données

**MySQL**

- **Version** : MySQL 8.0+
- **Moteur de stockage** : InnoDB (pour les transactions et clés étrangères)
- **Encodage** : UTF-8 (utf8mb4_general_ci)

**Structure de la base de données**

**Table `user`**

- `user_id` (INT, PRIMARY KEY, AUTO_INCREMENT) : Identifiant unique
- `nom` (VARCHAR(50)) : Nom de famille
- `prenom` (VARCHAR(50)) : Prénom
- `email` (VARCHAR(50), UNIQUE) : Email (unique)
- `password` (VARCHAR(255)) : Mot de passe hashé (bcrypt)
- `telephone` (VARCHAR(50)) : Numéro de téléphone
- `adresse` (VARCHAR(50)) : Adresse postale
- `date_naissance` (DATE) : Date de naissance
- `photo` (BLOB) : Photo de profil (binaire)
- `pseudo` (VARCHAR(50)) : Pseudo (unique)
- `role_id` (INT, FOREIGN KEY) : Rôle utilisateur (1=Admin, 2=Employé, 3=Utilisateur)
- `role_covoiturage` (VARCHAR(20)) : Rôle covoiturage (Passager/Chauffeur/Les deux)

**Table `role`**

- `role_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `libelle` (VARCHAR(50)) : Nom du rôle (Administrateur, Employé, Utilisateur)

**Table `voiture`**

- `voiture_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `modele` (VARCHAR(50)) : Modèle du véhicule
- `immatriculation` (VARCHAR(50), UNIQUE) : Immatriculation
- `energie` (VARCHAR(50)) : Type d'énergie (redondant)
- `couleur` (VARCHAR(50)) : Couleur du véhicule
- `date_premire_immatriculation` (VARCHAR(50)) : Date première immatriculation
- `marque_id` (INT, FOREIGN KEY) : Référence à la marque
- `user_id` (INT, FOREIGN KEY) : Propriétaire du véhicule
- `energie_id` (INT, FOREIGN KEY) : Type d'énergie

**Table `marque`**

- `marque_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `libelle` (VARCHAR(50)) : Nom de la marque (Renault, Peugeot, Tesla, etc.)
- `voiture_id` (INT, FOREIGN KEY) : Liaison optionnelle

**Table `energie`**

- `energie_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `libelle` (VARCHAR(50)) : Type (Essence, Diesel, Electrique)

**Table `covoiturage`**

- `covoiturage_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `date_depart` (DATE) : Date de départ
- `heure_depart` (DATE) : Heure de départ (format adapté)
- `lieu_depart` (VARCHAR(50)) : Ville de départ
- `date_arrivee` (DATE) : Date d'arrivée
- `heure_arrivee` (DATE) : Heure d'arrivée
- `lieu_arrivee` (VARCHAR(50)) : Ville d'arrivée
- `statut` (TINYINT(1)) : Statut (1=En attente, 2=En cours, 3=Terminé)
- `nb_place` (INT) : Nombre de places disponibles
- `prix_personne` (FLOAT) : Prix par personne
- `user_id` (INT, FOREIGN KEY) : Chauffeur
- `voiture_id` (INT, FOREIGN KEY) : Véhicule utilisé
- `duree` (INT, NULL) : Durée du trajet en minutes (optionnel)

**Table `statuts`**

- `statuts_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `libelle` (VARCHAR(50)) : Libellé du statut (En cours, En attente, Terminé)

**Table `avis`**

- `avis_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `commentaire` (VARCHAR(50)) : Commentaire de l'avis
- `note` (VARCHAR(50)) : Note (sur 5 étoiles)
- `statut` (VARCHAR(50)) : Statut de publication
- `user_id` (INT, FOREIGN KEY) : Auteur de l'avis

**Contraintes d'intégrité référentielle**

- Clés étrangères avec `ON DELETE RESTRICT` et `ON UPDATE RESTRICT`
- Protection contre la suppression accidentelle de données
- Cohérence des données assurée

**Index**

- Index sur les clés primaires et étrangères
- Index sur les colonnes de recherche fréquente (email, pseudo)

#### 3.1.3 Frontend

**HTML5**

- **Version** : HTML5 (Standard)
- **Fonctionnalités utilisées** :
  - Sémantique HTML5 (section, nav, article)
  - Formulaires avec validation HTML5
  - Élément `<datalist>` pour l'autocomplétion
  - Attributs `required`, `type`, `placeholder`

**CSS3**

- **Fichier principal** : `/assets/css/style.css`
- **Fonctionnalités** :
  - Feuilles de style personnalisées
  - Responsive design (Media queries)
  - Animations et transitions CSS
  - Flexbox et Grid Layout

**Bootstrap 5.3.3**

- **CDN** : `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3`
- **Composants utilisés** :
  - Grid system (responsive)
  - Composants : Navbar, Cards, Buttons, Forms, Alerts, Breadcrumbs
  - Carrousel (Carousel)
  - Badges
  - Tables responsives
  - Modal (pour confirmations)
- **Icônes** : Bootstrap Icons 1.11.3
- **Justification** : Framework CSS pour développement rapide et design cohérent

**JavaScript (ES6+)**

- **Fichiers JavaScript** :
  - `/assets/js/index.js` : Logique principale
  - `/assets/js/chart.js` : Gestion des graphiques
  - `/assets/js/avis.js` : Gestion du carrousel d'avis
  - `/assets/js/photo-preview.js` : Prévisualisation des photos
  - `/assets/js/temps_trajet.js` : Calcul du temps de trajet

**Fonctionnalités JavaScript** :

- Manipulation du DOM
- Gestion des événements
- Validation côté client
- Communication avec l'API (via données PHP passées en JSON)
- Prévisualisation d'images avant upload
- Calcul automatique de la durée de trajet

**Chart.js**

- **Version** : Latest (via CDN)
- **Utilisation** : Graphiques de statistiques pour le tableau de bord admin
- **Type de graphique** : Graphique linéaire (line chart)
- **Fonctionnalités** :
  - Visualisation de l'évolution des covoiturages sur 30 jours
  - Données dynamiques depuis PHP (via `window.covoituragesData`)
  - Responsive et interactif

**Google Fonts**

- **Police utilisée** : Poppins
- **CDN** : `https://fonts.googleapis.com`

#### 3.1.4 Serveur et environnement

**Serveur Web**

- **Apache** (version compatible PHP 8.2)
- **Configuration** :
  - Support des réécritures d'URL (.htaccess)
  - Support des sessions PHP
  - Configuration personnalisée via `.htaccess` (si nécessaire)

**Docker (Optionnel)**

- **Fichier** : `docker-compose.yml`
- **Services** :
  - **Base de données MySQL 8.0** :
    - Port : 3307 (host) -> 3306 (container)
    - Volume persistant pour les données
    - Initialisation automatique avec `ecoride.sql`
  - **Apache + PHP 8.2** :
    - Port : 8000 (host) -> 80 (container)
    - Extensions : pdo_mysql, mysqli
    - Volume monté depuis le répertoire du projet
  - **phpMyAdmin** :
    - Port : 8080 (host) -> 80 (container)
    - Interface de gestion MySQL
- **Environnement** : Détection automatique Docker vs local
- **Configuration** : Variables d'environnement pour différencier les environnements

**Environnements de développement**

- **WAMP** : Windows, Apache, MySQL, PHP
- **XAMPP** : Multi-plateforme
- **Configuration locale** :
  - Base de données : `localhost`
  - Utilisateur : `root`
  - Mot de passe : (vide par défaut)

**Configuration**

- **Fichier** : `lib/config.php`
- **Détection d'environnement** :
  - Vérification via `getenv('DOCKER_ENV')` ou fichier `/.dockerenv`
  - Configuration adaptative selon l'environnement
- **Paramètres de connexion** :
  - Host (Docker : `db`, Local : `localhost`)
  - Base de données : `ecoride`
  - Utilisateur et mot de passe selon l'environnement

#### 3.1.5 Sécurité

**Authentification et sessions**

- **Hashage des mots de passe** :
  - Algorithme : bcrypt (via `password_hash()`)
  - Coût : 10 (équilibre sécurité/performance)
- **Gestion des sessions** :
  - Durée : 3600 secondes (1 heure)
  - Cookie HttpOnly : Oui (protection XSS)
  - Cookie Secure : Recommandé en production (HTTPS)
  - Path : `/` (accessible sur tout le site)

**Protection contre les injections SQL**

- **Méthode** : Requêtes préparées PDO
- **Exemple** :
  ```php
  $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :email");
  $stmt->execute(['email' => $email]);
  ```
- **Désactivation de l'émulation des préparations** : `PDO::ATTR_EMULATE_PREPARES => false`

**Protection XSS (Cross-Site Scripting)**

- **Méthode** : Échappement HTML avec `htmlspecialchars()`
- **Utilisation** : Toutes les données utilisateur affichées
- **Exemple** :
  ```php
  <?= htmlspecialchars($user['nom'], ENT_QUOTES, 'UTF-8') ?>
  ```

**Validation des données**

- **Côté serveur** : Validation PHP stricte
- **Côté client** : Validation HTML5 et JavaScript
- **Types validés** :
  - Email (format)
  - Dates (format ISO)
  - Nombres (bornes min/max)
  - Chaînes de caractères (longueur)

**Contrôle d'accès**

- **Vérifications de session** : Sur toutes les pages protégées
- **Vérifications de rôle** : Pour les fonctions administratives
- **Messages d'erreur** : En cas d'accès non autorisé
- **Redirections** : Vers la page de connexion si non authentifié

**Gestion des erreurs**

- **Mode de développement** : Affichage des erreurs
- **Mode production** : Logging des erreurs, messages génériques
- **Exceptions PDO** : Capturées et gérées proprement

### 3.2 Architecture du système

#### 3.2.1 Structure des fichiers

```
ecoride_ecf/
├── assets/                    # Ressources statiques
│   ├── css/
│   │   └── style.css         # Feuilles de style personnalisées
│   ├── js/
│   │   ├── index.js         # Scripts principaux
│   │   ├── chart.js         # Gestion des graphiques
│   │   ├── avis.js          # Carrousel d'avis
│   │   ├── photo-preview.js # Prévisualisation photos
│   │   └── temps_trajet.js  # Calcul durée trajet
│   ├── img/                  # Images du site
│   └── logo/                 # Logos de l'application
├── docker/                    # Configuration Docker
│   ├── apache/
│   │   └── 000-default.conf # Configuration Apache
│   └── php/
│       ├── Dockerfile       # Image PHP personnalisée
│       └── php.ini          # Configuration PHP
├── Documentation/            # Documentation du projet
│   ├── Diagramme de cas d'utilisation EcoRide.drawio
│   ├── Diagramme de séquence EcoRide.drawio
│   └── [PDFs des diagrammes]
├── lib/                      # Bibliothèques PHP (Modèle)
│   ├── config.php           # Configuration BDD selon environnement
│   ├── pdo.php              # Connexion PDO à MySQL
│   ├── session.php          # Gestion des sessions
│   ├── user.php             # Fonctions utilisateur
│   ├── update_user.php      # Mise à jour profil
│   └── create_employee.php  # Création comptes employés
├── pages/                    # Pages de l'application (Contrôleur/Vue)
│   ├── admin.php            # Tableau de bord administrateur
│   ├── avis.php             # Liste des avis
│   ├── contact.php          # Page de contact
│   ├── covoiturage.php      # Gestion covoiturage/chauffeurs
│   ├── deposer_avis.php     # Formulaire avis
│   ├── detail_covoiturage.php # Détails d'un trajet
│   ├── employe.php          # Espace employé
│   ├── mes_reservations.php # Réservations utilisateur
│   ├── mes_trajets.php      # Trajets créés par l'utilisateur
│   ├── mes_voitures.php     # Gestion des véhicules
│   ├── mentions_legales.php # Mentions légales
│   ├── publish.php          # Page publique pour proposer trajets
│   ├── suggestions.php      # Suggestions de trajets
│   ├── trajets.php          # Liste et recherche de trajets
│   └── user_count.php       # Gestion du compte utilisateur
├── scripts/                  # Scripts utilitaires (vide pour l'instant)
├── templates/                # Templates réutilisables (Vue)
│   ├── header.php           # En-tête commun
│   └── footer.php           # Pied de page commun
├── utils/                    # Utilitaires (vide pour l'instant)
├── docker-compose.yml       # Configuration Docker Compose
├── ecoride.sql              # Dump SQL de la base de données
├── index.php                # Page d'accueil
├── login.php                # Page de connexion
├── logout.php               # Script de déconnexion
├── README-Docker.md         # Documentation Docker
└── readme.md                # Documentation générale
```

#### 3.2.2 Flux de données

**Flux d'authentification**

1. Utilisateur saisit email/mot de passe
2. Validation côté client (JavaScript)
3. Envoi POST vers `login.php`
4. Vérification en base de données
5. Comparaison du hash du mot de passe
6. Création de session avec données utilisateur
7. Redirection selon le rôle

**Flux de création de trajet**

1. Utilisateur connecté accède à "Mon Covoiturage"
2. Sélection d'un véhicule (ou création)
3. Remplissage du formulaire de trajet
4. Validation côté client et serveur
5. Insertion en base de données
6. Mise à jour de l'affichage

**Flux de recherche de trajet**

1. Utilisateur saisit critères sur la page d'accueil
2. Envoi POST vers `index.php`
3. Redirection vers `trajets.php` avec paramètres GET
4. Requête SQL avec filtres
5. Affichage des résultats

#### 3.2.3 Couches d'abstraction

**Couche d'accès aux données**

- **Fichier** : `lib/pdo.php`
- **Responsabilité** :
  - Création de la connexion PDO
  - Configuration des options PDO
  - Gestion des erreurs de connexion

**Couche de session**

- **Fichier** : `lib/session.php`
- **Responsabilité** :
  - Démarrage de session sécurisée
  - Fonctions utilitaires (`isUserConnected()`, `requireLogin()`)
  - Configuration des cookies de session

**Couche de configuration**

- **Fichier** : `lib/config.php`
- **Responsabilité** :
  - Définition des constantes de connexion BDD
  - Détection de l'environnement
  - Configuration adaptative

### 3.3 Interfaces utilisateur

#### 3.3.1 Design et ergonomie

**Principes de design**

- **Responsive** : Adaptation mobile, tablette, desktop
- **Accessibilité** : Contraste, navigation au clavier
- **Simplicité** : Interface claire et intuitive
- **Cohérence** : Design uniforme avec Bootstrap

**Composants visuels**

- **Navbar** : Navigation principale, fixe en haut
- **Hero section** : Section d'accueil avec recherche
- **Cards** : Affichage des trajets et informations
- **Carrousel** : Présentation des avis
- **Formulaires** : Design Bootstrap avec validation visuelle
- **Tableaux** : Affichage des données avec badges et icônes

**Palette de couleurs**

- Couleurs Bootstrap par défaut (personnalisable)
- Thème cohérent avec l'identité "écologique"

#### 3.3.2 Responsive design

**Breakpoints Bootstrap**

- Extra small (< 576px) : Mobile
- Small (≥ 576px) : Mobile large
- Medium (≥ 768px) : Tablette
- Large (≥ 992px) : Desktop
- Extra large (≥ 1200px) : Desktop large

**Adaptations**

- Menu hamburger sur mobile
- Grille responsive pour les cartes de trajets
- Formulaires adaptatifs
- Images responsives avec `img-fluid`

---

## 4. CONTRAINTES ET EXIGENCES

### 4.1 Contraintes techniques

**Performance**

- Temps de chargement des pages < 3 secondes
- Requêtes SQL optimisées (index sur colonnes fréquemment utilisées)
- Mise en cache des requêtes répétitives (si nécessaire)

**Compatibilité**

- Navigateurs supportés :
  - Chrome (dernière version)
  - Firefox (dernière version)
  - Safari (dernière version)
  - Edge (dernière version)
- PHP : Version 8.0 minimum
- MySQL : Version 8.0 minimum

**Sécurité**

- Conformité RGPD pour les données personnelles
- Protection contre les principales vulnérabilités web (OWASP Top 10)
- Chiffrement des mots de passe
- Validation stricte des données

### 4.2 Exigences fonctionnelles

**Obligatoires**

- Inscription et authentification des utilisateurs
- Gestion des rôles et permissions
- Création et recherche de trajets
- Gestion des véhicules
- Système de réservation
- Système d'avis
- Tableau de bord administrateur

**Optionnelles (futures évolutions)**

- Système de paiement en ligne
- Application mobile
- Intégration cartographique (Google Maps / OpenStreetMap)
- Notifications par email
- Chat en temps réel entre utilisateurs
- Système de fidélité

### 4.3 Exigences non fonctionnelles

**Maintenabilité**

- Code structuré et commenté
- Respect des conventions de nommage
- Documentation complète

**Scalabilité**

- Architecture extensible
- Base de données normalisée
- Possibilité d'ajouter de nouvelles fonctionnalités

**Disponibilité**

- Application accessible 24/7 (en production)
- Gestion des erreurs propre
- Logs pour le debugging

---

## 5. PLANIFICATION ET LIVRABLES

### 5.1 Livrables

**Code source**

- Tous les fichiers PHP, HTML, CSS, JavaScript
- Fichiers de configuration
- Scripts SQL

**Documentation**

- README.md avec instructions d'installation
- Cahier des charges (ce document)
- Diagrammes UML (cas d'utilisation, séquence)
- Commentaires dans le code

**Base de données**

- Script SQL de création (`ecoride.sql`)
- Données de test (optionnelles)

### 5.2 Tests et validation

**Tests fonctionnels**

- Test de toutes les fonctionnalités principales
- Test des droits d'accès selon les rôles
- Validation des formulaires

**Tests de sécurité**

- Vérification de la protection SQL Injection
- Vérification de la protection XSS
- Test de l'authentification

**Tests de compatibilité**

- Test sur différents navigateurs
- Test responsive sur différents appareils

---

## 6. CONCLUSION

Ce cahier des charges détaille l'ensemble des spécifications fonctionnelles et techniques du projet **EcoRide**, une plateforme web de covoiturage écologique développée dans le cadre d'un ECF.

**Points clés du projet** :

- ✅ Application web moderne avec PHP 8.2 et MySQL 8.0
- ✅ Interface responsive avec Bootstrap 5
- ✅ Système d'authentification sécurisé
- ✅ Gestion complète des trajets, véhicules et réservations
- ✅ Tableau de bord administrateur avec statistiques
- ✅ Architecture extensible et maintenable

**Technologies principales** :

- Backend : PHP 8.2+ (PDO, Sessions)
- Base de données : MySQL 8.0 (InnoDB)
- Frontend : HTML5, CSS3, JavaScript ES6+, Bootstrap 5.3.3
- Visualisation : Chart.js
- Infrastructure : Docker (optionnel), Apache

Le projet respecte les bonnes pratiques de développement web moderne et offre une base solide pour de futures évolutions.

---

**Document généré le** : 2025  
**Version** : 1.0  
**Auteur** : Équipe de développement EcoRide

