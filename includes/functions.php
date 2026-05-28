<?php

declare(strict_types=1);

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function formatRupiah(float|int $number): string
{
    return 'Rp ' . number_format($number, 0, ',', '.');
}

function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($sessionToken)
        && $sessionToken !== ''
        && hash_equals($sessionToken, $token);
}
