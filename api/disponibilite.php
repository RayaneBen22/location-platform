<?php

require_once dirname(__DIR__) . '/app/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

try {
    $id = positiveIdentifier($_GET['id'] ?? null);
    $vehicle = $id ? vehicleDetails(database(), $id) : false;

    if (!$vehicle) {
        http_response_code(404);
        echo json_encode(['available' => false, 'message' => 'Cette voiture est introuvable.']);
        exit;
    }

    $range = dateRange(submittedString($_GET, 'start'), submittedString($_GET, 'end'));
    $available = $vehicle['status'] === 'disponible' && vehicleAvailable(database(), $id, $range['start'], $range['end']);
    $total = decimalAmount(priceInCents((string) $vehicle['daily_price']) * $range['days']);
    echo json_encode(['available' => $available, 'days' => $range['days'], 'total' => $total, 'price_is_indicative' => !empty($vehicle['price_is_indicative']), 'message' => $available ? $range['days'] . ' jour(s) · ' . money($total) . ' au total. Dates disponibles. ' . indicativePriceLabel($vehicle) : 'Cette voiture est déjà réservée ou indisponible sur ces dates.'], JSON_UNESCAPED_UNICODE);
} catch (DomainException $exception) {
    http_response_code(422);
    echo json_encode(['available' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
}
