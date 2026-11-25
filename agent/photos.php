<?php

require_once __DIR__ . '/../app/agent.php';
requirePost();
$vehicleId = positiveIdentifier($_POST['vehicle_id'] ?? null);

try {
    if (!$vehicleId) {
        throw new DomainException('Voiture invalide.');
    }

    $action = submittedString($_POST, 'action');

    if ($action === 'upload') {
        uploadVehiclePhoto(database(), $vehicleId, $_FILES['photo'] ?? []);
    } elseif (in_array($action, ['show', 'hide'], true)) {
        databaseTransaction(database(), function () use ($vehicleId, $action): void {
            $lock = database()->prepare('SELECT id FROM articles WHERE id = :id AND vehicle_model_id IS NOT NULL' . lockSuffix(database()));
            $lock->execute(['id' => $vehicleId]);

            if (!$lock->fetch() || ($action === 'show' && count(vehiclePhotos(database(), $vehicleId)) >= 8)) {
                throw new DomainException('Voiture introuvable ou limite de photos atteinte.');
            }

            $query = database()->prepare('UPDATE article_images SET is_active = :active WHERE id = :id AND article_id = :vehicle');
            $query->execute(['active' => $action === 'show' ? 1 : 0, 'id' => positiveIdentifier($_POST['photo_id'] ?? null), 'vehicle' => $vehicleId]);

            if ($query->rowCount() === 0) {
                throw new DomainException('Photo introuvable ou déjà dans cet état.');
            }
        });
    } else {
        throw new DomainException('Action invalide.');
    }

    flashMessage('Les photos ont été mises à jour.');
} catch (DomainException $exception) {
    flashMessage($exception->getMessage());
}

redirectTo('agent/vehicule.php' . ($vehicleId ? '?id=' . $vehicleId : ''));
