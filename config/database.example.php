<?php

$host = '127.0.0.1';
$dbname = 'location_platform';
$username = 'VOTRE_UTILISATEUR_LOCAL';
$password = 'VOTRE_MOT_DE_PASSE_LOCAL';

$pdo = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
