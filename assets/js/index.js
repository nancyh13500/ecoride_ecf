// Navbar scroll effect
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar');
    
    // Hide navbar initially
    navbar.style.transform = 'translateY(-100%)';
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 0) {
            navbar.style.transform = 'translateY(0)';
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.style.transform = 'translateY(-100%)';
            navbar.classList.remove('navbar-scrolled');
        }
    });

    // Mobile menu toggle
    const menuToggle = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    if (menuToggle && navbarCollapse) {
        menuToggle.addEventListener('click', function() {
            navbarCollapse.classList.toggle('show');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
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