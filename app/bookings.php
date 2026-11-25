<?php

function dateRange(string $start, string $end): array
{
    $startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
    $endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $end);
    $today = new DateTimeImmutable('today');

    if (!$startDate || !$endDate || $startDate->format('Y-m-d') !== $start || $endDate->format('Y-m-d') !== $end || $startDate < $today || $endDate <= $startDate || $startDate > $today->modify('+365 days')) {
        throw new DomainException('Choisissez des dates valides, à partir d’aujourd’hui et dans les 12 prochains mois.');
    }

    $days = (int) $startDate->diff($endDate)->days;

    if ($days > 90) {
        throw new DomainException('Une réservation ne peut pas dépasser 90 jours.');
    }

    return ['start' => $start, 'end' => $end, 'days' => $days];
}

function priceInCents(string $price): int
{
    if (!preg_match('/\A([0-9]{1,8})(?:\.([0-9]{1,2}))?\z/', $price, $parts)) {
        throw new DomainException('Le prix doit être positif, avec au maximum deux décimales.');
    }

    $cents = (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');

    if ($cents < 1) {
        throw new DomainException('Le prix doit être supérieur à zéro.');
    }

    return $cents;
}

function decimalAmount(int $cents): string
{
    return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
}

function vehicleAvailable(PDO $connection, int $vehicleId, string $start, string $end, ?int $excludedBooking = null): bool
{
    $query = $connection->prepare("SELECT id FROM locations WHERE article_id = :vehicle AND status IN ('en_attente', 'confirmee', 'en_cours') AND start_date < :end_date AND end_date > :start_date" . ($excludedBooking !== null ? ' AND id <> :excluded' : '') . ' LIMIT 1');
    $parameters = ['vehicle' => $vehicleId, 'start_date' => $start, 'end_date' => $end];

    if ($excludedBooking !== null) {
        $parameters['excluded'] = $excludedBooking;
    }

    $query->execute($parameters);

    return $query->fetch() === false;
}

function createBooking(PDO $connection, int $userId, int $vehicleId, string $start, string $end, string $requestKey): array
{
    $range = dateRange($start, $end);

    if (!preg_match('/\A[a-f0-9]{64}\z/', $requestKey)) {
        throw new DomainException('Rechargez le formulaire de réservation.');
    }

    return databaseTransaction($connection, function () use ($connection, $userId, $vehicleId, $range, $requestKey): array {
        $query = $connection->prepare('SELECT * FROM articles WHERE id = :id' . lockSuffix($connection));
        $query->execute(['id' => $vehicleId]);
        $vehicle = $query->fetch();
        $existing = $connection->prepare('SELECT * FROM locations WHERE request_key = :key');
        $existing->execute(['key' => $requestKey]);
        $booking = $existing->fetch();

        if ($booking) {
            if ((int) $booking['user_id'] !== $userId || (int) $booking['article_id'] !== $vehicleId || $booking['start_date'] !== $range['start'] || $booking['end_date'] !== $range['end']) {
                throw new DomainException('Cette demande a déjà été utilisée. Rechargez le formulaire.');
            }

            return $booking;
        }

        $client = $connection->prepare('SELECT id FROM users WHERE id = :id AND is_active = 1');
        $client->execute(['id' => $userId]);

        if (!$client->fetch() || !$vehicle || !$vehicle['is_active'] || $vehicle['vehicle_model_id'] === null || $vehicle['status'] !== 'disponible') {
            throw new DomainException('Cette voiture ne peut pas être réservée actuellement.');
        }

        $model = vehicleModel($connection, (int) $vehicle['vehicle_model_id']);

        if (!$model || !vehicleAvailable($connection, $vehicleId, $range['start'], $range['end'])) {
            throw new DomainException('La voiture est déjà réservée sur ces dates. Essayez une autre période.');
        }

        $dailyCents = priceInCents((string) $vehicle['daily_price']);
        $reference = 'LP-' . strtoupper(bin2hex(random_bytes(6)));
        $query = $connection->prepare("INSERT INTO locations (user_id, article_id, start_date, end_date, daily_price, total_price, status, reference_code, request_key, vehicle_label) VALUES (:user_id, :article_id, :start_date, :end_date, :daily_price, :total_price, 'en_attente', :reference, :request_key, :vehicle_label)");
        $query->execute([
            'user_id' => $userId, 'article_id' => $vehicleId, 'start_date' => $range['start'], 'end_date' => $range['end'],
            'daily_price' => decimalAmount($dailyCents), 'total_price' => decimalAmount($dailyCents * $range['days']),
            'reference' => $reference, 'request_key' => $requestKey, 'vehicle_label' => $model['brand_name'] . ' ' . $model['model_name'] . ($vehicle['version'] ? ' ' . $vehicle['version'] : ''),
        ]);

        return ['id' => (int) $connection->lastInsertId(), 'reference_code' => $reference];
    });
}

function bookingTransitions(): array
{
    return ['en_attente' => ['confirmee', 'refusee', 'annulee'], 'confirmee' => ['en_cours', 'annulee'], 'en_cours' => ['terminee'], 'terminee' => [], 'annulee' => [], 'refusee' => []];
}

function changeBookingStatus(PDO $connection, int $bookingId, string $status, int $actorId, bool $agent): void
{
    $query = $connection->prepare('SELECT article_id FROM locations WHERE id = :id');
    $query->execute(['id' => $bookingId]);
    $vehicleId = $query->fetchColumn();

    if (!$vehicleId) {
        throw new DomainException('Réservation introuvable.');
    }

    databaseTransaction($connection, function () use ($connection, $bookingId, $vehicleId, $status, $actorId, $agent): void {
        $vehicleQuery = $connection->prepare('SELECT * FROM articles WHERE id = :id' . lockSuffix($connection));
        $vehicleQuery->execute(['id' => $vehicleId]);
        $vehicle = $vehicleQuery->fetch();
        $query = $connection->prepare('SELECT * FROM locations WHERE id = :id' . lockSuffix($connection));
        $query->execute(['id' => $bookingId]);
        $booking = $query->fetch();
        $actorQuery = $connection->prepare('SELECT role FROM users WHERE id = :id AND is_active = 1');
        $actorQuery->execute(['id' => $actorId]);
        $role = $actorQuery->fetchColumn();

        if (!$booking || !$vehicle) {
            throw new DomainException('Réservation ou voiture introuvable.');
        }

        if (!$role || ($agent && $role !== 'agent') || (!$agent && ((int) $booking['user_id'] !== $actorId || $status !== 'annulee' || $booking['start_date'] <= date('Y-m-d')))) {
            throw new DomainException('Cette action n’est pas autorisée.');
        }

        if (!in_array($status, bookingTransitions()[$booking['status']] ?? [], true)) {
            throw new DomainException('Ce changement de statut n’est pas autorisé.');
        }

        if ($status === 'en_cours' && ($booking['start_date'] > date('Y-m-d') || $booking['end_date'] <= date('Y-m-d'))) {
            throw new DomainException('Le départ doit avoir lieu pendant la période réservée.');
        }

        if ($status === 'terminee' && $booking['start_date'] > date('Y-m-d')) {
            throw new DomainException('Cette location n’a pas encore commencé.');
        }

        if (in_array($status, ['confirmee', 'en_cours'], true) && (!$vehicle['is_active'] || $vehicle['vehicle_model_id'] === null || $vehicle['status'] !== 'disponible' || !vehicleAvailable($connection, (int) $vehicleId, $booking['start_date'], $booking['end_date'], $bookingId))) {
            throw new DomainException('Vérifiez la disponibilité de cette voiture avant de confirmer.');
        }

        $query = $connection->prepare('UPDATE locations SET status = :status WHERE id = :id');
        $query->execute(['id' => $bookingId, 'status' => $status]);
    });
}

function clientBookings(PDO $connection, int $userId): array
{
    $query = $connection->prepare('SELECT l.*, a.name AS current_vehicle_name FROM locations l INNER JOIN articles a ON a.id = l.article_id WHERE l.user_id = :user_id ORDER BY l.created_at DESC, l.id DESC');
    $query->execute(['user_id' => $userId]);

    return $query->fetchAll();
}
