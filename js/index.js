/**scroll navbar */
const navbar = document.getElementById('.navbar');
let lastscroll = 0;

window.addEventListener("scroll", () => {
    if (window.scrollY < lastscroll) {
        navbar.style.top = 0;
    } else {
        navbar.style.top = "-100px";
    }
    lastscroll = window.scrollY;
});


/**image slide */

let playOnce = true;

window.addEventListener("scroll", () => {

    let scrollValue = (window.scrollY + window.innerHeight) / document.offsetHeight;
    // console.log(scrollValue);
    //image
    if (scrollValue > 0.45) {
        img_covoiturage.style.opacity = 1;
        img_covoiturage.style.transform = "none";
    }
})