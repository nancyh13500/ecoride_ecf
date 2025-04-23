<?php require_once __DIR__ . "/templates/header.php";
?>


<section class="hero">
    <div class="background-contact"></div>
    <div class="contact-title text-black text-center">
        <div class="container">
            <h1 class="contact-title mt-3 mb-3 fw-bold">Contact</h1>
        </div>
    </div>

    <div class="container  contact mt-3 mb-3">
        <form action="" method="POST">
            <div class="mb-4 row">
                <label for="exampleFormControlInput1" class="col-sm-3 col-form-label"></label>
                <div class="col-sm-6">
                    <input type="email" class="form-control bg-light border-dark rounded" id="exampleFormControlInput1" placeholder="Ex. Durand">
                </div>
            </div>
            <div class="mb-4 row">
                <label for="exampleFormControlInput1" class="col-sm-3 col-form-label"></label>
                <div class="col-sm-6">
                    <input type="email" class="form-control bg-light border-dark rounded" id="exampleFormControlInput1" placeholder="name@example.com">
                </div>
            </div>
            <div class="mb-4 row">
                <label for="exampleFormControlInput1" class="col-sm-3 col-form-label"></label>
                <div class="col-sm-6">
                    <input type="text" class="form-control bg-light border-dark rounded" id="exampleFormControlInput1" placeholder="Sujet">
                </div>
            </div>
            <div class="mb-4 row">
                <label for="exampleFormControlTextarea1" class="col-sm-3 col-form-label"></label>
                <div class="col-sm-6">
                    <textarea class="form-control bg-light border-dark rounded" id="exampleFormControlTextarea1" rows="5" placeholder="Message"></textarea>
                </div>
                <p class="text-center mt-3">Une réponse vous sera envoyée par mail sous 48 heures</p>
                <div class="text-center mt-2 mb-2">
                    <a href="*" class="btn btn-secondary">Envoyer le message</a>
                </div>
            </div>
        </form>
    </div>
</section>















<?php require_once __DIR__ . "/templates/footer.php";
?>