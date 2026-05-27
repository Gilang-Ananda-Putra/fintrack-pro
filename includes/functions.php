<?php

function redirect($path)
{
    header("Location: " . $path);
    exit;
}

function formatRupiah($number)
{
    return 'Rp ' . number_format($number, 0, ',', '.');
}