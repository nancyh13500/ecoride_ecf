document.addEventListener('DOMContentLoaded', function() {
    const commentaireTextarea = document.getElementById('commentaire');
    const charCountSpan = document.getElementById('charCount');

    if (commentaireTextarea && charCountSpan) {
        charCountSpan.textContent = commentaireTextarea.value.length;

        commentaireTextarea.addEventListener('input', function() {
            charCountSpan.textContent = this.value.length;
        });
    }

    const ratingStars = document.querySelectorAll('.rating-stars input[type="radio"]');
    const starLabels = document.querySelectorAll('.star-label');

    function updateStars(selectedValue) {
        starLabels.forEach((label, index) => {
            const starIndex = 5 - index;
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

    ratingStars.forEach(radio => {
        radio.addEventListener('change', function() {
            updateStars(parseInt(this.value, 10));
        });
    });

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

    const ratingContainer = document.querySelector('.rating-stars');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', function() {
            const checkedRadio = document.querySelector('.rating-stars input[type="radio"]:checked');
            if (checkedRadio) {
                updateStars(parseInt(checkedRadio.value, 10));
            } else {
                updateStars(0);
            }
        });
    }

    const checkedRadio = document.querySelector('.rating-stars input[type="radio"]:checked');
    if (checkedRadio) {
        updateStars(parseInt(checkedRadio.value, 10));
    }
});
