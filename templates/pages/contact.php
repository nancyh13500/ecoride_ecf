<section class="hero px-4 py-5">
    <div class="background-contact"></div>
    <div class="contact-title text-black text-center mt-5">
        <div class="container">
            <h1 class="contact-title mt-3 mb-3 fw-bold">Contact</h1>
        </div>
    </div>

    <div class="container contact mt-3 mb-3">

        <?php if (!empty($messageSuccess)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✅ Succès !</strong> <?= htmlspecialchars($messageSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($messageError)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>❌ Erreur !</strong> <?= $messageError ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <?php csrfField(); ?>

            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <input type="text"
                        name="name"
                        class="form-control bg-light border-dark rounded"
                        placeholder="Ex. Durand"
                        value="<?= htmlspecialchars($name ?? '') ?>"
                        required>
                </div>
            </div>

            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <input type="email"
                        name="email"
                        class="form-control bg-light border-dark rounded"
                        placeholder="name@example.com"
                        value="<?= htmlspecialchars($email ?? '') ?>"
                        required>
                </div>
            </div>

            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <input type="text"
                        name="subject"
                        class="form-control bg-light border-dark rounded"
                        placeholder="Sujet"
                        value="<?= htmlspecialchars($subject ?? '') ?>"
                        required>
                </div>
            </div>

            <div class="mb-4 row d-flex justify-content-center">
                <div class="col-md-4">
                    <textarea name="message"
                        class="form-control bg-light border-dark rounded"
                        rows="5"
                        placeholder="Message"
                        required><?= htmlspecialchars($message ?? '') ?></textarea>
                </div>
                <p class="text-center mt-3">Une réponse vous sera envoyée par mail sous 48 heures</p>
                <div class="text-center mt-2 mb-2">
                    <button type="submit" class="btn btn-secondary">Envoyer le message</button>
                </div>
            </div>
        </form>
    </div>
</section>
