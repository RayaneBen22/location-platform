<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/support.php';
require_once dirname(__DIR__) . '/app/vehicles.php';
require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/catalogue.php';
require_once dirname(__DIR__) . '/app/bookings.php';
require_once dirname(__DIR__) . '/app/photos.php';
require_once dirname(__DIR__) . '/app/view.php';
date_default_timezone_set('Africa/Casablanca');
$root = dirname(__DIR__);
$runtime = $root . '/var/test-' . bin2hex(random_bytes(6));
mkdir($runtime, 0700, true);
$passed = 0;

try {
    $serverDsn = getenv('TEST_SERVER_DSN');

    if ($serverDsn) {
        if (!str_starts_with($serverDsn, 'mysql:') || str_contains($serverDsn, 'dbname=')) {
            throw new RuntimeException('TEST_SERVER_DSN doit cibler un serveur de test sans nom de base.');
        }

        $connection = new PDO($serverDsn, getenv('TEST_DATABASE_USER') ?: null, getenv('TEST_DATABASE_PASSWORD') ?: null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $databaseName = 'location_platform_test_' . bin2hex(random_bytes(6));
        $connection->exec('CREATE DATABASE `' . $databaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $testDsn = $serverDsn . ';dbname=' . $databaseName;
        $connection = new PDO($testDsn, getenv('TEST_DATABASE_USER') ?: null, getenv('TEST_DATABASE_PASSWORD') ?: null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        $schema = preg_replace('/CREATE DATABASE.*?;|USE location_platform;/s', '', file_get_contents($root . '/database/schema.sql'));

        foreach (preg_split('/;\s*(?:\r?\n|$)/', $schema) as $statement) {
            if (trim($statement) !== '') {
                $connection->exec($statement);
            }
        }
    } else {
        $testDsn = 'sqlite:' . $runtime . '/catalogue.sqlite';
        $connection = new PDO($testDsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $connection->exec(file_get_contents(__DIR__ . '/fixtures/platform.sqlite.sql'));
        $connection->exec('PRAGMA busy_timeout = 10000');
    }

    $seed = preg_replace('/^USE location_platform;\s*/', '', file_get_contents($root . '/database/seed.sql'));

    foreach (preg_split('/;\s*(?:\r?\n|$)/', $seed) as $statement) {
        if (trim($statement) !== '') {
            $connection->exec($statement);
        }
    }

    $testPassword = 'Test-' . bin2hex(random_bytes(8));
    $query = $connection->prepare('INSERT INTO users (id, name, email, password, role) VALUES (:id, :name, :email, :password, :role)');

    foreach ([1 => 'client', 2 => 'client', 3 => 'agent'] as $id => $role) {
        $query->execute(['id' => $id, 'name' => 'Compte test ' . $id, 'email' => 'test' . $id . '@example.invalid', 'password' => password_hash($testPassword, PASSWORD_DEFAULT), 'role' => $role]);
    }

    $models = vehicleModels($connection);
    $categories = vehicleCategories($connection);
    $details = ['vehicle_model_id' => (string) $models[0]['id'], 'vehicle_category_id' => (string) $categories[0]['id'], 'daily_price' => '150.50', 'version' => 'Test', 'description' => 'Description de test', 'fuel_type' => 'essence', 'transmission' => 'manuelle', 'seats' => '5', 'manufacture_year' => '2024', 'mileage' => '20000', 'status' => 'disponible'];
    $vehicleId = saveVehicleDetails($connection, null, $details);
    $otherDetails = array_merge($details, ['vehicle_model_id' => (string) $models[1]['id'], 'fuel_type' => 'diesel', 'transmission' => 'automatique', 'daily_price' => '400', 'seats' => '7']);
    $otherVehicle = saveVehicleDetails($connection, null, $otherDetails);
    $connection->exec("INSERT INTO categories (id, name) VALUES (99, 'Informatique historique')");
    $connection->exec("INSERT INTO articles (id, category_id, name, daily_price) VALUES (99, 99, 'Ordinateur historique', 20)");
    check(cataloguePage($connection, [])['total'] === 2, 'Catalogue exclusivement automobile, objets historiques exclus');
    check(count(publicVehicles($connection)) === 2, 'Regression du catalogue initial');
    check(count(agentVehicles($connection)) === 3, 'Historique conserve pour les agents');
    check(cataloguePage($connection, ['fuel' => 'diesel', 'transmission' => 'automatique', 'seats' => '7'])['total'] === 1, 'Filtres carburant transmission et places combines');
    check(cataloguePage($connection, ['min_price' => '150', 'max_price' => '151', 'category' => $details['vehicle_category_id']])['total'] === 1, 'Filtres prix et categorie combines');
    check(cataloguePage($connection, ['brand' => (string) $models[0]['brand_id'], 'model' => (string) $models[0]['id']])['total'] === 1, 'Association marque modele coherente filtree');
    check(cataloguePage($connection, ['brand' => (string) $models[0]['brand_id'], 'model' => (string) $models[1]['id']])['total'] === 0, 'Association incoherente marque modele sans resultat');
    check(cataloguePage($connection, ['sort' => 'price_desc'])['vehicles'][0]['id'] == $otherVehicle, 'Tri du prix decroissant');
    check(cataloguePage($connection, ['page' => '999'], 1)['page'] === 2, 'Pagination bornee');
    check(cataloguePage($connection, ['q' => "' OR 1=1; DROP TABLE users;"])['total'] === 0, 'Recherche protegee contre injection SQL');
    rejects(fn () => cataloguePage($connection, ['brand' => '1 OR 1=1']), 'Identifiant filtre injecte refuse');
    rejects(fn () => cataloguePage($connection, ['min_price' => '500', 'max_price' => '1']), 'Budget inverse refuse');
    rejects(fn () => cataloguePage($connection, ['fuel' => 'kerosene']), 'Carburant non autorise refuse');
    rejects(fn () => saveVehicleDetails($connection, null, array_merge($details, ['seats' => '30'])), 'Caracteristiques invalides refusees');
    rejects(fn () => saveVehicleDetails($connection, null, array_merge($details, ['vehicle_model_id' => '9999'])), 'Modele forge refuse');

    foreach (['0', '-1', '1e3', '1.001', '100000000', ''] as $invalid) {
        rejects(fn () => priceInCents($invalid), 'Prix invalide refuse : ' . json_encode($invalid));
    }

    check(priceInCents('150.50') === 15050 && decimalAmount(45150) === '451.50', 'Calcul monetaire exact en centimes');
    check(money('150.50') === '150,50 MAD', 'Affichage du prix conserve les centimes');
    $start = date('Y-m-d', strtotime('+10 days'));
    $end = date('Y-m-d', strtotime('+13 days'));
    check(dateRange($start, $end)['days'] === 3, 'Calcul de duree avec retour exclusif');

    foreach ([['2027-02-30', '2027-03-03'], [$start, $start], [date('Y-m-d', strtotime('-1 day')), $end], [$start, date('Y-m-d', strtotime('+102 days'))]] as [$invalidStart, $invalidEnd]) {
        rejects(fn () => dateRange($invalidStart, $invalidEnd), 'Dates invalides refusees : ' . $invalidStart . ' / ' . $invalidEnd);
    }

    $key = bin2hex(random_bytes(32));
    $booking = createBooking($connection, 1, $vehicleId, $start, $end, $key);
    $bookingId = (int) $booking['id'];
    check($bookingId > 0 && str_starts_with($booking['reference_code'], 'LP-'), 'Reservation enregistree avec reference unique');
    check((int) createBooking($connection, 1, $vehicleId, $start, $end, $key)['id'] === $bookingId, 'Double soumission idempotente');
    rejects(fn () => createBooking($connection, 2, $vehicleId, $start, $end, $key), 'Cle idempotente non reutilisable par un autre client');
    rejects(fn () => createBooking($connection, 2, $vehicleId, $start, $end, bin2hex(random_bytes(32))), 'Reservation chevauchante refusee');
    rejects(fn () => createBooking($connection, 2, $vehicleId, date('Y-m-d', strtotime('+9 days')), date('Y-m-d', strtotime('+11 days')), bin2hex(random_bytes(32))), 'Chevauchement partiel refuse');
    $adjacent = createBooking($connection, 2, $vehicleId, $end, date('Y-m-d', strtotime('+14 days')), bin2hex(random_bytes(32)));
    check($adjacent['id'] > $bookingId, 'Reservations consecutives autorisees');
    $stored = $connection->query('SELECT * FROM locations WHERE id = ' . $bookingId)->fetch();
    check((float) $stored['daily_price'] === 150.50 && (float) $stored['total_price'] === 451.50, 'Tarif et total figes dans la reservation');
    saveVehicleDetails($connection, $vehicleId, array_merge($details, ['daily_price' => '200']));
    check((float) $connection->query('SELECT total_price FROM locations WHERE id = ' . $bookingId)->fetchColumn() === 451.50, 'Modification de tarif sans effet retroactif');
    rejects(fn () => changeBookingStatus($connection, $bookingId, 'annulee', 2, false), 'Annulation dune reservation dautrui refusee');
    rejects(fn () => changeBookingStatus($connection, $bookingId, 'confirmee', 1, true), 'Role agent forge refuse par le service');
    changeBookingStatus($connection, $bookingId, 'confirmee', 3, true);
    check($connection->query('SELECT status FROM locations WHERE id = ' . $bookingId)->fetchColumn() === 'confirmee', 'Confirmation par agent autorisee');
    rejects(fn () => changeBookingStatus($connection, $bookingId, 'en_cours', 3, true), 'Depart anticipe refuse');
    rejects(fn () => changeBookingStatus($connection, $bookingId, 'terminee', 3, true), 'Transition de statut interdite refusee');
    changeBookingStatus($connection, $bookingId, 'annulee', 1, false);
    check(vehicleAvailable($connection, $vehicleId, $start, $end), 'Annulation client libere les dates');
    check(cataloguePage($connection, ['start' => $end, 'end' => date('Y-m-d', strtotime('+14 days'))])['total'] === 1, 'Filtre de disponibilite exclut les dates occupees');
    check(!setVehicleActivation($connection, 99, 1), 'Objet historique non reactivable comme voiture');
    setVehicleActivation($connection, $otherVehicle, 0);
    check(vehicleDetails($connection, $otherVehicle) === false, 'Fiche voiture masquee inaccessible');
    rejects(fn () => createBooking($connection, 1, $otherVehicle, $start, $end, bin2hex(random_bytes(32))), 'Reservation de voiture masquee refusee');
    setVehicleActivation($connection, $otherVehicle, 1);
    saveVehicleDetails($connection, $otherVehicle, array_merge($otherDetails, ['status' => 'en_maintenance']));
    rejects(fn () => createBooking($connection, 1, $otherVehicle, $start, $end, bin2hex(random_bytes(32))), 'Reservation de voiture en maintenance refusee');
    saveVehicleDetails($connection, $otherVehicle, $otherDetails);
    saveReference($connection, 'brand', null, 'Marque de test');
    rejects(fn () => saveReference($connection, 'brand', null, 'Marque de test'), 'Doublon de reference refuse');
    rejects(fn () => saveReference($connection, 'model', null, 'Modele test', 99999), 'Modele sans marque refuse');
    rejects(fn () => saveReference($connection, 'users', null, 'Injection table'), 'Type de reference non autorise refuse');
    $concurrentVehicle = saveVehicleDetails($connection, null, $details);
    verifyConcurrentBookings($concurrentVehicle, date('Y-m-d', strtotime('+30 days')), date('Y-m-d', strtotime('+33 days')));
    check((int) $connection->query('SELECT COUNT(*) FROM locations WHERE article_id = ' . $concurrentVehicle)->fetchColumn() === 1, 'Une seule ligne apres deux ecritures simultanees');

    $client = issueSession(1);
    $otherClient = issueSession(2);
    $agentSession = issueSession(3);
    $agentPages = ['index.php', 'vehicules.php', 'vehicule.php', 'references.php', 'reservations.php', 'utilisateurs.php', 'photos.php', 'changer-activation.php'];

    foreach ($agentPages as $page) {
        check(requestPage('agent/' . $page, 'GET', [], $client['id'])['status'] === 403, 'Client interdit : agent/' . $page);
    }

    check(requestPage('agent/index.php')['status'] === 303, 'Agent anonyme redirige vers connexion');
    check(requestPage('agent/index.php', 'GET', [], $agentSession['id'])['status'] === 200, 'Tableau de bord accessible a lagent');

    foreach (['vehicules.php', 'vehicule.php', 'references.php', 'reservations.php', 'utilisateurs.php'] as $page) {
        check(requestPage('agent/' . $page, 'GET', [], $agentSession['id'])['status'] === 200, 'Page administration fonctionnelle : ' . $page);
    }

    foreach (['agent/vehicule.php', 'agent/references.php', 'agent/reservations.php', 'agent/utilisateurs.php', 'agent/photos.php', 'agent/changer-activation.php', 'mon-compte.php', 'deconnexion.php', 'annuler-reservation.php'] as $page) {
        $session = str_starts_with($page, 'agent/') ? $agentSession : $client;
        check(requestPage($page, 'POST', [], $session['id'])['status'] === 403, 'CSRF obligatoire : ' . $page);
    }

    check(requestPage('deconnexion.php', 'GET', [], $client['id'])['status'] === 405, 'Deconnexion GET refusee');
    check(requestPage('reservation.php', 'GET', [], $otherClient['id'], 'id=' . $bookingId)['status'] === 404, 'Reservation dautrui invisible par acces direct');
    check(requestPage('voiture.php', 'GET', [], '', 'id=99')['status'] === 404, 'Objet historique inaccessible par fiche directe');
    check(requestPage('voiture.php', 'GET', [], '', 'id[]=1')['status'] === 404, 'Identifiant tableau sans erreur PHP');
    $update = $connection->prepare('UPDATE articles SET description = :description WHERE id = :id');
    $update->execute(['description' => '<script>alert(1)</script>', 'id' => $vehicleId]);
    $page = requestPage('voiture.php', 'GET', [], '', 'id=' . $vehicleId);
    check($page['status'] === 200 && str_contains($page['body'], '&lt;script&gt;') && !str_contains($page['body'], '<script>alert'), 'Fiche protegee contre XSS stocke');
    check(str_contains($page['headers'], 'Content-Security-Policy:') && str_contains($page['headers'], 'X-Content-Type-Options: nosniff'), 'Entetes de securite emis');
    check(!str_contains(requestPage('catalogue.php')['body'], 'Ordinateur historique'), 'Catalogue public sans objet generaliste');
    $api = requestPage('api/disponibilite.php', 'GET', [], '', http_build_query(['id' => $vehicleId, 'start' => $start, 'end' => $end]));
    check($api['status'] === 200 && json_decode($api['body'], true)['available'] === true, 'API de disponibilite et de prix fonctionnelle');
    check(requestPage('api/disponibilite.php', 'GET', [], '', 'id=' . $vehicleId . '&start=invalid&end=invalid')['status'] === 422, 'API refuse les dates invalides');

    $bookingForm = requestPage('reserver.php', 'GET', [], $client['id'], http_build_query(['id' => $vehicleId, 'start' => $start, 'end' => $end]));
    preg_match('/name="request_key" value="([a-f0-9]+)"/', $bookingForm['body'], $keyMatch);
    $form = ['csrf_token' => $client['token'], 'id' => (string) $vehicleId, 'start' => $start, 'end' => $end, 'request_key' => $keyMatch[1] ?? '', 'accept_terms' => '1', 'daily_price' => '1', 'total_price' => '1', 'user_id' => '2'];
    $result = requestPage('reserver.php', 'POST', $form, $client['id']);
    check($result['status'] === 303, 'Reservation complete via formulaire HTTP CGI');
    $stored = $connection->query('SELECT * FROM locations ORDER BY id DESC LIMIT 1')->fetch();
    check((int) $stored['user_id'] === 1 && (float) $stored['total_price'] === 600.00, 'Prix et utilisateur falsifies ignores cote serveur');
    $countBefore = (int) $connection->query('SELECT COUNT(*) FROM locations')->fetchColumn();
    check(requestPage('reserver.php', 'POST', $form, $client['id'])['status'] === 303 && (int) $connection->query('SELECT COUNT(*) FROM locations')->fetchColumn() === $countBefore, 'Formulaire idempotent meme apres double clic');
    check(requestPage('agent/reservations.php', 'POST', ['csrf_token' => $agentSession['token'], 'id' => (string) $stored['id'], 'status' => 'confirmee'], $agentSession['id'])['status'] === 303, 'Confirmation par formulaire agent');
    check(requestPage('annuler-reservation.php', 'POST', ['csrf_token' => $client['token'], 'id' => (string) $stored['id']], $client['id'])['status'] === 303 && $connection->query('SELECT status FROM locations WHERE id = ' . (int) $stored['id'])->fetchColumn() === 'annulee', 'Annulation par formulaire client avant le depart');
    $newCar = array_merge($details, ['csrf_token' => $agentSession['token'], 'name' => 'Objet injecte', 'category_id' => '99']);
    check(requestPage('agent/vehicule.php', 'POST', $newCar, $agentSession['id'])['status'] === 303, 'Creation de voiture par formulaire agent');
    $lastCar = $connection->query('SELECT * FROM articles ORDER BY id DESC LIMIT 1')->fetch();
    check($lastCar['name'] !== 'Objet injecte' && (int) $lastCar['category_id'] !== 99, 'Creation automobile ignore les champs generalistes injectes');
    check(requestPage('agent/vehicule.php', 'POST', array_merge($newCar, ['daily_price' => '320.25']), $agentSession['id'], 'id=' . $lastCar['id'])['status'] === 303 && (float) $connection->query('SELECT daily_price FROM articles WHERE id = ' . (int) $lastCar['id'])->fetchColumn() === 320.25, 'Edition de voiture par formulaire agent');
    requestPage('agent/changer-activation.php', 'POST', ['csrf_token' => $agentSession['token'], 'id' => (string) $lastCar['id'], 'is_active' => '0'], $agentSession['id']);
    check(vehicleDetails($connection, (int) $lastCar['id']) === false, 'Masquage automobile effectif par formulaire agent');
    check(requestPage('agent/references.php', 'POST', ['csrf_token' => $agentSession['token'], 'kind' => 'brand', 'name' => 'Marque formulaire'], $agentSession['id'])['status'] === 303, 'Creation de marque par formulaire');
    $brandId = (int) $connection->query("SELECT id FROM vehicle_brands WHERE name = 'Marque formulaire'")->fetchColumn();
    check(requestPage('agent/references.php', 'POST', ['csrf_token' => $agentSession['token'], 'kind' => 'model', 'name' => 'Modele formulaire', 'brand_id' => (string) $brandId], $agentSession['id'])['status'] === 303, 'Creation de modele rattache par formulaire');
    check(requestPage('agent/references.php', 'POST', ['csrf_token' => $agentSession['token'], 'kind' => 'brand', 'id' => (string) $brandId, 'name' => 'Marque renommee'], $agentSession['id'])['status'] === 303, 'Renommage de marque sans suppression');
    $referenceCount = (int) $connection->query('SELECT COUNT(*) FROM vehicle_brands')->fetchColumn();
    check(str_contains(requestPage('agent/references.php', 'POST', ['csrf_token' => $agentSession['token'], 'kind' => 'brand', 'name' => 'Marque renommee'], $agentSession['id'])['body'], 'existe déjà') && (int) $connection->query('SELECT COUNT(*) FROM vehicle_brands')->fetchColumn() === $referenceCount, 'Doublon de marque affiche sans fuite SQL');
    $badProfile = requestPage('mon-compte.php', 'POST', ['csrf_token' => $client['token'], 'name' => ['bad'], 'phone' => ''], $client['id']);
    check(str_contains($badProfile['body'], 'Vérifiez'), 'Profil gere les entrees malformees');
    check(requestPage('mon-compte.php', 'POST', ['csrf_token' => $client['token'], 'name' => 'Nouveau nom', 'phone' => '+212600000000'], $client['id'])['status'] === 303 && $connection->query('SELECT name FROM users WHERE id = 1')->fetchColumn() === 'Nouveau nom', 'Modification du profil client fonctionnelle');
    $passwordBefore = $connection->query('SELECT password FROM users WHERE id = 1')->fetchColumn();
    requestPage('mon-compte.php', 'POST', ['csrf_token' => $client['token'], 'name' => 'Nouveau nom', 'phone' => '', 'new_password' => $testPassword . '-new', 'current_password' => 'incorrect'], $client['id']);
    check($connection->query('SELECT password FROM users WHERE id = 1')->fetchColumn() === $passwordBefore, 'Changement de mot de passe exige le mot de passe actuel');
    $beforeAgentRole = $connection->query('SELECT is_active FROM users WHERE id = 3')->fetchColumn();
    requestPage('agent/utilisateurs.php', 'POST', ['csrf_token' => $agentSession['token'], 'id' => '3', 'is_active' => '0'], $agentSession['id']);
    check($connection->query('SELECT is_active FROM users WHERE id = 3')->fetchColumn() == $beforeAgentRole, 'Un agent ne peut pas suspendre un autre agent');

    $signup = formSession(requestPage('inscription.php'));
    $registration = ['csrf_token' => $signup['token'], 'name' => 'Compte inscrit', 'email' => 'signup@example.invalid', 'phone' => '', 'password' => $testPassword, 'password_confirmation' => $testPassword, 'accept_terms' => '1', 'role' => 'agent'];
    check(requestPage('inscription.php', 'POST', $registration, $signup['id'])['status'] === 303, 'Inscription fonctionnelle avec CSRF');
    $registered = $connection->query("SELECT role, password FROM users WHERE email = 'signup@example.invalid'")->fetch();
    check($registered['role'] === 'client' && password_verify($testPassword, $registered['password']) && $registered['password'] !== $testPassword, 'Mot de passe hache et privilege dinjection refuse');
    check(requestPage('inscription.php', 'POST', [], $signup['id'])['status'] === 403, 'Inscription sans CSRF refusee');
    $loginPage = requestPage('connexion.php');
    $login = formSession($loginPage);
    $logged = requestPage('connexion.php', 'POST', ['csrf_token' => $login['token'], 'email' => 'test1@example.invalid', 'password' => $testPassword], $login['id']);
    check($logged['status'] === 303 && !str_contains($logged['headers'], 'LPSESSID=' . $login['id'] . ';'), 'Connexion et rotation didentifiant de session');
    check(str_contains($loginPage['headers'], 'HttpOnly') && str_contains($loginPage['headers'], 'SameSite=Lax'), 'Cookie de session HttpOnly et SameSite');
    $lockedLogin = formSession(requestPage('connexion.php'));

    for ($attempt = 0; $attempt < 8; $attempt++) {
        requestPage('connexion.php', 'POST', ['csrf_token' => $lockedLogin['token'], 'email' => 'blocked@example.invalid', 'password' => 'wrong'], $lockedLogin['id']);
    }

    check(requestPage('connexion.php', 'POST', ['csrf_token' => $lockedLogin['token'], 'email' => 'blocked@example.invalid', 'password' => 'wrong'], $lockedLogin['id'])['status'] === 429, 'Limitation des tentatives de connexion persistante');
    $connection->exec('UPDATE users SET is_active = 0 WHERE id = 2');
    check(requestPage('mon-compte.php', 'GET', [], $otherClient['id'])['status'] === 303, 'Suspension invalide une session deja ouverte');
    $connection->exec('UPDATE users SET is_active = 1 WHERE id = 2');

    $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l9sAAAAASUVORK5CYII=');
    $photoForm = ['csrf_token' => $agentSession['token'], 'vehicle_id' => (string) $vehicleId, 'action' => 'upload'];
    $upload = requestPage('agent/photos.php', 'POST', $photoForm, $agentSession['id'], '', ['name' => 'photo.png', 'type' => 'image/png', 'bytes' => $image]);
    check($upload['status'] === 303, 'Televersement reel multipart de photo via PHP CGI');
    $photo = $connection->query('SELECT * FROM article_images ORDER BY id DESC LIMIT 1')->fetch();
    check($photo && preg_match('/\Avehicle-[a-f0-9]{32}\.png\z/', $photo['image_path']) === 1, 'Nom de photo aleatoire sans chemin fourni par le client');
    $photoResponse = requestPage('photo.php', 'GET', [], '', 'id=' . $photo['id']);
    check($photoResponse['status'] === 200 && $photoResponse['body'] === $image && str_contains($photoResponse['headers'], 'image/png'), 'Photo servie avec type controle');
    $photoCount = (int) $connection->query('SELECT COUNT(*) FROM article_images')->fetchColumn();
    requestPage('agent/photos.php', 'POST', $photoForm, $agentSession['id'], '', ['name' => 'evil.php', 'type' => 'image/png', 'bytes' => '<?php echo "unsafe";']);
    check((int) $connection->query('SELECT COUNT(*) FROM article_images')->fetchColumn() === $photoCount, 'Faux type MIME et fichier executable refuses');
    requestPage('agent/photos.php', 'POST', ['csrf_token' => $agentSession['token'], 'vehicle_id' => (string) $vehicleId, 'photo_id' => (string) $photo['id'], 'action' => 'hide'], $agentSession['id']);
    check(requestPage('photo.php', 'GET', [], '', 'id=' . $photo['id'])['status'] === 404, 'Photo masquee inaccessible au public');
    check(requestPage('agent/utilisateurs.php', 'POST', ['csrf_token' => $agentSession['token'], 'id' => '2', 'is_active' => '0'], $agentSession['id'])['status'] === 303 && (int) $connection->query('SELECT is_active FROM users WHERE id = 2')->fetchColumn() === 0, 'Suspension client par formulaire agent');
    $oldSession = issueSession(1);
    file_put_contents($runtime . '/sess_' . $oldSession['id'], 'user|' . serialize(['id' => 1]) . 'last_seen|' . serialize(time() - 7201));
    check(requestPage('mon-compte.php', 'GET', [], $oldSession['id'])['status'] === 303, 'Session inactive depuis deux heures expiree');
    $logout = requestPage('deconnexion.php', 'POST', ['csrf_token' => $client['token']], $client['id']);
    check($logout['status'] === 303 && requestPage('mon-compte.php', 'GET', [], $client['id'])['status'] === 303, 'Deconnexion detruit la session');
    $passwordSession = issueSession(1);
    $otherDevice = issueSession(1);
    $passwordChange = requestPage('mon-compte.php', 'POST', ['csrf_token' => $passwordSession['token'], 'name' => 'Nouveau nom', 'phone' => '', 'current_password' => $testPassword, 'new_password' => $testPassword . '-new'], $passwordSession['id']);
    check($passwordChange['status'] === 303 && password_verify($testPassword . '-new', $connection->query('SELECT password FROM users WHERE id = 1')->fetchColumn()), 'Changement de mot de passe avec verification et hachage');
    check(requestPage('mon-compte.php', 'GET', [], $otherDevice['id'])['status'] === 303, 'Changement de mot de passe invalide les autres sessions');

    $sourceResult = processResult([PHP_BINARY, __DIR__ . '/source-policy.php']);
    check($sourceResult['code'] === 0, 'Absence de commentaires et palette centralisee : ' . trim($sourceResult['output']));
    echo $passed . ' verifications reussies sur ' . $connection->getAttribute(PDO::ATTR_DRIVER_NAME) . '.' . PHP_EOL;
    file_put_contents($runtime . '/results.json', json_encode(['checks' => $passed, 'driver' => $connection->getAttribute(PDO::ATTR_DRIVER_NAME), 'date' => date(DATE_ATOM)], JSON_PRETTY_PRINT));
} catch (Throwable $error) {
    fwrite(STDERR, 'ECHEC : ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
