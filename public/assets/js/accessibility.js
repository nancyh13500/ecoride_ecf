/**
 * Accessibilité globale : annonces lecteur d'écran, navigation, focus.
 */
(function () {
    'use strict';

    /**
     * Annonce un message aux technologies d'assistance (aria-live).
     * @param {string} message
     */
    window.announceToScreenReader = function (message) {
        var region = document.getElementById('announcements');
        if (!region) {
            return;
        }
        region.textContent = '';
        window.setTimeout(function () {
            region.textContent = message;
        }, 100);
    };

    /**
     * Affiche une alerte Bootstrap accessible et l'annonce au lecteur d'écran.
     * @param {string} message
     * @param {'success'|'danger'|'warning'|'info'} type
     */
    window.showAccessibleAlert = function (message, type) {
        type = type || 'info';
        var alertDiv = document.createElement('div');
        alertDiv.setAttribute('role', 'alert');
        alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
        alertDiv.style.zIndex = '9999';
        alertDiv.style.minWidth = '300px';

        var iconClass = type === 'success' ? 'check-circle' : 'exclamation-triangle';
        alertDiv.innerHTML =
            '<span aria-hidden="true"><i class="bi bi-' + iconClass + ' me-2"></i></span>' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>';

        document.body.appendChild(alertDiv);
        window.announceToScreenReader(message);

        window.setTimeout(function () {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    };

    document.addEventListener('DOMContentLoaded', function () {
        var currentPath = window.location.pathname;
        var navLinks = document.querySelectorAll('.navbar-nav .nav-link');

        navLinks.forEach(function (link) {
            var href = link.getAttribute('href');
            var isCurrent = href === currentPath ||
                (currentPath !== '/index.php' && href !== '/index.php' && currentPath.endsWith(href.replace(/^\//, '')));

            if (href === currentPath || (href === '/index.php' && (currentPath === '/' || currentPath.endsWith('/index.php')))) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            } else {
                link.classList.remove('active');
                link.removeAttribute('aria-current');
            }
        });

        var skipLink = document.querySelector('.skip-link');
        var mainContent = document.getElementById('main-content');
        if (skipLink && mainContent) {
            skipLink.addEventListener('click', function (event) {
                event.preventDefault();
                mainContent.focus({ preventScroll: false });
            });
        }

        document.querySelectorAll('.carousel').forEach(function (carousel) {
            carousel.addEventListener('slid.bs.carousel', function (event) {
                var total = carousel.querySelectorAll('.carousel-item').length;
                if (total > 1) {
                    window.announceToScreenReader('Avis ' + (event.to + 1) + ' sur ' + total);
                }
            });
        });
    });
})();
