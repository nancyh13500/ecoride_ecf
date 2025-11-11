-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 29 août 2025 à 16:06
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
    `commentaire` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
    `note` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `statut` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `user_id` int NOT NULL,
    PRIMARY KEY (`avis_id`),
    KEY `avis_ibfk_1` (`user_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `covoiturage`
--

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
    KEY `covoiturage_ibfk_2` (`voiture_id`)
) ENGINE = InnoDB AUTO_INCREMENT = 12 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `covoiturage`
--

INSERT INTO
    `covoiturage` (
        `covoiturage_id`,
        `date_depart`,
        `heure_depart`,
        `lieu_depart`,
        `date_arrivee`,
        `heure_arrivee`,
        `lieu_arrivee`,
        `statut`,
        `nb_place`,
        `prix_personne`,
        `user_id`,
        `voiture_id`,
        `duree`
    )
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
        '0000-00-00',
        'Lyon',
        '0000-00-00',
        '0000-00-00',
        'Chaponost',
        NULL,
        2,
        5,
        5,
        3,
        NULL
    );

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
    (3, 'Electrique');

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
) ENGINE = InnoDB AUTO_INCREMENT = 16 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `marque`
--

INSERT INTO
    `marque` (
        `marque_id`,
        `libelle`,
        `voiture_id`
    )
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
-- Structure de la table `statuts`
--

DROP TABLE IF EXISTS `statuts`;

CREATE TABLE IF NOT EXISTS `statuts` (
    `statuts_id` int NOT NULL AUTO_INCREMENT,
    `libelle` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    PRIMARY KEY (`statuts_id`)
) ENGINE = MyISAM AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `statuts`
--

INSERT INTO
    `statuts` (`statuts_id`, `libelle`)
VALUES (1, 'En cours'),
    (2, 'En attente'),
    (3, 'Terminé');

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
    `date_naissance` date NOT NULL,
    `photo` blob NOT NULL,
    `pseudo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `role_id` int NOT NULL,
    `role_covoiturage` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `credits` int NOT NULL DEFAULT 20,
    PRIMARY KEY (`user_id`),
    KEY `user_ibfk_1` (`role_id`)
) ENGINE = InnoDB AUTO_INCREMENT = 8 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

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
        `role_id`,
        `role_covoiturage`
    )
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
        'Passager'
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
        'Passager'
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
        'Passager'
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
        0xffd8ffe000104a46494600010101007800780000ffe100224578696600004d4d002a00000008000101120003000000010001000000000000ffec00114475636b7900010004000000640000ffed027050686f746f73686f7020332e30003842494d04040000000002541c0200000200041c021900067072696e63651c02190007726f79616c74791c021900086e6f626c656d616e1c02190005796f756e671c0219000872656420686169721c02190006766563746f721c0219000c696c6c757374726174696f6e1c02190008706f7274726169741c02190007636172746f6f6e1c021900056e6f626c651c02190005726567616c1c0219000b61726973746f63726163791c021900086d6564696576616c1c0219000766616e746173791c021900046d616c651c02190003626f791c02190005796f7574681c021900086d6f6e61726368791c02190009666169727974616c651c02190007636f7374756d651c021900066174746972651c02190005677265656e1c0219000562726f776e1c02190004666163651c02190004686561641c0219000973686f756c646572731c0219000764726177696e671c02190007677261706869631c0219000664657369676e1c021900036172741c021900076469676974616c1c02190005696d6167651c02190009616e696d6174696f6e1c021900066176617461721c021900106368617261637465722064657369676e1c0219000973746f7279626f6f6b1c021900056368696c641c0219001166616e74617379206368617261637465721c021900046865726f1c02190010696c6c757374726174697665206172741c021900097768696d736963616c1c02690020596f756e67205072696e636520566563746f7220496c6c757374726174696f6e1c0278002c496c6c757374726174696f6e206f66206120796f756e67207072696e63652077697468207265642068616972ffdb0043000201010201010202020202020202030503030303030604040305070607070706070708090b0908080a0807070a0d0a0a0b0c0c0c0c07090e0f0d0c0e0b0c0c0cffdb004301020202030303060303060c0807080c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0cffc0001108006e006403012200021101031101ffc4001f0000010501010101010100000000000000000102030405060708090a0bffc400b5100002010303020403050504040000017d01020300041105122131410613516107227114328191a1082342b1c11552d1f02433627282090a161718191a25262728292a3435363738393a434445464748494a535455565758595a636465666768696a737475767778797a838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae1e2e3e4e5e6e7e8e9eaf1f2f3f4f5f6f7f8f9faffc4001f0100030101010101010101010000000000000102030405060708090a0bffc400b51100020102040403040705040400010277000102031104052131061241510761711322328108144291a1b1c109233352f0156272d10a162434e125f11718191a262728292a35363738393a434445464748494a535455565758595a636465666768696a737475767778797a82838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae2e3e4e5e6e7e8e9eaf2f3f4f5f6f7f8f9faffda000c03010002110311003f00fdfca28a2800a28ae27f68af8eba47ecd7f0675df1a6b7b9ecb45837ac084092ee6621228533fc4eecaa3d339e80d454a91a707526ec96adf91ad0a152bd58d1a4af293492eede8911fc7dfda4bc19fb327838eb7e32d6adf4bb67252de1c192e6f5c0cec8a25f99dbe83033c9039af857e2cffc1783539afe587c0be05b386d1490977aedcb3c920ecde4c2405fa190d7c5bf1f3e3df897f695f89b7de2bf155e9bad42ec95860563e469f0e72b042a7eea2fe6c724e49ae36bf26cd78d3175a6e3847c90f95dfadf6f97de7f4670ef85b97616929e64bdad47babb515e492b5fd5efd91f61e99ff05bcf8c167a8096e34cf045e419e603633c608f4dc26cff003af41f0cff00c1773538350b39b58f04d95c593b6dbcb6b49da3b8847f7e191894907fb0ea87fda39c8fcfba3bd7934b89b3386d59bf5b3fccfa4afc05905556961a2bd2ebf267efd7c0ef8e1e1bfda2be1a69fe2cf0adf0bed27515382cbb2582453878a44ea9229e083f864104f5d5f8d3ff0004ecfdbe6fff0063bf18be917b6b16a1e0bf12ea10b6a6accc26d3db1e59b88b9da700a97523e60830411cfecac722cd1aba3065619041c823d6bf54e1fcea198e1f9f69c7e25e7dd793ff00807f3cf1970ad5c931becf7a53bb83eebaa7e6af67df7ea2d14515ef1f20145145001451450015f057fc17e3c673787be0478074f691a3d3753f1297ba2064168ad6568c1f6dcd9faa8f4afbd6be40ff0082e47c305f1fff00c13f7c41a9ac61ee7c197b6bae4671f30557f2a503fed9cce4fd2bc6e21a52a996d68c77e57f86aff03eab822b53a59f6165536e74be6f44fe4da67e5d780ff661f1ff00c76f873ab78e7c23a72dde8fe1a768e28589173ac4831e72dbc78f9f62919c919390b96e2b87d2fc5f67a8e51dfecb7284a490cdf23230e08e7b83d8f3ed5fa75f067c4de10fd933f660f87da478875ab0d0dbfb1e19424858cd71348a2595c2282c7e790e4e303d6b1bc47f08ff0067efdb77529ee557c3badeb9b4b4d75a5dd3596a440fe270bb59f1eaead5f883a69e87f44d2e299c6a4e55a9374aeed24b64b4d7a3befbab1f9d72ea76d0a6e7b9b755f5320acbbdf12c3ac5c43a669dbaeef751992d60da085123b055e7ae7711d2beeef107fc1217e1168f6d3dfdcf887c5ba569f6cbe64af3ea36eb142beacef1703ea697e01fc3bfd96be08f8ead6ef44f1169babf88ad6402db50d56f64ba4b593a0646d8b02373c3751d88a9f6696ecee7c4f8695372c3c2736bb47f37d0f882eecf51d0a5d6347d66dded35ad0ae26b0be85c7cc92c64a9cfe23af7ebdebfa11f842f2c9f09bc2ed392676d22d0c99ebbbc94cfeb5f8e7ff0518f866ba2feda5a48b1b6691be27c1622358c7125df9c2d5c9ff794c649ff001afda7d334f8f49d36ded62188ada258907a2a8007e82bf42e02a4d4ebcfa7babf33f29f15f1f1c4e130335bcb9dfa7c29fe37fb89e8a28afd20fc5028a28a0028a28a002bc3ff006cdf13e93e34f84be36f877736ed7137883c3f7568df385dad2c2e13683f7883838cf6af70af13fda97c157124171a8dbc5e747750ed3cedf2a64076107b1e060fa8c77af9fe26af88a38194f0ff003d2feebbdf7f97c8f7787214a58e87b5e9aae9ef269afebb9e49e22f09eb771e08ff008a564f0ee99e238f4a58d2ef52b2374ce22872904681901667e06f7545c92431e0f9a7ec9de02f1978abc2be16f1b7c54f08f8634df1a4d6b24ae61d363d3758d0ee7e68e5b799629248ee20652764c0a93cee8870d5ee5672b5c5942eebb5e48d5997d09032299a86a10e91632dcdc388e085773b63a0afca238ba71c3ba32a69bfe6d6e8fd1dd1acf10ab42a4974e55b3f2323e25782acbe20f83ae74cbfd1b4ff001044ee92c5617f3186d67991818fcd6c1c206c13c3700fcac700f937c14f0efc61b4f8dbe38d0bc4567e08b3f87de1c4b38f4e922f0e476367e2069509b88ec8a4f249b21380b24e844a09cac75ebfa178cec35bbe16d0de5b4f3cb1f9f1ac4e1f319e878ede87bf35b0062a7078da51a524e9a9397577baf41e2f0b5a538af6928a8eb65b33c27e3afc32b3d63f6c4fd9e7536b453a77852f351bf95170891c76d0c52c4b93c2a897cbafbd3e1d78fed3e2478746a366ac89e6344eadced61efdc7239af993c53a47f6a788f4365b54b8921924cb37f02100e3dc1754e3b9515f4afc2af0c4be16f07c315c02b7370c67954f542d8c0fc0015f5bc135ebfb79d28fc1bbf5d12d77f97a9e0f19ce9d4c35173f8a2b957a394a4ff3dfd0e928a28afd30fce028a28a0028a28a002a3bab58afadde19a34962906191d432b0f420d4945269356634da7747cf9f10b455f0f78d350b548d628564df1228c2aa30c803d874fc2b82f88ff16341f857a7a4faddccd12ce088e38ad649de6c75002823f3c57d03f1efc2905cf86e5d6f2527d323cc985cf991e791c7719c8fc6bc66ff004eb3f1169be55cc305e5a4c3700c032b7a11fe35f8c67797ac1e3e54ea2f75fbcadd9bfd36f91face478d8e270b0a8efa68fd52fd773c9fc2dfb57fc34b6befb3d8a6a1a69b9c6f90e8d2c680f60c541231f4c0af5eb5b94bdb68e6898b472a875254ae41e470791f8d60e83f0bb44f0f5efda61b0b73329ca33296f2fdc649e7debb3f07681ff00098f8aedf4b59d629254699cf5648d7196c7e200cf735e73a54aace34b0b1776ed676ddedb7f99eae2ead28a752eec95db7aedf23d1be047856ddbc3cda8dc5b4324ef70c6091e305a30a02e549e9939e9e95e8955f4bd321d1b4e86d2dd04705ba0445f402ac57ed395e06384c2c282dd2d7cdf53f1dcc318f13889567b37a792e81451457a0710514514005145457b7b0e9d6b24f7134504110dcf248c15107a9278149bb6ac0968af0bf8d3ff000515f861f06348bf9db569bc4b75611b48f69a1c42e9ced1c8df911ffe3fdabe2ff8ebff0005edf155c58dabf807c15a469563a8c2c61bed6676bb9d1d4e1d4c51ed456538eaec0820f7ae059ae124f9615149deda3beb6bdb4eb62e9d373f84e93fe0e00f8c9e27f819e2cfd9d7c49a55df88a7f0e683e25bdd575ed0f4cbf6b64d76386184469328e2454323b00d901b048ee317e00ffc148be157c708a2ff00846fc67a6e91aa4fccba26be458cdbbb85de42939ef1b3035f137ed15fb5efc44fdabf53b1b9f1e7885b581a5b3b595ba5b456d6f665c00e511147242a82492781cd78b78c7e1be8fe2bb3b97b8d3ad9ee9a26292aaed62d838271d79f5af9ccf328c2e69353a8e519474525dbcd75fbd1f5193e6b89cba2e34d2945ead3dbef3f65fc7bfb4169bf0e74437de22d73c2be15b129bc5ddf6a3180cbea8ac46ef6c66be0bfdb87fe0a1365fb443786fc19f0335cf1a1f12e97e28b0d6ae3c5ba54d269d142b03b068fcc3867470f86ca842a3186ce2be66f0347a5fed2be2cf0bff6d5a0d46d3c13e05d3746d8ecc024f1c920c1c107a13c57b6683a1d97856ca3b6d32d2db4fb789832c76f188d411c83c753ee6bcbcb785e8e06b46bd79ca7523aad94576d16ff0081e8e63c4757194a5428c230a72ded76dfcd9fd024449894939240cd3abf273e197fc15afe2ffc3ff2e3d4af748f16da46305353b411cc47fd758b69cfbb06afa8be117fc1607c29e2cd4a3d37c53e1bd63c3b7c220f34f6ac2fad23638c8246d90751fc06beda59ae1a0af565cbd75ecb7d76ea8f8aa9879c3567d834572df0e3e37f847e2e5b097c37e21d2f56e32638661e727fbd19c3afe22ba9aeda55a9d58a9d292927d53ba300a28a2b403e70fdb4ff006e69ff00677d622f0de85a52ddf882e6d45d1b9bc5616b6d1b12aa540c195b20f008030324f4af873e27fc70f177c66bd69bc4dafea1aa2962cb6ecfb2da3ff7625c20fcb3ef5fa4ff00b4e7ecc7a2fed31e0a1617e7ec7aad9ee7d3b5144cc96ae7a823f8a36c0dcbdf008c100d7e6bfc60f831e22f813e2f9345f1258b5adc72609d72d6f7a83f8e27e8c3d4751d0815f8f71e52cd2159cea4dba0f6b689794977f37bf4ecb29dce46f7647a7cfbf023589f77a6dda73fa57ce8fe10d5350f84ba4bd9e9f737908b9b8bc9248577881084455207209d85b18e98afa2355d363d634bb8b495a458aea3689cc6db582b0c1c1ec715e4fe25f084bfb3e6b167afe893dcc9a4492ac17d6d23ee214fbf71d707a8207ad787c338b54dca9d37fbc6d38a7b3b292b5d3d1bbe9e68e9c2ceda2dcf1f073403820fa57a3fed1de0fb7d235fb4d62c5556d35a42edb0617cc001dc3fde520fd41af38afd372fc6c317878e221a2974ecf66be4cf56954538a923a6f80ff0e6c3c09a16a173686479358ba69a4df8fdd85242a2fb0c93f8d775589f0f3fe45383fdf7ff00d0ab6c2966000c927007a9aea9cdc9f3499ac62a2ac8b1a669d71a8dc85b6824b864218aa0cf7efe95daf8521f23c5bad06e1e4db2004f2158938fa8ce3f0a8358493c37a358e8f63f2de5f1c3b8e09fef1cfd78f602b4fc3be0db6f0dc9e6c724b24ec9b5d89c2b7e15f9e6759bd3c461a5293e5534d416eda524f99f449b8d92df73ccc45753836f4bedf7eff81b56b712585e47716f2cb6f7111ca4b1394910fa861c8fc2bdebe08ffc145bc77f0aa786df59b8ff0084bb454203c57af8bb8d7bec9fa938ecfb87b8eb5e02cc10649c0afab7f631ff00827f5df8d6eecfc55e3bb392cf448cacd67a4ccbb65d43babccbd562efb4f2ddf03afcff000ed3ccaa629432d9352eafa25de5d2deb7f23cd8defa1f6bfc3cf1c5a7c4bf03695e20b04b98ecf58b64ba852e2331caaac320329e87f4f4c8a2b5e28960895115511005555180a07400515fd0d4d494529bbbebea6e3ab03e24fc2ed03e2f78625d1fc47a65b6a9612f3b255f9a36ecc8c3e6461eaa41adfa28a94e1522e1515d3dd3d5303e11f8ebff0004b9d73c392cd7de04be5d72c796fecebc758af221e89270927e3b4fd6be43fda13e1ceade17f056bba5ebfa4dfe8f78b6aee22bd81a23b93e6046460f4ea09afdaaacff0012f8534bf19e952d86b1a6d86ab65329592deeedd2789c11820ab020f15f0d8ce02c23ad1c460e4e9b4d3b6eb477f55f7fc8951b49491f81de3b7feddfd99fc3f78df33db342a1be9be3fe40579257eedfc47ff825e7c14f891e176d224f0847a2d9b3870ba3dd4b6410862dc2ab6c1c9cfddaf17d77fe0809f092f5c9d3fc4be3ed381fe13796f381ff007d439fd6bd1cb324af8584e9c9a69ce4d5bb3d52d91df42bc629a7dd9f9a3f0f3fe45383fdf7ff00d0aba9f0cdb8baf11d8c646419949fc0e7fa57e88786bfe0861e00f0fd825b1f1978cee2246279fb3293939ea22aeebc19ff000483f845e12d460ba957c4fabcd036e02ef542a84fd2254ae9c4e5b88a94a70859369a5af5b686d3c5c395a47e72bb2dd7c4e1bc8ff45b5f9413dcfa7fdf55ee5f07ff00639f883f1ae689f4dd0a7d3f4d9304ea1a9836d6e17d5411bdff00e02a7eb5fa21f0f7f65ef879f0aaf9aef40f07e85617ac00375f66125c1c74fde3e5ff005aef2be721c030aae9bc5d4d2118c6d1f2df57d1b6fa1e64d735bc958f01fd9c7fe09f3e13f825716faaea8478a3c450e1d2e2e6202dad5bd628b9008fef312de98af7ea28afb8c065d86c152f6386828c7cbafabddbf512560a28a2bb467fffd9,
        'nancy',
        3,
        'Les deux'
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
        'Passager'
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
        'Passager'
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
) ENGINE = InnoDB AUTO_INCREMENT = 5 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `voiture`
--

INSERT INTO
    `voiture` (
        `voiture_id`,
        `modele`,
        `immatriculation`,
        `energie`,
        `couleur`,
        `date_premire_immatriculation`,
        `marque_id`,
        `user_id`,
        `energie_id`
    )
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

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

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

-- --------------------------------------------------------

--
-- Structure de la table `site_credits`
--

DROP TABLE IF EXISTS `site_credits`;

CREATE TABLE IF NOT EXISTS `site_credits` (
    `site_credits_id` int NOT NULL AUTO_INCREMENT,
    `total_credits` int NOT NULL DEFAULT 0,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`site_credits_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `site_credits`
--

INSERT INTO `site_credits` (`total_credits`) VALUES (0);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;