<?php

function optionalVehicleSpecifications(array $input): array
{
    $result = [];

    foreach (['seats' => [2, 9], 'manufacture_year' => [1950, (int) date('Y') + 1], 'mileage' => [0, 9999999], 'doors' => [2, 5], 'power_hp' => [1, 2000]] as $field => [$minimum, $maximum]) {
        $value = $input[$field] ?? '';

        if ($value === null || $value === '') {
            $result[$field] = null;
            continue;
        }

        if ((!is_string($value) && !is_int($value)) || !preg_match('/\A[0-9]+\z/', (string) $value) || (int) $value < $minimum || (int) $value > $maximum) {
            throw new DomainException('Caractéristique numérique invalide : ' . $field);
        }

        $result[$field] = (int) $value;
    }

    foreach (['fuel_type' => ['essence', 'diesel', 'hybride', 'electrique'], 'transmission' => ['manuelle', 'automatique'], 'air_conditioning' => ['0', '1']] as $field => $allowed) {
        $value = $input[$field] ?? '';

        if ($value === null || $value === '') {
            $result[$field] = null;
        } elseif ((is_string($value) || is_int($value)) && in_array((string) $value, $allowed, true)) {
            $result[$field] = $field === 'air_conditioning' ? (int) $value : $value;
        } else {
            throw new DomainException('Caractéristique invalide : ' . $field);
        }
    }

    return $result;
}

function publicVehicleSpecifications(array $vehicle): array
{
    $specifications = [];

    foreach (['category_name' => 'Catégorie', 'manufacture_year' => 'Année', 'seats' => 'Places', 'doors' => 'Portes'] as $key => $label) {
        if (isset($vehicle[$key]) && $vehicle[$key] !== '') {
            $specifications[$label] = $vehicle[$key];
        }
    }

    if (isset(fuelTypes()[$vehicle['fuel_type'] ?? ''])) {
        $specifications['Carburant'] = fuelTypes()[$vehicle['fuel_type']];
    }

    if (isset(transmissions()[$vehicle['transmission'] ?? ''])) {
        $specifications['Transmission'] = transmissions()[$vehicle['transmission']];
    }

    if (isset($vehicle['mileage'])) {
        $specifications['Kilométrage'] = number_format((int) $vehicle['mileage'], 0, ',', ' ') . ' km';
    }

    if (isset($vehicle['power_hp'])) {
        $specifications['Puissance'] = (int) $vehicle['power_hp'] . ' ch';
    }

    if (isset($vehicle['air_conditioning'])) {
        $specifications['Climatisation'] = $vehicle['air_conditioning'] ? 'Oui' : 'Non';
    }

    return $specifications;
}

function indicativePriceLabel(array $vehicle): string
{
    return !empty($vehicle['price_is_indicative']) ? 'Tarif indicatif, soumis à validation du propriétaire.' : '';
}
