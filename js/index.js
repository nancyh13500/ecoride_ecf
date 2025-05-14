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
    // console.log(scrollValue);
    //image
    if (scrollValue > 0.65) {
        img_covoiturage.style.opacity = 1;
        img_covoiturage.style.transform = "none";
    }
});


