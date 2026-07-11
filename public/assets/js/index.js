// Effets visuels légers — navigation laissée à Bootstrap pour l'accessibilité clavier
document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.querySelector('.navbar');

    if (navbar) {
        navbar.style.transition = 'box-shadow 0.3s ease-in-out';

        window.addEventListener('scroll', function () {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        }, { passive: true });
    }

    const imgCovoiturage = document.getElementById('img_covoiturage');
    if (imgCovoiturage && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        window.addEventListener('scroll', function () {
            const scrollValue =
                (window.scrollY + window.innerHeight) / document.body.offsetHeight;

            if (scrollValue > 0.65) {
                imgCovoiturage.style.opacity = '1';
                imgCovoiturage.style.transform = 'none';
            }
        }, { passive: true });
    } else if (imgCovoiturage) {
        imgCovoiturage.style.opacity = '1';
        imgCovoiturage.style.transform = 'none';
    }
});
