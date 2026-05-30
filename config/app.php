<?php

declare(strict_types=1);

define('APP_NAME', 'FinTrack Pro');
$baseUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost/fintrack-pro';

define('BASE_URL', rtrim($baseUrl, '/'));

date_default_timezone_set('Asia/Jakarta');
