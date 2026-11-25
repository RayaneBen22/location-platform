<?php

require_once __DIR__ . '/app/bootstrap.php';
$user = requireUser();
requirePost();

try {
    $id = positiveIdentifier($_POST['id'] ?? null);

    if (!$id) {
        throw new DomainException('Réservation introuvable.');
    }

    changeBookingStatus(database(), $id, 'annulee', (int) $user['id'], false);
    flashMessage('Votre réservation a été annulée.');
} catch (DomainException $exception) {
    flashMessage($exception->getMessage());
}

redirectTo('mon-compte.php');
