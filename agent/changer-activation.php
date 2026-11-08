<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../connexion.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'agent') {
    http_response_code(403);
    exit('Accès réservé aux agents.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}

$token = $_POST['article_token'] ?? '';

if (
    !isset($_SESSION['article_token'])
    || !is_string($token)
    || !hash_equals($_SESSION['article_token'], $token)
) {
    http_response_code(403);
    exit('Formulaire invalide. Recharge la page et réessaie.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$isActive = $_POST['is_active'] ?? '';

if (!$id || $id < 1 || !in_array($isActive, ['0', '1'], true)) {
    http_response_code(400);
    exit('Article ou état invalide.');
}

require_once __DIR__ . '/../config/database.php';

$query = $pdo->prepare(
    'UPDATE articles SET is_active = :is_active WHERE id = :id'
);
$query->execute([
    'is_active' => (int) $isActive,
    'id' => $id,
]);

header('Location: index.php', true, 303);
exit;