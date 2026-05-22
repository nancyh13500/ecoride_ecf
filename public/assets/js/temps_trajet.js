// CALCUL DU TEMPS DE COVOITURAGE - DÉSACTIVÉ
// Gestion du temps de trajet
/*
class TempsTrajet {
    constructor() {
        this.trajetsEnCours = new Map(); // Stocke les trajets en cours avec leur heure de début
        this.init();
    }

    init() {
        // clics sur les boutons de démarrage et d'arrêt
        document.addEventListener('click', (e) => {
            if (e.target.name === 'start_trajet_id') {
                this.demarrerTrajet(e.target.value);
            } else if (e.target.name === 'stop_trajet_id') {
                this.arreterTrajet(e.target.value);
            }
        });
    }

    demarrerTrajet(trajetId) {
        const heureDebut = new Date();
        this.trajetsEnCours.set(trajetId, heureDebut);
        
        // Sauvegarder en sessionStorage pour persister entre les pages
        sessionStorage.setItem(`trajet_${trajetId}_debut`, heureDebut.getTime());
        
        console.log(`Trajet ${trajetId} démarré à ${heureDebut.toLocaleTimeString()}`);
        
        // Optionnel : afficher une notification
        this.afficherNotification(`Trajet ${trajetId} démarré !`, 'success');
    }

    arreterTrajet(trajetId) {
        const heureDebut = this.trajetsEnCours.get(trajetId) || 
                          new Date(parseInt(sessionStorage.getItem(`trajet_${trajetId}_debut`)));
        
        if (!heureDebut) {
            console.error(`Aucune heure de début trouvée pour le trajet ${trajetId}`);
            return;
        }

        const heureFin = new Date();
        const dureeMs = heureFin.getTime() - heureDebut.getTime();
        const dureeMinutes = Math.round(dureeMs / (1000 * 60));
        
        // Calculer la durée en heures et minutes
        const heures = Math.floor(dureeMinutes / 60);
        const minutes = dureeMinutes % 60;
        
        const dureeFormatee = heures > 0 ? `${heures}h ${minutes}min` : `${minutes}min`;
        
        console.log(`Trajet ${trajetId} terminé. Durée : ${dureeFormatee}`);
        
        // Nettoyer le stockage
        this.trajetsEnCours.delete(trajetId);
        sessionStorage.removeItem(`trajet_${trajetId}_debut`);
        
        // Envoyer la durée au serveur
        this.envoyerDureeAuServeur(trajetId, dureeMinutes);
        
        // Afficher une notification avec la durée
        this.afficherNotification(`Trajet ${trajetId} terminé ! Durée : ${dureeFormatee}`, 'info');
    }

    envoyerDureeAuServeur(trajetId, dureeMinutes) {
        // Créer un formulaire caché pour envoyer la durée
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'mes_trajets.php';
        form.style.display = 'none';
        
        const inputTrajetId = document.createElement('input');
        inputTrajetId.type = 'hidden';
        inputTrajetId.name = 'update_duree_trajet_id';
        inputTrajetId.value = trajetId;
        
        const inputDuree = document.createElement('input');
        inputDuree.type = 'hidden';
        inputDuree.name = 'duree_minutes';
        inputDuree.value = dureeMinutes;
        
        form.appendChild(inputTrajetId);
        form.appendChild(inputDuree);
        document.body.appendChild(form);
        
        // Soumettre le formulaire
        form.submit();
    }

    afficherNotification(message, type = 'info') {
        // Créer une notification temporaire
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    // Méthode pour récupérer la durée d'un trajet en cours
    getDureeEnCours(trajetId) {
        const heureDebut = this.trajetsEnCours.get(trajetId) || 
                          new Date(parseInt(sessionStorage.getItem(`trajet_${trajetId}_debut`)));
        
        if (!heureDebut) return null;
        
        const maintenant = new Date();
        const dureeMs = maintenant.getTime() - heureDebut.getTime();
        return Math.round(dureeMs / (1000 * 60)); // Retourne en minutes
    }

    // Méthode pour afficher le temps écoulé en temps réel
    afficherTempsReel(trajetId, elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const updateTime = () => {
            const dureeMinutes = this.getDureeEnCours(trajetId);
            if (dureeMinutes !== null) {
                const heures = Math.floor(dureeMinutes / 60);
                const minutes = dureeMinutes % 60;
                const tempsFormate = heures > 0 ? `${heures}h ${minutes}min` : `${minutes}min`;
                element.textContent = `Temps écoulé : ${tempsFormate}`;
            }
        };
        
        // Mettre à jour toutes les secondes
        setInterval(updateTime, 1000);
        updateTime(); // Première mise à jour immédiate
    }
}
*/

// Initialiser quand le DOM est chargé
/*
document.addEventListener('DOMContentLoaded', () => {
    window.tempsTrajet = new TempsTrajet();
});
*/

// Fonction utilitaire pour formater la durée
/*
function formaterDuree(minutes) {
    if (minutes < 60) {
        return `${minutes}min`;
    } else {
        const heures = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return mins > 0 ? `${heures}h ${mins}min` : `${heures}h`;
    }
}
*/
