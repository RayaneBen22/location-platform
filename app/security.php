<?php

function escape(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function appUrl(string $path = ''): string
{
    $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $directory = preg_replace('~/(agent|api)$~', '', $directory);
    $directory = $directory === '/' || $directory === '.' ? '' : rtrim($directory, '/');

    return $directory . '/' . ltrim($path, '/');
}

function redirectTo(string $path): never
{
    header('Location: ' . appUrl($path), true, 303);
    exit;
}

function csrfToken(): string
{
    $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . escape(csrfToken()) . '">';
}

function validateCsrf(): void
{
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        pageHeader('Formulaire expiré');
        echo '<section class="container section"><h1>Ce formulaire a expiré.</h1><p>Rechargez la page avant de réessayer.</p></section>';
        pageFooter();
        exit;
    }
}

function requirePost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    validateCsrf();
}

function currentUser(): array|false
{
    static $loaded = false;
    static $user = false;

    if ($loaded) {
        return $user;
    }

    $loaded = true;
    $userId = positiveIdentifier($_SESSION['user']['id'] ?? null);

    if ($userId === null) {
        return false;
    }

    if (isset($_SESSION['last_seen']) && time() - (int) $_SESSION['last_seen'] > 7200) {
        unset($_SESSION['user']);

        return false;
    }

    $query = database()->prepare('SELECT id, name, email, phone, role, is_active, session_version FROM users WHERE id = :id');
    $query->execute(['id' => $userId]);
    $user = $query->fetch();

    if (!$user || !(bool) $user['is_active'] || (int) ($_SESSION['auth_version'] ?? 0) !== (int) $user['session_version']) {
        unset($_SESSION['user']);
        $user = false;

        return false;
    }

    $_SESSION['last_seen'] = time();

    return $user;
}

function requireUser(): array
{
    $user = currentUser();

    if (!$user) {
        redirectTo('connexion.php');
    }

    header('Cache-Control: private, no-store');

    return $user;
}

function requireAgent(): array
{
    $user = requireUser();

    if ($user['role'] !== 'agent') {
        http_response_code(403);
        pageHeader('Accès réservé');
        echo '<section class="container section"><h1>Un espace réservé à l’équipe.</h1><p>Votre compte ne permet pas d’accéder à cette page.</p></section>';
        pageFooter();
        exit;
    }

    return $user;
}

function flashMessage(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;

        return null;
    }

    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $message;
}

function loginIdentity(string $email): array
{
    return [hash('sha256', 'email|' . mb_strtolower($email)), hash('sha256', 'ip|' . ($_SERVER['REMOTE_ADDR'] ?? 'local'))];
}

function loginBlocked(PDO $connection, string $email): bool
{
    [$identity, $address] = loginIdentity($email);
    $query = $connection->prepare('SELECT identity_hash, COUNT(*) AS attempts FROM login_attempts WHERE (identity_hash = :identity OR identity_hash = :address) AND created_at >= :cutoff GROUP BY identity_hash');
    $query->execute(['identity' => $identity, 'address' => $address, 'cutoff' => date('Y-m-d H:i:s', time() - 900)]);

    foreach ($query->fetchAll() as $attempt) {
        if ((int) $attempt['attempts'] >= ($attempt['identity_hash'] === $identity ? 8 : 40)) {
            return true;
        }
    }

    return false;
}

function recordLoginFailure(PDO $connection, string $email): void
{
    $query = $connection->prepare('INSERT INTO login_attempts (identity_hash, created_at) VALUES (:identity, :created_at)');

    foreach (loginIdentity($email) as $identity) {
        $query->execute(['identity' => $identity, 'created_at' => date('Y-m-d H:i:s')]);
    }
}
