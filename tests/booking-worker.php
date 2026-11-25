<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/vehicles.php';
require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/bookings.php';
date_default_timezone_set('Africa/Casablanca');
$deadline = microtime(true) + 10;

while (!is_file($argv[5]) && microtime(true) < $deadline) {
    usleep(10000);
}

try {
    $booking = createBooking(database(), (int) $argv[1], (int) $argv[2], $argv[3], $argv[4], bin2hex(random_bytes(32)));
    echo (int) $booking['id'];
} catch (DomainException $exception) {
    exit(2);
} catch (Throwable $error) {
    fwrite(STDERR, get_class($error) . ': ' . $error->getMessage());
    exit(3);
}
