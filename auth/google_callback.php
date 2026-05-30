<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$loginUrl = rtrim(BASE_URL, '/') . '/auth/login.php';
$dashboardUrl = rtrim(BASE_URL, '/') . '/dashboard/index.php';

$failGoogleLogin = static function (string $message) use ($loginUrl): never {
    unset($_SESSION['google_oauth_state']);
    set_flash('error', $message);
    header('Location: ' . $loginUrl);
    exit;
};

$httpRequest = static function (string $url, string $method = 'GET', array $headers = [], ?string $body = null): array {
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [$statusCode, $responseBody === false ? '' : (string) $responseBody];
    }

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $body ?? '',
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);

    $responseBody = file_get_contents($url, false, $context);
    $statusCode = 0;

    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $headerLine) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $headerLine, $matches) === 1) {
                $statusCode = (int) $matches[1];
                break;
            }
        }
    }

    return [$statusCode, $responseBody === false ? '' : (string) $responseBody];
};

if (isset($_GET['error'])) {
    $failGoogleLogin('Login Google dibatalkan atau ditolak. Silakan coba lagi.');
}

$code = trim((string) ($_GET['code'] ?? ''));
$state = trim((string) ($_GET['state'] ?? ''));
$sessionState = (string) ($_SESSION['google_oauth_state'] ?? '');

if ($code === '' || $state === '' || $sessionState === '' || !hash_equals($sessionState, $state)) {
    $failGoogleLogin('State login Google tidak valid. Silakan coba lagi.');
}

unset($_SESSION['google_oauth_state']);

$clientId = trim((string) ($_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?: ''));
$clientSecret = trim((string) ($_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?: ''));
$redirectUri = trim((string) ($_ENV['GOOGLE_REDIRECT_URI'] ?? getenv('GOOGLE_REDIRECT_URI') ?: rtrim(BASE_URL, '/') . '/auth/google_callback.php'));

if ($clientId === '' || $clientSecret === '') {
    $failGoogleLogin('Login Google belum dikonfigurasi lengkap. Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di file .env.');
}

[$tokenStatus, $tokenBody] = $httpRequest(
    'https://oauth2.googleapis.com/token',
    'POST',
    ['Content-Type: application/x-www-form-urlencoded'],
    http_build_query([
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
    ])
);

$tokenData = json_decode($tokenBody, true);

if ($tokenStatus < 200 || $tokenStatus >= 300 || !is_array($tokenData) || empty($tokenData['access_token'])) {
    $failGoogleLogin('Gagal menukar kode login Google. Pastikan redirect URI di Google Console sudah benar.');
}

[$profileStatus, $profileBody] = $httpRequest(
    'https://www.googleapis.com/oauth2/v3/userinfo',
    'GET',
    ['Authorization: Bearer ' . (string) $tokenData['access_token']]
);

$profile = json_decode($profileBody, true);

if ($profileStatus < 200 || $profileStatus >= 300 || !is_array($profile)) {
    $failGoogleLogin('Gagal mengambil profil Google. Silakan coba lagi.');
}

$googleId = trim((string) ($profile['sub'] ?? ''));
$email = trim((string) ($profile['email'] ?? ''));
$name = trim((string) ($profile['name'] ?? ''));
$avatarUrl = trim((string) ($profile['picture'] ?? ''));
$emailVerified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($googleId === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $failGoogleLogin('Profil Google tidak memiliki email valid.');
}

if (!$emailVerified) {
    $failGoogleLogin('Email Google belum terverifikasi. Verifikasi email Google terlebih dahulu.');
}

if ($name === '') {
    $name = strstr($email, '@', true) ?: 'Google User';
}

if ($avatarUrl !== '' && filter_var($avatarUrl, FILTER_VALIDATE_URL) === false) {
    $avatarUrl = '';
}

$userStmt = $pdo->prepare(
    'SELECT id, name, email
     FROM users
     WHERE google_id = :google_id OR email = :email
     LIMIT 1'
);
$userStmt->execute([
    'google_id' => $googleId,
    'email' => $email,
]);
$user = $userStmt->fetch();

if ($user === false) {
    $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $insertStmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, google_id, avatar_url, email_verified)
         VALUES (:name, :email, :password, :google_id, :avatar_url, 1)'
    );
    $insertStmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => $randomPassword,
        'google_id' => $googleId,
        'avatar_url' => $avatarUrl !== '' ? $avatarUrl : null,
    ]);

    $user = [
        'id' => (int) $pdo->lastInsertId(),
        'name' => $name,
        'email' => $email,
    ];
} else {
    $updateStmt = $pdo->prepare(
        'UPDATE users
         SET google_id = :google_id,
             avatar_url = :avatar_url,
             email_verified = 1
         WHERE id = :id'
    );
    $updateStmt->execute([
        'google_id' => $googleId,
        'avatar_url' => $avatarUrl !== '' ? $avatarUrl : null,
        'id' => (int) $user['id'],
    ]);
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['name'] = (string) $user['name'];
$_SESSION['email'] = (string) $user['email'];

header('Location: ' . $dashboardUrl);
exit;