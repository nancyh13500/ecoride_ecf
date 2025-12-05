document.addEventListener('DOMContentLoaded', function() {
    // Compteur de caractères pour le commentaire
    const commentaireTextarea = document.getElementById('commentaire');
    const charCountSpan = document.getElementById('charCount');

    if (commentaireTextarea && charCountSpan) {
        // Initialiser le compteur avec le contenu existant
        charCountSpan.textContent = commentaireTextarea.value.length;

        // Mettre à jour le compteur à chaque saisie
        commentaireTextarea.addEventListener('input', function() {
            charCountSpan.textContent = this.value.length;
        });
    }

    const ratingStars = document.querySelectorAll('.rating-stars input[type="radio"]');
    const starLabels = document.querySelectorAll('.star-label');

    // Fonction pour mettre à jour l'affichage des étoiles
    function updateStars(selectedValue) {
        starLabels.forEach((label, index) => {
            const starIndex = 5 - index; // Inversé car flex-direction: row-reverse
            const icon = label.querySelector('i');

            if (starIndex <= selectedValue) {
                icon.classList.remove('bi-star');
                icon.classList.add('bi-star-fill');
            } else {
                icon.classList.remove('bi-star-fill');
                icon.classList.add('bi-star');
            }
        });
    }

    // Gérer le clic sur les étoiles
    ratingStars.forEach(radio => {
        radio.addEventListener('change', function() {
            updateStars(parseInt(this.value));
        });
    });

    // Gérer le survol des étoiles
    starLabels.forEach((label, index) => {
        const starIndex = 5 - index;

        label.addEventListener('mouseenter', function() {
            starLabels.forEach((l, i) => {
                const sIndex = 5 - i;
                const icon = l.querySelector('i');
                if (sIndex <= starIndex) {
                    icon.classList.remove('bi-star');
                    icon.classList.add('bi-star-fill');
                }
            });
        });
    });

    // Réinitialiser au survol de la zone de notation
    const ratingContainer = document.querySelector('.rating-stars');
    ratingContainer.addEventListener('mouseleave', function() {
        const checkedRadio = document.querySelector('.rating-stars input[type="radio"]:checked');
        if (checkedRadio) {
            updateStars(parseInt(checkedRadio.value));
        } else {
            updateStars(0);
        }
    });

    // Initialiser l'affichage au chargement
    const checkedRadio = document.querySelector('.rating-stars input[type="radio"]:checked');
    if (checkedRadio) {
        updateStars(parseInt(checkedRadio.value));
    }
});

