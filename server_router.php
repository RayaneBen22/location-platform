<?php

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$publicPages = ['index.php', 'catalogue.php', 'connexion.php', 'inscription.php', 'deconnexion.php', 'mon-compte.php', 'voiture.php', 'reserver.php', 'reservation.php', 'annuler-reservation.php', 'photo.php', 'conditions.php'];
$agentPages = ['index.php', 'vehicules.php', 'vehicule.php', 'modifier-article.php', 'changer-activation.php', 'photos.php', 'references.php', 'reservations.php', 'utilisateurs.php'];
$relative = ltrim($path, '/');
$allowed = $path === '/' || in_array($relative, $publicPages, true) || in_array(preg_replace('~^agent/~', '', $relative), $agentPages, true) && str_starts_with($relative, 'agent/') || $relative === 'api/disponibilite.php' || preg_match('~\Aassets/[a-z0-9-]+\.(css|js|svg)\z~', $relative) || preg_match('~\Aassets/images/voitures/(audi-a3|bmw-serie-3|mercedes-classe-c|peugeot-208|renault-clio|toyota-corolla)\.webp\z~', $relative);

if (!$allowed || str_contains($path, "\0") || str_contains($path, '..')) {
    http_response_code(404);
    exit('Page introuvable.');
}

return false;
