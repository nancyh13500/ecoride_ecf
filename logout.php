<?php
require_once __DIR__ . "/lib/session.php";

//prévient les attaques de fixation de session
session_regenerate_id(true);

//supprime les données du serveur
session_destroy();


//enleve la session de la memoire des variables $_session
unset($_SESSION);

header('location: login.php');
