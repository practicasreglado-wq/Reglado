<?php

declare(strict_types=1);

loadBackendEnv(__DIR__ . DIRECTORY_SEPARATOR . '.env');

function loadBackendEnv(string $path): void
{
    static $loaded = false;
    if ($loaded || !is_file($path)) return;

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;

        $position = strpos($line, '=');
        if ($position === false) continue;

        $key = trim(substr($line, 0, $position));
        $value = trim(substr($line, $position + 1));
        if ($key === '') continue;

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    $loaded = true;
}
