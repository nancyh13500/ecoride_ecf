-- Script pour créer la table site_credits
-- À exécuter dans phpMyAdmin ou via MySQL

DROP TABLE IF EXISTS `site_credits`;

CREATE TABLE IF NOT EXISTS `site_credits` (
    `site_credits_id` int NOT NULL AUTO_INCREMENT,
    `total_credits` int NOT NULL DEFAULT 0,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`site_credits_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Insérer un enregistrement initial avec 0 crédit
INSERT INTO `site_credits` (`total_credits`) VALUES (0);



