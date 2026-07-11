<?php
require_once __DIR__ . "/../lib/session.php";

ecoride_session()->logout();

header('Location: /login.php');
exit();
