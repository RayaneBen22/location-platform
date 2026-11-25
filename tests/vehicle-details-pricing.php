<?php

require_once __DIR__ . '/support.php';
require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/vehicles.php';
require_once dirname(__DIR__) . '/app/security.php';
require_once dirname(__DIR__) . '/app/catalogue.php';
require_once dirname(__DIR__) . '/app/bookings.php';
require_once dirname(__DIR__) . '/app/catalogue-pricing.php';
require_once dirname(__DIR__) . '/app/vehicle-media.php';
require_once dirname(__DIR__) . '/app/view.php';
date_default_timezone_set('Africa/Casablanca');
$root = dirname(__DIR__);
$runtime = $root . '/var/pricing-test-' . bin2hex(random_bytes(6));
mkdir($runtime, 0700, true);
$testDsn = 'sqlite:' . $runtime . '/catalogue.sqlite';
$connection = new PDO($testDsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$passed = 0;

try {
    $connection->exec(file_get_contents(__DIR__ . '/fixtures/platform.sqlite.sql'));
    $connection->exec(preg_replace('/^USE location_platform;\s*/', '', file_get_contents($root . '/database/seed.sql')));
    $connection->exec("INSERT INTO users (id, name, email, password) VALUES (1, 'Client test', 'pricing@example.invalid', 'test-only-hash')");
    $categoryId = (string) $connection->query("SELECT id FROM vehicle_categories WHERE name = 'Berline'")->fetchColumn();
    $input = ['vehicle_model_id' => '', 'vehicle_category_id' => $categoryId, 'daily_price' => '199.75', 'status' => 'disponible'];

    foreach (vehicleModels($connection) as $model) {
        $input['vehicle_model_id'] = (string) $model['id'];
        saveVehicleDetails($connection, null, $input);
    }

    check(publicVehicleSpecifications([]) === [], 'Aucune caracteristique inventee sans donnees');
    $known = publicVehicleSpecifications(['mileage' => 0, 'air_conditioning' => 0, 'doors' => 4, 'power_hp' => 150]);
    check($known === ['Portes' => 4, 'Kilométrage' => '0 km', 'Puissance' => '150 ch', 'Climatisation' => 'Non'], 'Zero kilometre et absence de climatisation conserves');
    check(count(array_filter(optionalVehicleSpecifications([]), fn ($value) => $value !== null)) === 0, 'Champs inconnus stockables a NULL');

    foreach (['seats' => '1', 'manufacture_year' => '1800', 'mileage' => '-1', 'doors' => '6', 'power_hp' => '0', 'air_conditioning' => 'oui', 'fuel_type' => 'kerosene', 'transmission' => ['automatique']] as $field => $value) {
        rejects(fn () => optionalVehicleSpecifications([$field => $value]), 'Caracteristique invalide refusee : ' . $field);
    }

    $start = date('Y-m-d', strtotime('+20 days'));
    $end = date('Y-m-d', strtotime('+23 days'));
    $historical = createBooking($connection, 1, 1, $start, $end, bin2hex(random_bytes(32)));
    $report = applyIndicativeCataloguePricing($connection);
    check(count($report) === 6 && count(array_filter($report, fn ($row) => $row['old_daily_price'] === '199.75')) === 6, 'Six anciens et nouveaux tarifs traces');
    $historicalAfter = $connection->query('SELECT daily_price, total_price FROM locations WHERE id = ' . (int) $historical['id'])->fetch();
    check(priceInCents((string) $historicalAfter['daily_price']) === 19975 && priceInCents((string) $historicalAfter['total_price']) === 59925, 'Reservations historiques non revalorisees');
    $firstPass = $connection->query('SELECT * FROM articles ORDER BY id')->fetchAll();
    applyIndicativeCataloguePricing($connection);
    check($firstPass === $connection->query('SELECT * FROM articles ORDER BY id')->fetchAll(), 'Reapplication des propositions sans changement supplementaire');
    integrateCatalogueMedia($connection);
    $catalogue = requestPage('catalogue.php');
    check($catalogue['status'] === 200 && !preg_match('/à préciser/iu', $catalogue['body']) && substr_count($catalogue['body'], 'Tarif indicatif') === 6, 'Catalogue sans champs inconnus et six mentions tarifaires');
    $ownerSession = issueSession(1);
    $nextStart = date('Y-m-d', strtotime('+25 days'));
    $nextEnd = date('Y-m-d', strtotime('+28 days'));

    foreach (cataloguePage($connection, [])['vehicles'] as $vehicle) {
        $label = vehicleLabel($vehicle);
        $price = money($vehicle['daily_price']);
        $page = requestPage('voiture.php', 'GET', [], '', 'id=' . $vehicle['id']);
        check($page['status'] === 200 && str_contains($page['body'], escape($price)) && str_contains($page['body'], 'Tarif indicatif') && !preg_match('/à préciser/iu', $page['body']), $label . ' : fiche sans faux details au tarif de la base');
        check(str_contains($catalogue['body'], escape($price)), $label . ' : tarif coherent dans le catalogue');
        $api = requestPage('api/disponibilite.php', 'GET', [], '', http_build_query(['id' => $vehicle['id'], 'start' => $nextStart, 'end' => $nextEnd]));
        $estimate = json_decode($api['body'], true, 512, JSON_THROW_ON_ERROR);
        check($estimate['available'] && $estimate['days'] === 3 && priceInCents($estimate['total']) === 3 * priceInCents((string) $vehicle['daily_price']) && $estimate['price_is_indicative'] === true, $label . ' : devis trois jours exact et indicatif');
        $bookingPage = requestPage('reserver.php', 'GET', [], $ownerSession['id'], http_build_query(['id' => $vehicle['id'], 'start' => $nextStart, 'end' => $nextEnd]));
        check($bookingPage['status'] === 200 && str_contains($bookingPage['body'], 'Tarif indicatif') && str_contains($bookingPage['body'], escape(money($estimate['total']))), $label . ' : recapitulatif coherent');
        $booking = createBooking($connection, 1, (int) $vehicle['id'], $nextStart, $nextEnd, bin2hex(random_bytes(32)));
        $storedTotal = $connection->query('SELECT total_price FROM locations WHERE id = ' . (int) $booking['id'])->fetchColumn();
        check(priceInCents((string) $storedTotal) === priceInCents($estimate['total']), $label . ' : reservation au tarif de la base');
        rejects(fn () => createBooking($connection, 1, (int) $vehicle['id'], $nextStart, $nextEnd, bin2hex(random_bytes(32))), $label . ' : chevauchement refuse cote serveur');
    }

    $validVehicle = vehicleDetails($connection, 1);
    $validInput = array_merge($validVehicle, ['daily_price' => (string) $validVehicle['daily_price'], 'doors' => '4', 'power_hp' => '150', 'air_conditioning' => '0', 'price_is_indicative' => '0']);
    saveVehicleDetails($connection, 1, $validInput);
    $updated = vehicleDetails($connection, 1);
    check($updated['doors'] == 4 && $updated['power_hp'] == 150 && $updated['air_conditioning'] == 0 && indicativePriceLabel($updated) === '', 'Specifications connues et validation explicite enregistrables');
    rejects(fn () => saveVehicleDetails($connection, 1, array_merge($validInput, ['price_is_indicative' => '2'])), 'Validation tarifaire malformee refusee');
    $incomplete = $connection->query("SELECT id FROM vehicle_brands WHERE name = 'Toyota'")->fetchColumn();
    $connection->exec('UPDATE vehicle_brands SET name = name || \' absent\' WHERE id = ' . (int) $incomplete);
    $beforeFailure = $connection->query('SELECT id, daily_price, price_is_indicative FROM articles ORDER BY id')->fetchAll();
    rejects(fn () => applyIndicativeCataloguePricing($connection), 'Modele absent interrompt la mise a jour');
    check($beforeFailure === $connection->query('SELECT id, daily_price, price_is_indicative FROM articles ORDER BY id')->fetchAll(), 'Mise a jour tarifaire atomique sans modele manquant');
    echo $passed . " verifications tarifs et caracteristiques reussies.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
