-- Colonnes pour le chronomètre trajet et la durée estimée OSRM
ALTER TABLE `covoiturage`
    ADD COLUMN IF NOT EXISTS `debut_trajet_at` DATETIME DEFAULT NULL COMMENT 'Heure réelle de démarrage du trajet',
    ADD COLUMN IF NOT EXISTS `duree_estimee` INT DEFAULT NULL COMMENT 'Durée estimée OSRM en minutes';

UPDATE `covoiturage`
SET `duree_estimee` = `duree`
WHERE `duree_estimee` IS NULL AND `duree` IS NOT NULL AND `duree` > 0;
