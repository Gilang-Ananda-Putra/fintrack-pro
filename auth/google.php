<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header('Location: ' . rtrim(BASE_URL, '/') . '/dashboard/index.php');
    exit;
}

$clientId = trim((string) ($_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?: ''));
$clientSecret = trim((string) ($_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?: ''));
$redirectUri = trim((string) ($_ENV['GOOGLE_REDIRECT_URI'] ?? getenv('GOOGLE_REDIRECT_URI') ?: rtrim(BASE_URL, '/') . '/auth/google_callback.php'));

if ($clientId === '' || $clientSecret === '') {
    set_flash('error', 'Login Google belum dikonfigurasi. Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di file .env.');
    header('Location: ' . rtrim(BASE_URL, '/') . '/auth/login.php');
    exit;
}

$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;

$query = http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $query);
exit;