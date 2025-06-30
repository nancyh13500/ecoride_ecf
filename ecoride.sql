-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 08 mai 2025 à 08:47
-- Version du serveur : 8.3.0
-- Version de PHP : 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;

--
-- Base de données : `ecoride`
--

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

DROP TABLE IF EXISTS `avis`;

CREATE TABLE IF NOT EXISTS `avis` (
    `avis_id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `note` int NOT NULL DEFAULT 5,
    `commentaire` text,
    `date_avis` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`avis_id`),
    KEY `avis_ibfk_1` (`user_id`),
    CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `covoiturage`
--

DROP TABLE IF EXISTS `covoiturage`;

CREATE TABLE IF NOT EXISTS `covoiturage` (
    `covoiturage_id` int NOT NULL AUTO_INCREMENT,
    `date_depart` date NOT NULL,
    `heure_depart` time NOT NULL,
    `lieu_depart` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
    `lieu_arrivee` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
    `nb_place` int NOT NULL,
    `prix_personne` decimal(10, 2) NOT NULL,
    `statut` tinyint(1) NOT NULL DEFAULT '1',
    `user_id` int NOT NULL,
    `voiture_id` int NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`covoiturage_id`),
    KEY `covoiturage_ibfk_1` (`user_id`),
    KEY `covoiturage_ibfk_2` (`voiture_id`),
    CONSTRAINT `covoiturage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `covoiturage_ibfk_2` FOREIGN KEY (`voiture_id`) REFERENCES `voiture` (`voiture_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `energie`
--

DROP TABLE IF EXISTS `energie`;

CREATE TABLE IF NOT EXISTS `energie` (
    `energie_id` int NOT NULL AUTO_INCREMENT,
    `libelle` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    PRIMARY KEY (`energie_id`)
) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `energie`
--

INSERT INTO
    `energie` (`energie_id`, `libelle`)
VALUES (1, 'Essence'),
    (2, 'Diesel'),
    (3, 'Elèctrique');

-- --------------------------------------------------------

--
-- Structure de la table `marque`
--

DROP TABLE IF EXISTS `marque`;

CREATE TABLE IF NOT EXISTS `marque` (
    `marque_id` int NOT NULL AUTO_INCREMENT,
    `libelle` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `voiture_id` int NOT NULL,
    PRIMARY KEY (`marque_id`),
    KEY `marque_ibfk_1` (`voiture_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role`
--

DROP TABLE IF EXISTS `role`;

CREATE TABLE IF NOT EXISTS `role` (
    `role_id` int NOT NULL AUTO_INCREMENT,
    `libelle` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    PRIMARY KEY (`role_id`)
) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `role`
--

INSERT INTO
    `role` (`role_id`, `libelle`)
VALUES (1, 'Administrateur'),
    (2, 'Employé'),
    (3, 'Utilisateur');

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

DROP TABLE IF EXISTS `user`;

CREATE TABLE IF NOT EXISTS `user` (
    `user_id` int NOT NULL AUTO_INCREMENT,
    `nom` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `prenom` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `email` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `telephone` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `adresse` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `date_naissance` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `photo` blob NOT NULL,
    `pseudo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `role_id` int NOT NULL,
    PRIMARY KEY (`user_id`),
    KEY `user_ibfk_1` (`role_id`)
) ENGINE = InnoDB AUTO_INCREMENT = 2 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO
    `user` (
        `user_id`,
        `nom`,
        `prenom`,
        `email`,
        `password`,
        `telephone`,
        `adresse`,
        `date_naissance`,
        `photo`,
        `pseudo`,
        `role_id`
    )
VALUES (
        1,
        'test',
        'test',
        'test@test.com',
        '\'$2y$10$slFhjE6RYtM9dAhV7dbI2eipc.Mj939Ez0rrGAz10d',
        '',
        '',
        '',
        '',
        '',
        1
    );

-- --------------------------------------------------------

--
-- Structure de la table `voiture`
--

DROP TABLE IF EXISTS `voiture`;

CREATE TABLE IF NOT EXISTS `voiture` (
    `voiture_id` int NOT NULL AUTO_INCREMENT,
    `modele` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `immatriculation` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `energie` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `couleur` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `date_premire_immatriculation` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `marque_id` int NOT NULL,
    `user_id` int NOT NULL,
    `energie_id` int NOT NULL,
    PRIMARY KEY (`voiture_id`),
    KEY `voiture_ibfk_1` (`marque_id`),
    KEY `voiture_ibfk_2` (`user_id`),
    KEY `voiture_ibfk_3` (`energie_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `covoiturage`
--
ALTER TABLE `covoiturage`
ADD CONSTRAINT `covoiturage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
ADD CONSTRAINT `covoiturage_ibfk_2` FOREIGN KEY (`voiture_id`) REFERENCES `voiture` (`voiture_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Contraintes pour la table `user`
--
ALTER TABLE `user`
ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Contraintes pour la table `voiture`
--
ALTER TABLE `voiture`
ADD CONSTRAINT `voiture_ibfk_1` FOREIGN KEY (`marque_id`) REFERENCES `marque` (`marque_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
ADD CONSTRAINT `voiture_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
ADD CONSTRAINT `voiture_ibfk_3` FOREIGN KEY (`energie_id`) REFERENCES `energie` (`energie_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;

INSERT INTO
    `marque` (`marque_id`, `libelle`)
VALUES (1, 'Renault'),
    (2, 'Peugeot'),
    (3, 'Tesla'),
    (4, 'Mercedes'),
    (5, 'BMW'),
    (6, 'Porshe'),
    (7, 'Kia'),
    (8, 'Audi'),
    (9, 'Citroen'),
    (10, 'Volkswagen'),
    (11, 'Toyota'),
    (12, 'Ford'),
    (13, 'Fiat');

ALTER TABLE covoiturage ADD COLUMN duree INT DEFAULT NULL;

INSERT INTO marque (libelle, voiture_id) VALUES ('SEAT', 14);

INSERT INTO marque (libelle, voiture_id) VALUES ('Nissan', 15);

CREATE TABLE `statuts` (
    `statut_id` int NOT NULL AUTO_INCREMENT,
    `libelle` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    PRIMARY KEY (`statut_id`)
)

insert into
    `statuts` (`statuts_id`, `libelle`)
values (1, 'En cours'),
    (2, 'En attente'),
    (3, 'Terminé');

-- Insertion de quelques avis d'exemple
INSERT INTO
    `avis` (
        `user_id`,
        `note`,
        `commentaire`,
        `date_avis`
    )
VALUES (
        1,
        5,
        'Excellent service, très pratique pour mes déplacements quotidiens !',
        '2025-01-15 10:30:00'
    ),
    (
        2,
        4,
        'Très bonne expérience, les chauffeurs sont ponctuels.',
        '2025-01-20 14:15:00'
    ),
    (
        3,
        5,
        'Je recommande vivement, économies garanties !',
        '2025-01-25 09:45:00'
    );