<?php

declare(strict_types=1);

/**
 * Simple .env loader without external dependencies.
 */
function load_env(string $path = __DIR__ . '/../.env'): array
{
    $vars = [];

    if (!file_exists($path)) {
        return $vars;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $vars[trim($key)] = trim($value);
    }

    return $vars;
}
