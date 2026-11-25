<?php

function pageHeader(string $title, string $active = ''): void
{
    $user = currentUser();
    $flash = flashMessage();
    require __DIR__ . '/views/header.php';
}

function pageFooter(): void
{
    require __DIR__ . '/views/footer.php';
}

function vehicleLabel(array $vehicle): string
{
    $version = trim($vehicle['version'] ?? '');

    if (in_array(mb_strtolower($version), ['démonstration', 'demo', 'test', 'exemple'], true)) {
        $version = '';
    }

    return trim(($vehicle['brand_name'] ?? '') . ' ' . ($vehicle['model_name'] ?? '') . ' ' . $version);
}

function money(mixed $amount): string
{
    return number_format((float) $amount, floor((float) $amount) === (float) $amount ? 0 : 2, ',', ' ') . ' MAD';
}

function statusLabel(string $status): string
{
    return ['disponible' => 'Disponible', 'en_location' => 'Indisponible', 'en_maintenance' => 'En préparation', 'en_attente' => 'En attente', 'confirmee' => 'Confirmée', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée', 'refusee' => 'Refusée'][$status] ?? 'À vérifier';
}

function vehicleCard(array $vehicle, array $dates = []): void
{
    $parameters = ['id' => (int) $vehicle['id']];

    if (!empty($dates['start']) && !empty($dates['end'])) {
        $parameters['start'] = $dates['start'];
        $parameters['end'] = $dates['end'];
    }

    $detailsUrl = appUrl('voiture.php?' . http_build_query($parameters));
    require __DIR__ . '/views/vehicle-card.php';
}

function selectOptions(array $items, mixed $selected, string $label = 'name'): void
{
    foreach ($items as $item) {
        echo '<option value="' . (int) $item['id'] . '"' . ((string) $selected === (string) $item['id'] ? ' selected' : '') . '>' . escape($item[$label]) . '</option>';
    }
}

function choiceOptions(array $items, string $selected): void
{
    foreach ($items as $value => $label) {
        echo '<option value="' . escape($value) . '"' . ($selected === (string) $value ? ' selected' : '') . '>' . escape($label) . '</option>';
    }
}

function agentNavigation(string $active): void
{
    echo '<nav class="dashboard-tabs" aria-label="Administration">';

    foreach (['index.php' => 'Vue d’ensemble', 'vehicules.php' => 'Véhicules', 'reservations.php' => 'Réservations', 'references.php' => 'Marques & modèles', 'utilisateurs.php' => 'Clients'] as $path => $label) {
        echo '<a' . ($path === $active ? ' aria-current="page"' : '') . ' href="' . escape(appUrl('agent/' . $path)) . '">' . escape($label) . '</a>';
    }

    echo '</nav>';
}
