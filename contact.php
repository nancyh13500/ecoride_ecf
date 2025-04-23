<?php require_once __DIR__ . "/templates/header.php";
?>
<section class="contact">
    <div class="contact-title">
        <h1 class="text-center fw-bold mt-5 mb-4">Contactez-nous</h1>
    </div>
    <div class="container">
        <div class="col">
            <div class="col-md-4 d-flex justify-content-center mt-4 mb-4">
                <label for="exampleFormControlInput1" class="form-label"></label>
                <input type="name" class="name form-control border-dark bg-light" id="exampleFormControlInput1" placeholder="Nom" required>
            </div>
            <div class="col-md-4 d-flex justify-content-center mt-4 mb-4">
                <label for="exampleFormControlInput1" class="form-label"></label>
                <input type="email" class="email form-control border-dark bg-light" id="exampleFormControlInput1" placeholder="name@example.com" required>
            </div>
            <div class="col-md-4 d-flex justify-content-center mt-4 mb-4">
                <label for="exampleFormControlInput1" class="form-label"></label>
                <input type="text" class="sujet form-control border-dark bg-light" id="exampleFormControlInput1" placeholder="Sujet" required>
            </div>
            <div class="col-md-4 d-flex justify-content-center mt-4 mb-4">
                <label for="exampleFormControlTextarea1" class="form-label"></label>
                <textarea class="message form-control border-dark bg-light" id="exampleFormControlTextarea1" rows="8" placeholder="Message" required></textarea>
            </div>
        </div>
        <div class="mb-4 d-flex justify-content-center">
            <button class="btn-contact btn btn-primary">Envoyer le message</button>
        </div>
    </div>
</section>















<?php require_once __DIR__ . "/templates/footer.php";
?>