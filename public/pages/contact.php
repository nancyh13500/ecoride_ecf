<?php

require_once __DIR__ . '/../../bootstrap/app.php';

use Ecoride\Ecf\Controller\ContactController;

$controller = new ContactController();
$viewData = $controller->defaultViewData();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $viewData = $controller->handlePost($_POST);
}

extract($viewData, EXTR_SKIP);

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/pages/contact.php';
require_once __DIR__ . '/../../templates/footer.php';
