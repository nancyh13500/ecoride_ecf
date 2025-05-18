<?php require_once __DIR__ . "/../templates/header.php";
?>


<section class="hero px-4 py-5">
    <div class="background-contact"></div>
    <div class="contact-title text-black text-center mt-5">
        <div class="container">
            <h1 class="contact-title mt-3 mb-3 fw-bold">Contact</h1>
        </div>
    </div>

    <div class="container  contact mt-3 mb-3">
        <form action="" method="POST">
            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <input type="text" class="form-control bg-light border-dark rounded" id="exampleFormControlInput1" placeholder="Ex. Durand" required>
                </div>
            </div>
            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <input type="email" class="form-control bg-light border-dark rounded" id="exampleFormControlInput1" placeholder="name@example.com" required>
                </div>
            </div>
            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <input type="text" class="form-control bg-light border-dark rounded" id="exampleFormControlInput1" placeholder="Sujet" required>
                </div>
            </div>
            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <textarea class="form-control bg-light border-dark rounded" id="exampleFormControlTextarea1" rows="5" placeholder="Message" required></textarea>
                </div>
                <p class="text-center mt-3">Une réponse vous sera envoyée par mail sous 48 heures</p>
                <div class="text-center mt-2 mb-2">
                    <button type="submit" class="btn btn-secondary">Envoyer le message</button>
                </div>
            </div>
        </form>
    </div>
</section>















<?php require_once __DIR__ . "/../templates/footer.php";
?>