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
