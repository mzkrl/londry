<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
        ]);
    }
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf(string $token): bool
{
    start_session();
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function current_user(): ?array
{
    start_session();
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: /login.php');
        exit;
    }
}

function require_role(array $roles): void
{
    $user = current_user();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    if (!in_array($user['role'], $roles, true)) {
        redirect_dashboard();
    }
}

function redirect_dashboard(): void
{
    $user = current_user();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }

    switch ($user['role']) {
        case 'kasir':
            header('Location: /kasir/index.php');
            break;
        case 'admin':
            header('Location: /admin/products.php');
            break;
        case 'owner':
            header('Location: /owner/reports.php');
            break;
        default:
            header('Location: /login.php');
    }
    exit;
}
