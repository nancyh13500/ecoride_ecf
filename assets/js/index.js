// Navbar scroll effect
document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.querySelector('.navbar');
    let lastScrollTop = 0;

    // Add transition for smooth effect
    navbar.style.transition = 'transform 0.3s ease-in-out';

    window.addEventListener('scroll', function () {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        if (scrollTop > lastScrollTop) {
            // Scrolling down
            if (scrollTop > 50) {
                navbar.style.transform = 'translateY(0)';
                navbar.classList.add('navbar-scrolled');
            }
        } else {
            // Scrolling up
            navbar.style.transform = 'translateY(0)';
            navbar.classList.add('navbar-scrolled');
        }

        lastScrollTop = scrollTop;
    });

    // Mobile menu toggle
    const menuToggle = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    if (menuToggle && navbarCollapse) {
        menuToggle.addEventListener('click', function () {
            navbarCollapse.classList.toggle('show');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function (event) {
            if (!navbarCollapse.contains(event.target) && !menuToggle.contains(event.target)) {
                navbarCollapse.classList.remove('show');
            }
        });
    }

    // Active link highlighting
    const currentLocation = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentLocation) {
            link.classList.add('active');
        }
    });
});

/**scroll navbar */

let lastscroll = 0;

window.addEventListener("scroll", () => {
    if (window.scrollY < lastscroll) {
        navbar.style.top = 0;

    } else {
        navbar.style.top = "-150px";
    }
    lastscroll = window.scrollY;
});

/**image slide */

let playOnce = true;

window.addEventListener("scroll", () => {

    let scrollValue =
        (window.scrollY + window.innerHeight) / document.body.offsetHeight;

    if (scrollValue > 0.65) {
        img_covoiturage.style.opacity = 1;
        img_covoiturage.style.transform = "none";
    }
});


