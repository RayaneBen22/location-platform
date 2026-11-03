<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mon-compte.php');
    exit;
}

$_SESSION = [];

session_destroy();

header('Location: connexion.php');
exit;