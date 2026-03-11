<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $message = null): ?string
{
    start_session();
    if ($message === null) {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }

    $_SESSION['flash'][$key] = $message;
    return null;
}

function redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}
