-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 29 août 2025 à 16:06
-- Version du serveur : 8.3.0
-- Version de PHP : 8.2.18

-- phpMyAdmin SQL Dump
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

--
-- Base de données : `ecoride`
--²²

-- Désactiver temporairement les contraintes
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- TABLES SANS DÉPENDANCES D'ABORD
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

INSERT INTO
    `role` (`role_id`, `libelle`)
VALUES (1, 'Administrateur'),
    (2, 'Employé'),
    (3, 'Utilisateur');

--
-- Structure de la table `energie`
--
DROP TABLE IF EXISTS `energie`;

CREATE TABLE IF NOT EXISTS `energie` (
    `energie_id` int NOT NULL AUTO_INCREMENT,
    `libelle` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    PRIMARY KEY (`energie_id`)
) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `energie` (`energie_id`, `libelle`)
VALUES (1, 'Essence'),
    (2, 'Diesel'),
    (3, 'Electrique');

--
-- Structure de la table `statuts`
--
DROP TABLE IF EXISTS `statuts`;

CREATE TABLE IF NOT EXISTS `statuts` (
    `statuts_id` int NOT NULL AUTO_INCREMENT,
    `libelle` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    PRIMARY KEY (`statuts_id`)
) ENGINE = MyISAM AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `statuts` (`statuts_id`, `libelle`)
VALUES (1, 'En cours'),
    (2, 'En attente'),
    (3, 'Terminé');

-- --------------------------------------------------------
-- TABLE USER (dépend de role)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user`;

CREATE TABLE IF NOT EXISTS `user` (
    `user_id` int NOT NULL AUTO_INCREMENT,
    `nom` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `prenom` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `email` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `telephone` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `adresse` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `date_naissance` date NOT NULL,
    `photo` blob NOT NULL,
    `pseudo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `role_id` int NOT NULL,
    `role_covoiturage` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `credits` int NOT NULL DEFAULT 20,
    PRIMARY KEY (`user_id`),
    KEY `user_ibfk_1` (`role_id`),
    CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 8 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `user`
VALUES (
        1,
        'test',
        'test',
        'test@test.com',
        '$2y$10$slFhjE6RYtM9dAhV7dbI2eipc.Mj939Ez0rrGAz10d',
        '',
        '',
        '0000-00-00',
        '',
        '',
        1,
        'Passager',
        20
    ),
    (
        3,
        'HANOYAN',
        'nancy',
        'nancy.hanoyan@free.fr',
        '$2y$10$0k5x8z4CysSJIoMM/G7tsOUca70vKhEhvmv10wmPuSiVXVojN40TK',
        '0616514174',
        '1 Rue Vendôme',
        '2005-05-26',
        '',
        'test',
        1,
        'Passager',
        20
    ),
    (
        4,
        'nancy',
        'nancy',
        'dadounan@hotmail.com',
        '$2y$10$dj36KDF7wle2P87vom3u5uOL8AXt29yaJcoI2tW.OmNkmwqUVs.Fe',
        '',
        '',
        '0000-00-00',
        '',
        'nancy',
        3,
        'Passager',
        20
    ),
    (
        5,
        'nancy',
        'nancy',
        'nancy@nancy.com',
        '$2y$10$hD1Mkm28Vtv3ih/sUhk8HOF/.AesOjNekBo86y8DSrfWSCQfgv0YG',
        '0123456789',
        'Lyon',
        '0000-00-00',
        '',
        'nancy nancy',
        3,
        'Les deux',
        20
    ),
    (
        6,
        'BAPTISTE',
        'baptiste',
        'baptiste@mail.com',
        '$2y$10$OI4CgMBPCFZlB3XEGoQpWuUH.Pyoi/G5MDjjPXVvL4drm6BGfCc0u',
        '',
        '',
        '0000-00-00',
        '',
        'baptiste',
        3,
        'Passager',
        20
    ),
    (
        7,
        'nancy',
        'nancy',
        'nancy@mail.fr',
        '$2y$10$moLhOF6lbz4/yMTYh5klmuHTPImPqguGKL/kOyXcjUOAqJK.sclcq',
        '000000000',
        'rue du web, 05000 Ville',
        '2004-05-22',
        '',
        'nancy nancy',
        3,
        'Passager',
        20
    );

--
-- Structure de la table `avis` (dépend de user)
--
DROP TABLE IF EXISTS `avis`;

