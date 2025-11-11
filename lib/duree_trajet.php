<?php

/**
 * CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ
 * Fonctions pour gérer la durée des trajets
 * Peut être étendu pour utiliser MongoDB ou une autre solution de stockage
 */

/**
 * Démarre l'enregistrement d'un trajet
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $trajet_id ID du trajet
 * @return bool|array Retourne true en cas de succès ou false en cas d'erreur
 */
/*
function demarrerTrajetMongo($user_id, $trajet_id)
{
    try {
        // TODO: Implémenter l'enregistrement dans MongoDB si nécessaire
        // Pour l'instant, on retourne simplement true pour ne pas bloquer l'exécution
        return true;
    } catch (Exception $e) {
        // Ne pas bloquer l'exécution en cas d'erreur
        error_log("Erreur lors du démarrage du trajet MongoDB: " . $e->getMessage());
        return false;
    }
}
*/

/**
 * Arrête l'enregistrement d'un trajet et calcule la durée
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $trajet_id ID du trajet
 * @return array|false Retourne un tableau avec 'duration_minutes' ou false en cas d'erreur
 */
/*
function arreterTrajetMongo($user_id, $trajet_id)
{
    try {
        // TODO: Implémenter le calcul de durée depuis MongoDB si nécessaire
        // Pour l'instant, on retourne false pour indiquer qu'aucune durée n'est disponible
        // La durée peut être calculée manuellement ou via le formulaire de mise à jour
        return false;
    } catch (Exception $e) {
        // Ne pas bloquer l'exécution en cas d'erreur
        error_log("Erreur lors de l'arrêt du trajet MongoDB: " . $e->getMessage());
        return false;
    }
}
*/
