// Gestion du temps de trajet (durée réelle chauffeur + durée estimée OSRM)
class TempsTrajet {
    constructor() {
        this.trajetsEnCours = new Map();
        this.init();
    }

    init() {
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[name="start_trajet_id"], [name="stop_trajet_id"]');
            if (!target) {
                return;
            }

            if (target.name === 'start_trajet_id') {
                this.demarrerTrajet(target.value);
                return;
            }

            if (target.name === 'stop_trajet_id') {
                e.preventDefault();
                this.arreterTrajet(target.value);
            }
        });
    }

    demarrerTrajet(trajetId) {
        const heureDebut = new Date();
        this.trajetsEnCours.set(String(trajetId), heureDebut);
        sessionStorage.setItem(`trajet_${trajetId}_debut`, String(heureDebut.getTime()));
    }

    arreterTrajet(trajetId) {
        const heureDebut = this.getHeureDebut(String(trajetId));

        if (!heureDebut) {
            this.soumettreArret(trajetId, null);
            return;
        }

        const dureeMs = Date.now() - heureDebut.getTime();
        const dureeMinutes = Math.max(1, Math.round(dureeMs / (1000 * 60)));

        this.trajetsEnCours.delete(String(trajetId));
        sessionStorage.removeItem(`trajet_${trajetId}_debut`);

        this.soumettreArret(trajetId, dureeMinutes);
    }

    getHeureDebut(trajetId) {
        if (this.trajetsEnCours.has(trajetId)) {
            return this.trajetsEnCours.get(trajetId);
        }

        const element = document.getElementById(`temps-${trajetId}`);
        const debutServeur = element?.dataset?.debut;
        if (debutServeur) {
            const dateServeur = new Date(debutServeur);
            if (!Number.isNaN(dateServeur.getTime())) {
                return dateServeur;
            }
        }

        const stored = sessionStorage.getItem(`trajet_${trajetId}_debut`);
        if (!stored) {
            return null;
        }

        const dateSession = new Date(parseInt(stored, 10));
        return Number.isNaN(dateSession.getTime()) ? null : dateSession;
    }

    soumettreArret(trajetId, dureeMinutes) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'mes_trajets.php';
        form.style.display = 'none';

        const inputStop = document.createElement('input');
        inputStop.type = 'hidden';
        inputStop.name = 'stop_trajet_id';
        inputStop.value = trajetId;
        form.appendChild(inputStop);

        if (dureeMinutes !== null) {
            const inputDuree = document.createElement('input');
            inputDuree.type = 'hidden';
            inputDuree.name = 'duree_minutes';
            inputDuree.value = dureeMinutes;
            form.appendChild(inputDuree);
        }

        document.body.appendChild(form);
        form.submit();
    }

    getDureeEnCoursMs(trajetId) {
        const heureDebut = this.getHeureDebut(String(trajetId));
        if (!heureDebut) {
            return null;
        }

        return Math.max(0, Date.now() - heureDebut.getTime());
    }

    afficherTempsReel(element) {
        const trajetId = element.dataset.trajetId || element.id.replace('temps-', '');
        if (!trajetId) {
            return;
        }

        const updateTime = () => {
            const dureeMs = this.getDureeEnCoursMs(trajetId);
            if (dureeMs !== null) {
                element.textContent = `Temps écoulé : ${formaterDureeEcoulee(dureeMs)}`;
            }
        };

        setInterval(updateTime, 1000);
        updateTime();
    }
}

function formaterDureeEcoulee(dureeMs) {
    const totalSeconds = Math.floor(dureeMs / 1000);
    const heures = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const secondes = totalSeconds % 60;

    if (heures > 0) {
        return `${heures}h ${String(minutes).padStart(2, '0')}min ${String(secondes).padStart(2, '0')}s`;
    }
    if (minutes > 0) {
        return `${minutes}min ${String(secondes).padStart(2, '0')}s`;
    }
    return `${secondes}s`;
}

document.addEventListener('DOMContentLoaded', () => {
    window.tempsTrajet = new TempsTrajet();

    document.querySelectorAll('.temps-trajet-live, [id^="temps-"]').forEach((element) => {
        if (window.tempsTrajet) {
            window.tempsTrajet.afficherTempsReel(element);
        }
    });
});
