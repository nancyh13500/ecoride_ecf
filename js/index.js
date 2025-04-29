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

