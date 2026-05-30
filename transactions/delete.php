<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Method tidak diizinkan.';
    header('Location: index.php');
    exit;
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!verify_csrf_token($csrfToken)) {
    $_SESSION['error_message'] = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    header('Location: index.php');
    exit;
}

$transactionId = trim((string) ($_POST['id'] ?? ''));
if (!ctype_digit($transactionId)) {
    $_SESSION['error_message'] = 'ID transaksi tidak valid.';
    header('Location: index.php');
    exit;
}

$deleteStmt = $pdo->prepare(
    'DELETE FROM transactions WHERE id = :id AND user_id = :user_id'
);
$deleteStmt->execute([
    'id' => (int) $transactionId,
    'user_id' => $userId,
]);

if ($deleteStmt->rowCount() > 0) {
    $_SESSION['success_message'] = 'Transaksi berhasil dihapus.';
} else {
    $_SESSION['error_message'] = 'Transaksi tidak ditemukan atau bukan milik Anda.';
}

header('Location: index.php');
exit;
