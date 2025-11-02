document.addEventListener('DOMContentLoaded', function () {
    var carousel = document.querySelector('#avisCarousel .carousel-inner');
    if (!carousel) return;
    var items = carousel.querySelectorAll('.carousel-item');
    // Nettoyage si reload
    items.forEach(function (item) {
        var clones = item.querySelectorAll('.cloned-card');
        clones.forEach(function (clone) { clone.remove(); });
    });
    if (window.innerWidth >= 768) {
        items.forEach(function (el, idx) {
            let next = el.nextElementSibling;
            for (let i = 1; i < 3; i++) {
                if (!next) next = items[0];
                var cloneChild = next.querySelector('.col-12').cloneNode(true);
                cloneChild.classList.remove('col-12');
                cloneChild.classList.add('d-none', 'd-md-flex', 'cloned-card');
                el.querySelector('.row').appendChild(cloneChild);
                next = next.nextElementSibling;
            }
        });
    }
}); 