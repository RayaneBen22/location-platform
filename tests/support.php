<?php

function check(bool $condition, string $message): void
{
    global $passed;

    if (!$condition) {
        throw new RuntimeException($message);
    }

    $passed++;
    echo 'OK ' . $message . PHP_EOL;
}

function rejects(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (DomainException $exception) {
        check(true, $message);

        return;
    }

    check(false, $message);
}

function processResult(array $command, string $input = '', ?array $environment = null): array
{
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);

    if (!is_resource($process)) {
        throw new RuntimeException('Impossible de lancer un processus de test.');
    }

    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['code' => proc_close($process), 'output' => $output, 'error' => $error];
}

function issueSession(int $userId): array
{
    global $runtime;
    $session = bin2hex(random_bytes(16));
    $token = bin2hex(random_bytes(32));
    file_put_contents($runtime . '/sess_' . $session, 'user|' . serialize(['id' => $userId]) . 'auth_version|' . serialize(1) . 'csrf_token|' . serialize($token) . 'last_seen|' . serialize(time()));

    return ['id' => $session, 'token' => $token];
}

function requestPage(string $page, string $method = 'GET', array $form = [], string $session = '', string $query = '', ?array $upload = null): array
{
    global $root, $runtime, $testDsn;
    $input = http_build_query($form);
    $contentType = 'application/x-www-form-urlencoded';

    if ($upload !== null) {
        $boundary = 'test-' . bin2hex(random_bytes(10));
        $contentType = 'multipart/form-data; boundary=' . $boundary;
        $input = '';

        foreach ($form as $key => $value) {
            $input .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"" . $key . "\"\r\n\r\n" . $value . "\r\n";
        }

        $input .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"photo\"; filename=\"" . $upload['name'] . "\"\r\nContent-Type: " . $upload['type'] . "\r\n\r\n" . $upload['bytes'] . "\r\n--" . $boundary . "--\r\n";
    }

    $environment = getenv();
    $environment['SCRIPT_FILENAME'] = $root . '/' . $page;
    $environment['SCRIPT_NAME'] = '/' . $page;
    $environment['REQUEST_URI'] = '/' . $page . ($query === '' ? '' : '?' . $query);
    $environment['REQUEST_METHOD'] = $method;
    $environment['SERVER_PROTOCOL'] = 'HTTP/1.1';
    $environment['SERVER_NAME'] = 'localhost';
    $environment['REMOTE_ADDR'] = '127.0.0.1';
    $environment['QUERY_STRING'] = $query;
    $environment['REDIRECT_STATUS'] = '1';
    $environment['CONTENT_TYPE'] = $contentType;
    $environment['CONTENT_LENGTH'] = (string) strlen($input);
    $environment['HTTP_COOKIE'] = $session === '' ? '' : 'LPSESSID=' . $session;
    $environment['APP_ENV'] = 'testing';
    $environment['APP_DATABASE_DSN'] = $testDsn;
    $environment['APP_DATABASE_USER'] = getenv('TEST_DATABASE_USER') ?: '';
    $environment['APP_DATABASE_PASSWORD'] = getenv('TEST_DATABASE_PASSWORD') ?: '';
    $cgi = getenv('PHP_CGI_BINARY') ?: dirname(PHP_BINARY) . (PHP_OS_FAMILY === 'Windows' ? '/php-cgi.exe' : '/php-cgi');
    $result = processResult([$cgi, '-d', 'cgi.force_redirect=0', '-d', 'session.save_path=' . $runtime, '-d', 'display_errors=0', '-d', 'log_errors=1', '-d', 'upload_tmp_dir=' . $runtime], $input, $environment);

    if ($result['code'] !== 0 || $result['error'] !== '') {
        throw new RuntimeException('Erreur CGI : ' . $page . ' ' . $result['error']);
    }

    $parts = preg_split('/\r?\n\r?\n/', $result['output'], 2);
    $headers = $parts[0];
    $status = preg_match('/^Status: (\d+)/mi', $headers, $matches) ? (int) $matches[1] : (stripos($headers, 'Location:') !== false ? 302 : 200);

    return ['status' => $status, 'headers' => $headers, 'body' => $parts[1] ?? ''];
}

function formSession(array $page): array
{
    preg_match('/LPSESSID=([a-zA-Z0-9,-]+)/', $page['headers'], $session);
    preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $page['body'], $token);

    return ['id' => $session[1] ?? '', 'token' => $token[1] ?? ''];
}

function verifyConcurrentBookings(int $vehicleId, string $start, string $end): void
{
    global $root, $runtime, $testDsn;
    $gate = $runtime . '/concurrency-gate';
    $processes = [];

    for ($number = 0; $number < 2; $number++) {
        $environment = getenv();
        $environment['APP_ENV'] = 'testing';
        $environment['APP_DATABASE_DSN'] = $testDsn;
        $environment['APP_DATABASE_USER'] = getenv('TEST_DATABASE_USER') ?: '';
        $environment['APP_DATABASE_PASSWORD'] = getenv('TEST_DATABASE_PASSWORD') ?: '';
        $process = proc_open([PHP_BINARY, $root . '/tests/booking-worker.php', (string) ($number + 1), (string) $vehicleId, $start, $end, $gate], [0 => ['pipe', 'r'], 1 => ['file', $runtime . '/worker-' . $number . '.log', 'w'], 2 => ['file', $runtime . '/worker-' . $number . '.error', 'w']], $pipes, null, $environment);

        if (!is_resource($process)) {
            throw new RuntimeException('Impossible de lancer le test concurrent.');
        }

        fclose($pipes[0]);
        $processes[] = $process;
    }

    file_put_contents($gate, 'start');
    $codes = array_map('proc_close', $processes);
    sort($codes);
    check($codes === [0, 2], 'Deux demandes concurrentes : une seule reservation acceptee');
}
