-- Étape 1 : Désactiver temporairement le mode strict pour permettre la modification
SET sql_mode = '';

-- Étape 2 : Nettoyer les données invalides (0000-00-00) dans la table
-- Mettre NULL pour les dates/heures invalides ou utiliser la date de départ
UPDATE `covoiturage` 
SET `date_arrivee` = `date_depart`
WHERE `date_arrivee` = '0000-00-00' OR `date_arrivee` IS NULL OR CAST(`date_arrivee` AS CHAR) = '0000-00-00';

UPDATE `covoiturage` 
SET `heure_arrivee` = `heure_depart`
WHERE (`heure_arrivee` = '00:00:00' OR `heure_arrivee` IS NULL) 
AND (`date_arrivee` = `date_depart` OR `date_arrivee` IS NOT NULL);

UPDATE `covoiturage` 
SET `heure_depart` = '00:00:00' 
WHERE CAST(`heure_depart` AS CHAR) LIKE '0000%' OR `heure_depart` = '';

-- Étape 3 : Modifier la structure de la table pour autoriser NULL
ALTER TABLE `covoiturage` 
MODIFY COLUMN `date_arrivee` date NULL,
MODIFY COLUMN `heure_arrivee` time NULL;

-- Étape 4 : Réactiver le mode strict (optionnel)
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

