<?php

declare(strict_types=1);

$envPath = dirname(__DIR__) . '/.env';

if (is_readable($envPath)) {
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($envLines !== false) {
        foreach ($envLines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, "\"'");

            if ($key !== '') {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}
