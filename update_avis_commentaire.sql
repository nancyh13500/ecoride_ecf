-- Script pour modifier la colonne commentaire de la table avis
-- Permet d'accepter jusqu'à 1000 caractères au lieu de 50

ALTER TABLE `avis` 
MODIFY COLUMN `commentaire` VARCHAR(1000) COLLATE utf8mb4_general_ci NOT NULL;

