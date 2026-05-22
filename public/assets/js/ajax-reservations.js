// Gestion des requêtes AJAX pour les réservations (annulation, validation)
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les formulaires d'action sur les réservations
    const forms = document.querySelectorAll('.js-reservation-action');
    
    forms.forEach(form => {
        form.addEventListener('submit', async function(event) {
            // Empêcher le rechargement de page
            event.preventDefault();
            
            // Récupérer les données du formulaire
            const formData = new FormData(form);
            const reservationId = formData.get('reservation_id');
            const action = formData.get('action');
            
            // Demander confirmation selon l'action
            let confirmMessage = '';
            if (action === 'cancel_reservation') {
                confirmMessage = 'Êtes-vous sûr de vouloir annuler cette réservation ? Les places seront libérées.';
            } else if (action === 'validate_reservation') {
                confirmMessage = 'Confirmer cette réservation pour le passager sélectionné ?';
            }
            
            if (confirmMessage && !confirm(confirmMessage)) {
                return;
            }
            
            // Désactiver le bouton pendant la requête
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>En cours...';
            
            try {
                // Envoyer la requête avec fetch()
                const response = await fetch('/pages/ajax_reservations.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Parser la réponse JSON
                const data = await response.json();
                
                // Vérifier si la requête a réussi
                if (data.success) {
                    // Afficher un message de succès
                    showMessage(data.message, 'success');
                    
                    // Retirer l'élément de la page (carte)
                    const cardElement = form.closest('.col-md-6');
                    if (cardElement) {
                        // Animation de disparition
                        cardElement.style.transition = 'opacity 0.3s';
                        cardElement.style.opacity = '0';
                        setTimeout(() => {
                            cardElement.remove();
                            
                            // Vérifier s'il reste des réservations
                            checkEmptyState();
                        }, 300);
                    } else {
                        // Si c'est une validation, mettre à jour le statut visuellement
                        if (action === 'validate_reservation') {
                            const card = form.closest('.card');
                            if (card) {
                                // Changer le header de la carte
                                const cardHeader = card.querySelector('.card-header');
                                if (cardHeader) {
                                    cardHeader.className = 'card-header bg-primary text-white';
                                    cardHeader.textContent = 'Réservation confirmée';
                                }
                                
                                // Remplacer le formulaire par un badge
                                const cardBody = card.querySelector('.card-body');
                                if (cardBody) {
                                    const formParent = form.parentElement;
                                    formParent.innerHTML = '<span class="badge bg-primary mt-2">Réservation confirmée</span>';
                                }
                            }
                        }
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
                showMessage('Erreur réseau. Veuillez réessayer.', 'danger');
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
    
    // Fonction pour vérifier l'état vide et afficher un message si nécessaire
    function checkEmptyState() {
        const reservationsContainer = document.querySelector('.card-body .row');
        if (reservationsContainer) {
            const remainingCards = reservationsContainer.querySelectorAll('.col-md-6');
            if (remainingCards.length === 0) {
                const parentCard = reservationsContainer.closest('.card-body');
                if (parentCard && !parentCard.querySelector('.text-muted')) {
                    const emptyMessage = document.createElement('p');
                    emptyMessage.className = 'text-muted';
                    emptyMessage.textContent = 'Aucune réservation trouvée.';
                    parentCard.appendChild(emptyMessage);
                }
            }
        }
    }
});

