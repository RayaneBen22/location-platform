<?php
require_once __DIR__ . '/../app/agent.php';
requirePost();
try {
    $id = positiveIdentifier($_POST['id'] ?? null);
    $active = submittedString($_POST, 'is_active');
    if (!$id || !in_array($active, ['0', '1'], true)) throw new DomainException('Voiture ou état invalide.');
    databaseTransaction(database(), function () use ($id, $active): void {
        $lock = database()->prepare('SELECT id FROM articles WHERE id = :id' . lockSuffix(database()));
        $lock->execute(['id' => $id]);
        if (!$lock->fetch() || !setVehicleActivation(database(), $id, (int) $active)) throw new DomainException('Vérifiez le modèle automobile avant toute activation.');
    });
    flashMessage('La visibilité de la voiture a été mise à jour.');
} catch (DomainException $exception) {
    flashMessage($exception->getMessage());
}
redirectTo('agent/vehicules.php');
