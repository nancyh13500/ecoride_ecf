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
-- TABLE VILLE
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ville`;

CREATE TABLE IF NOT EXISTS `ville` (
    `ville_id` INT NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(100) COLLATE utf8mb4_general_ci NOT NULL,
    `code_postal` VARCHAR(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `code_insee` VARCHAR(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `latitude` DECIMAL(10, 7) DEFAULT NULL,
    `longitude` DECIMAL(10, 7) DEFAULT NULL,
    PRIMARY KEY (`ville_id`),
    INDEX `idx_nom` (`nom`),
    INDEX `idx_code_postal` (`code_postal`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `ville` (
        `nom`,
        `code_postal`,
        `code_insee`,
        `latitude`,
        `longitude`
    )
VALUES (
        'Aix-en-Provence',
        '13100',
        '13001',
        43.5297000,
        5.4474000
    ),
    (
        'Ajaccio',
        '20000',
        '2A004',
        41.9192000,
        8.7386000
    ),
    (
        'Amiens',
        '80000',
        '80021',
        49.8941000,
        2.2957000
    ),
    (
        'Angers',
        '49000',
        '49007',
        47.4784000,
        -0.5632000
    ),
    (
        'Annecy',
        '74000',
        '74010',
        45.8992000,
        6.1294000
    ),
    (
        'Antibes',
        '06600',
        '06004',
        43.5808000,
        7.1239000
    ),
    (
        'Argenteuil',
        '95100',
        '95018',
        48.9474000,
        2.2482000
    ),
    (
        'Arles',
        '13200',
        '13004',
        43.6766000,
        4.6280000
    ),
    (
        'Avignon',
        '84000',
        '84007',
        43.9493000,
        4.8055000
    ),
    (
        'Besançon',
        '25000',
        '25056',
        47.2380000,
        6.0243000
    ),
    (
        'Béziers',
        '34500',
        '34032',
        43.3441000,
        3.2196000
    ),
    (
        'Bordeaux',
        '33000',
        '33063',
        44.8378000,
        -0.5792000
    ),
    (
        'Boulogne-Billancourt',
        '92100',
        '92012',
        48.8350000,
        2.2412000
    ),
    (
        'Bourges',
        '18000',
        '18033',
        47.0810000,
        2.3988000
    ),
    (
        'Brest',
        '29200',
        '29019',
        48.3905000,
        -4.4860000
    ),
    (
        'Caen',
        '14000',
        '14118',
        49.1829000,
        -0.3707000
    ),
    (
        'Calais',
        '62100',
        '62193',
        50.9513000,
        1.8587000
    ),
    (
        'Cannes',
        '06400',
        '06029',
        43.5528000,
        7.0174000
    ),
    (
        'Chambéry',
        '73000',
        '73065',
        45.5646000,
        5.9178000
    ),
    (
        'Cherbourg-en-Cotentin',
        '50100',
        '50129',
        49.6386000,
        -1.6164000
    ),
    (
        'Clermont-Ferrand',
        '63000',
        '63113',
        45.7772000,
        3.0870000
    ),
    (
        'Colmar',
        '68000',
        '68066',
        48.0794000,
        7.3585000
    ),
    (
        'Créteil',
        '94000',
        '94028',
        48.7904000,
        2.4554000
    ),
    (
        'Dijon',
        '21000',
        '21231',
        47.3220000,
        5.0415000
    ),
    (
        'Dunkerque',
        '59140',
        '59183',
        51.0344000,
        2.3768000
    ),
    (
        'Évry-Courcouronnes',
        '91000',
        '91228',
        48.6331000,
        2.4310000
    ),
    (
        'Grenoble',
        '38000',
        '38185',
        45.1885000,
        5.7245000
    ),
    (
        'La Rochelle',
        '17000',
        '17300',
        46.1591000,
        -1.1520000
    ),
    (
        'Le Havre',
        '76600',
        '76351',
        49.4944000,
        0.1079000
    ),
    (
        'Le Mans',
        '72000',
        '72181',
        48.0061000,
        0.1996000
    ),
    (
        'Lille',
        '59000',
        '59350',
        50.6292000,
        3.0573000
    ),
    (
        'Limoges',
        '87000',
        '87085',
        45.8336000,
        1.2611000
    ),
    (
        'Lorient',
        '56100',
        '56121',
        47.7482000,
        -3.3702000
    ),
    (
        'Lyon',
        '69000',
        '69123',
        45.7640000,
        4.8357000
    ),
    (
        'Marseille',
        '13000',
        '13055',
        43.2965000,
        5.3698000
    ),
    (
        'Martigues',
        '13500',
        '13056',
        43.4053000,
        5.0479000
    ),
    (
        'Metz',
        '57000',
        '57463',
        49.1193000,
        6.1757000
    ),
    (
        'Montpellier',
        '34000',
        '34172',
        43.6108000,
        3.8767000
    ),
    (
        'Montreuil',
        '93100',
        '93048',
        48.8638000,
        2.4485000
    ),
    (
        'Mulhouse',
        '68100',
        '68224',
        47.7508000,
        7.3359000
    ),
    (
        'Nancy',
        '54000',
        '54395',
        48.6921000,
        6.1844000
    ),
    (
        'Nantes',
        '44000',
        '44109',
        47.2184000,
        -1.5536000
    ),
    (
        'Nice',
        '06000',
        '06088',
        43.7102000,
        7.2620000
    ),
    (
        'Nîmes',
        '30000',
        '30189',
        43.8367000,
        4.3601000
    ),
    (
        'Orléans',
        '45000',
        '45234',
        47.9029000,
        1.9039000
    ),
    (
        'Paris',
        '75000',
        '75056',
        48.8566000,
        2.3522000
    ),
    (
        'Pau',
        '64000',
        '64445',
        43.2951000,
        -0.3708000
    ),
    (
        'Perpignan',
        '66000',
        '66136',
        42.6886000,
        2.8949000
    ),
    (
        'Poitiers',
        '86000',
        '86194',
        46.5802000,
        0.3404000
    ),
    (
        'Reims',
        '51100',
        '51454',
        49.2583000,
        4.0317000
    ),
    (
        'Rennes',
        '35000',
        '35238',
        48.1173000,
        -1.6778000
    ),
    (
        'Roubaix',
        '59100',
        '59512',
        50.6927000,
        3.1746000
    ),
    (
        'Rouen',
        '76000',
        '76540',
        49.4432000,
        1.0993000
    ),
    (
        'Saint-Denis',
        '93200',
        '93066',
        48.9362000,
        2.3574000
    ),
    (
        'Saint-Étienne',
        '42000',
        '42218',
        45.4397000,
        4.3872000
    ),
    (
        'Saint-Nazaire',
        '44600',
        '44184',
        47.2735000,
        -2.2138000
    ),
    (
        'Strasbourg',
        '67000',
        '67482',
        48.5734000,
        7.7521000
    ),
    (
        'Toulon',
        '83000',
        '83137',
        43.1242000,
        5.9280000
    ),
    (
        'Toulouse',
        '31000',
        '31555',
        43.6047000,
        1.4442000
    ),
    (
        'Tours',
        '37000',
        '37261',
        47.3941000,
        0.6848000
    ),
    (
        'Troyes',
        '10000',
        '10387',
        48.2973000,
        4.0744000
    ),
    (
        'Valence',
        '26000',
        '26362',
        44.9334000,
        4.8924000
    ),
    (
        'Versailles',
        '78000',
        '78646',
        48.8014000,
        2.1301000
    ),
    (
        'Villeurbanne',
        '69100',
        '69266',
        45.7665000,
        4.8795000
    );

-- --------------------------------------------------------
-- TABLE COVOITURAGE (dépend de user, voiture, ville)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `covoiturage`;

CREATE TABLE IF NOT EXISTS `covoiturage` (
    `covoiturage_id` int NOT NULL AUTO_INCREMENT,
    `date_depart` date NOT NULL,
    `heure_depart` time NOT NULL,
    `lieu_depart` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `ville_depart_id` INT DEFAULT NULL,
    `date_arrivee` date NOT NULL,
    `heure_arrivee` time NOT NULL,
    `lieu_arrivee` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `ville_arrivee_id` INT DEFAULT NULL,
    `distance_km` DECIMAL(8, 2) DEFAULT NULL COMMENT 'Distance totale en km (calculée via OSRM)',
    `co2_economise_kg` DECIMAL(8, 2) DEFAULT NULL COMMENT 'CO2 économisé par passager vs voiture solo',
    `statut` tinyint(1) DEFAULT NULL,
    `nb_place` int NOT NULL,
    `prix_personne` float NOT NULL,
    `user_id` int NOT NULL,
    `voiture_id` int NOT NULL,
    `duree` int DEFAULT NULL,
    PRIMARY KEY (`covoiturage_id`),
    KEY `covoiturage_ibfk_1` (`user_id`),
    KEY `covoiturage_ibfk_2` (`voiture_id`),
    KEY `idx_ville_depart` (`ville_depart_id`),
    KEY `idx_ville_arrivee` (`ville_arrivee_id`),
    CONSTRAINT `covoiturage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `covoiturage_ibfk_2` FOREIGN KEY (`voiture_id`) REFERENCES `voiture` (`voiture_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `covoiturage_ibfk_3` FOREIGN KEY (`ville_depart_id`) REFERENCES `ville` (`ville_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `covoiturage_ibfk_4` FOREIGN KEY (`ville_arrivee_id`) REFERENCES `ville` (`ville_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
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
-- TABLE ETAPE (dépend de covoiturage, ville)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `etape`;

CREATE TABLE IF NOT EXISTS `etape` (
    `etape_id` INT NOT NULL AUTO_INCREMENT,
    `covoiturage_id` INT NOT NULL,
    `ville_id` INT NOT NULL,
    `ordre` INT NOT NULL COMMENT '1 = départ, n = arrivée. Unique par covoiturage.',
    `heure_prevue` TIME DEFAULT NULL,
    `date_prevue` DATE DEFAULT NULL,
    `prix_segment` FLOAT DEFAULT NULL COMMENT 'Prix entre cette étape et la suivante (optionnel)',
    PRIMARY KEY (`etape_id`),
    UNIQUE KEY `uniq_covoiturage_ordre` (`covoiturage_id`, `ordre`),
    KEY `idx_ville` (`ville_id`),
    CONSTRAINT `etape_ibfk_1` FOREIGN KEY (`covoiturage_id`) REFERENCES `covoiturage` (`covoiturage_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT `etape_ibfk_2` FOREIGN KEY (`ville_id`) REFERENCES `ville` (`ville_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
-- TABLE RESERVATIONS (dépend de user, covoiturage, etape)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `reservations`;

CREATE TABLE IF NOT EXISTS `reservations` (
    `reservation_id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `covoiturage_id` int NOT NULL,
    `etape_montee_id` INT DEFAULT NULL,
    `etape_descente_id` INT DEFAULT NULL,
    `date_reservation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `nb_places_reservees` int NOT NULL DEFAULT 1,
    `prix_total` float NOT NULL,
    `statut` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'En attente',
    PRIMARY KEY (`reservation_id`),
    KEY `reservations_ibfk_1` (`user_id`),
    KEY `reservations_ibfk_2` (`covoiturage_id`),
    KEY `idx_etape_montee` (`etape_montee_id`),
    KEY `idx_etape_descente` (`etape_descente_id`),
    CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`covoiturage_id`) REFERENCES `covoiturage` (`covoiturage_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`etape_montee_id`) REFERENCES `etape` (`etape_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `reservations_ibfk_4` FOREIGN KEY (`etape_descente_id`) REFERENCES `etape` (`etape_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
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