CREATE TABLE IF NOT EXISTS `avis` (
    `avis_id` int NOT NULL AUTO_INCREMENT,
    `commentaire` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
    `note` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `statut` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `user_id` int NOT NULL,
    PRIMARY KEY (`avis_id`),
    KEY `avis_ibfk_1` (`user_id`),
    CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
-- TABLE MARQUE (temporairement sans contrainte voiture)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `marque`;

CREATE TABLE IF NOT EXISTS `marque` (
    `marque_id` int NOT NULL AUTO_INCREMENT,
    `libelle` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `voiture_id` int NOT NULL,
    PRIMARY KEY (`marque_id`)
) ENGINE = InnoDB AUTO_INCREMENT = 16 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `marque`
VALUES (1, 'Renault', 0),
    (2, 'Peugeot', 0),
    (3, 'Tesla', 0),
    (4, 'Mercedes', 0),
    (5, 'BMW', 0),
    (6, 'Porsche', 0),
    (7, 'Kia', 0),
    (8, 'Audi', 0),
    (9, 'Citroen', 0),
    (10, 'Volkswagen', 0),
    (11, 'Toyota', 0),
    (12, 'Ford', 0),
    (13, 'Fiat', 0),
    (14, 'Séat', 14),
    (15, 'Nissan', 15);

-- --------------------------------------------------------
-- TABLE VOITURE (dépend de marque, user, energie)
-- --------------------------------------------------------

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
    KEY `voiture_ibfk_3` (`energie_id`),
    CONSTRAINT `voiture_ibfk_1` FOREIGN KEY (`marque_id`) REFERENCES `marque` (`marque_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `voiture_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `voiture_ibfk_3` FOREIGN KEY (`energie_id`) REFERENCES `energie` (`energie_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `voiture`
VALUES (
        3,
        '500S',
        'XX-500-ZZ',
        '',
        'Grise',
        '2024-12-07',
        13,
        5,
        3
    ),
    (
        4,
        'Ateca',
        'FV-652-PD',
        '',
        'Grise',
        '2024-06-04',
        14,
        5,
        1
    );

-- --------------------------------------------------------
-- TABLE COVOITURAGE (dépend de user, voiture)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `covoiturage`;

CREATE TABLE IF NOT EXISTS `covoiturage` (
    `covoiturage_id` int NOT NULL AUTO_INCREMENT,
    `date_depart` date NOT NULL,
    `heure_depart` time NOT NULL,
    `lieu_depart` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `date_arrivee` date NOT NULL,
    `heure_arrivee` time NOT NULL,
    `lieu_arrivee` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `statut` tinyint(1) DEFAULT NULL,
    `nb_place` int NOT NULL,
    `prix_personne` float NOT NULL,
    `user_id` int NOT NULL,
    `voiture_id` int NOT NULL,
    `duree` int DEFAULT NULL,
    PRIMARY KEY (`covoiturage_id`),
    KEY `covoiturage_ibfk_1` (`user_id`),
    KEY `covoiturage_ibfk_2` (`voiture_id`),
    CONSTRAINT `covoiturage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `covoiturage_ibfk_2` FOREIGN KEY (`voiture_id`) REFERENCES `voiture` (`voiture_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 12 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `covoiturage`
VALUES (
        8,
        '2025-06-28',
        '00:00:00',
        'martigues',
        '2025-06-28',
        '00:00:00',
        'marseille',
        3,
        1,
        5,
        5,
        3,
        NULL
    ),
    (
        9,
        '2025-07-08',
        '00:00:00',
        'martigues',
        '2025-07-08',
        '00:00:00',
        'marseille',
        3,
        1,
        5,
        5,
        3,
        NULL
    ),
    (
        10,
        '2025-07-07',
        '00:00:00',
        'lyon',
        '2025-07-07',
        '00:00:00',
        'paris',
        3,
        1,
        10,
        5,
        3,
        NULL
    ),
    (
        11,
        '2025-08-27',
        '00:00:00',
        'Lyon',
        '0000-00-00',
        '00:00:00',
        'Chaponost',
        NULL,
        2,
        5,
        5,
        3,
        NULL
    );

-- --------------------------------------------------------
-- TABLE RESERVATIONS (dépend de user, covoiturage)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `reservations`;

CREATE TABLE IF NOT EXISTS `reservations` (
    `reservation_id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `covoiturage_id` int NOT NULL,
    `date_reservation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `nb_places_reservees` int NOT NULL DEFAULT 1,
    `prix_total` float NOT NULL,
    `statut` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'En attente',
    PRIMARY KEY (`reservation_id`),
    KEY `reservations_ibfk_1` (`user_id`),
    KEY `reservations_ibfk_2` (`covoiturage_id`),
    CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`covoiturage_id`) REFERENCES `covoiturage` (`covoiturage_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
-- TABLE SITE_CREDITS
-- --------------------------------------------------------

DROP TABLE IF EXISTS `site_credits`;

CREATE TABLE IF NOT EXISTS `site_credits` (
    `site_credits_id` int NOT NULL AUTO_INCREMENT,
    `total_credits` int NOT NULL DEFAULT 0,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`site_credits_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO `site_credits` (`total_credits`) VALUES (0);

-- Réactiver les contraintes
SET FOREIGN_KEY_CHECKS = 1;