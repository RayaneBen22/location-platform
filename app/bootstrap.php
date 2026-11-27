<?php

date_default_timezone_set('Africa/Casablanca');
ini_set('display_errors', '0');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('LPSESSID');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/vehicles.php';
require_once __DIR__ . '/vehicle-media.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/catalogue.php';
require_once __DIR__ . '/bookings.php';
require_once __DIR__ . '/photos.php';
require_once __DIR__ . '/view.php';

set_exception_handler(function (Throwable $error): void {
    error_log('location-platform: ' . get_class($error) . ' [' . $error->getCode() . ']');
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Service indisponible · RayaneCars</title><link rel="stylesheet" href="' . escape(appUrl('assets/style.css')) . '"></head><body><main class="container section"><p class="eyebrow">RayaneCars</p><h1>Une petite pause technique.</h1><p>Le service est momentanément indisponible. En installation locale, vérifiez la connexion et les migrations de la base dans le README.</p><a class="button" href="' . escape(appUrl('index.php')) . '">Réessayer</a></main></body></html>';
});
