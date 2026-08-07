<?php
// ============================================================
// .env Loader — Programmers Lab
// Reads key=value pairs from .env into getenv() / $_ENV.
// Call this once at the very top of config.php.
// ============================================================
function load_env(string $path): void {
    if (!is_file($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and blank lines
        if ($line === '' || str_starts_with($line, '#')) continue;

        if (!str_contains($line, '=')) continue;

        [$key, $value] = array_map('trim', explode('=', $line, 2));

        // Strip optional surrounding quotes
        $value = trim($value, '"\'');

        if ($key === '') continue;

        // Only set if not already defined by the server environment
        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

load_env(__DIR__ . '/.env');
