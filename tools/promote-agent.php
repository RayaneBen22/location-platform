<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/database.php';
$email = $argv[1] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage : php tools/promote-agent.php adresse-email-du-compte-existant\n");
    exit(1);
}

$query = database()->prepare("UPDATE users SET role = 'agent' WHERE email = :email AND is_active = 1");
$query->execute(['email' => mb_strtolower($email)]);

if ($query->rowCount() !== 1) {
    fwrite(STDERR, "Compte introuvable, inactif ou déjà agent.\n");
    exit(1);
}

echo "Rôle agent attribué. Reconnectez le compte concerné.\n";
