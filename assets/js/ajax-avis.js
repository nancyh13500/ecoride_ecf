// Gestion des requêtes AJAX pour les avis (validation, refus, suppression)
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les formulaires d'action sur les avis
    const forms = document.querySelectorAll('.js-avis-action');

    forms.forEach(form => {
        form.addEventListener('submit', async function(event) {
            // Empêcher le rechargement de page
            event.preventDefault();

            // Récupérer les données du formulaire
            const formData = new FormData(form);
            const avisId = formData.get('avis_id');
            const action = formData.get('action');

            // Pour la suppression, demander confirmation
            if (action === 'supprimer') {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')) {
                    return;
                }
            }

            // Désactiver le bouton pendant la requête
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>En cours...';

            try {
                // Envoyer la requête avec fetch()
                const response = await fetch('/pages/ajax_avis.php', {
                    method: 'POST',
                    body: formData
                });

                // Vérifier si la réponse est OK
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }

                // Parser la réponse JSON
                let data;
                try {
                    data = await response.json();
                } catch (jsonError) {
                    // Si la réponse n'est pas du JSON valide, lire le texte
                    const text = await response.text();
                    throw new Error('Réponse invalide du serveur: ' + text.substring(0, 100));
                }

                // Vérifier si la requête a réussi
                if (data.success) {
                    // Afficher un message de succès
                    showMessage(data.message, 'success');

                    // Retirer l'élément de la page (carte ou ligne de tableau)
                    const cardElement = form.closest('.col-md-6, .col-lg-4, tr');
                    if (cardElement) {
                        // Animation de disparition
                        cardElement.style.transition = 'opacity 0.3s';
                        cardElement.style.opacity = '0';
                        setTimeout(() => {
                            cardElement.remove();

                            // Mettre à jour les compteurs dans les onglets
                            updateTabCounters();
                        }, 300);
                    }
                } else {
                    // Afficher un message d'erreur
                    showMessage(data.message || 'Une erreur est survenue', 'danger');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            } catch (error) {
                // Gérer les erreurs réseau
                console.error('Erreur:', error);
                showMessage('Erreur: ' + (error.message || 'Erreur réseau. Veuillez réessayer.'), 'danger');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    });

    // Fonction pour afficher des messages (toast/alerte)
    function showMessage(message, type) {
        // Créer une alerte Bootstrap
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex = '9999';
        alertDiv.style.minWidth = '300px';
        alertDiv.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        // Retirer automatiquement après 3 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }

    // Fonction pour mettre à jour les compteurs dans les onglets
    function updateTabCounters() {
        // Compter les avis en attente restants
        const avisEnAttente = document.querySelectorAll('#avis .col-md-6, #avis .col-lg-4').length;
        const avisTab = document.querySelector('#avis-tab');
        if (avisTab) {
            avisTab.innerHTML = `<i class="bi bi-star me-2"></i>Avis à valider (${avisEnAttente})`;
        }

        // Si plus d'avis en attente, afficher le message "Aucun avis"
        if (avisEnAttente === 0) {
            const avisContainer = document.querySelector('#avis .card-body');
            if (avisContainer && !avisContainer.querySelector('.text-center')) {
                avisContainer.innerHTML = `
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle text-success icon-large-avis"></i>
                        <h5 class="mt-3 text-muted">Aucun avis en attente</h5>
                        <p class="text-muted">Tous les avis ont été traités !</p>
                    </div>
                `;
            }
        }

        // Mettre à jour le compteur de tous les avis dans le tableau
        const tousAvis = document.querySelectorAll('#admin_avis_mongodb tbody tr').length;
        const tousAvisTab = document.querySelector('#admin-avis-tab');
        if (tousAvisTab) {
            tousAvisTab.innerHTML = `<i class="bi bi-database me-2"></i>Tous les avis (${tousAvis})`;
        }
    }
});

