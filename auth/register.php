<?php

session_start();

require '../config/database.php';
require '../config/app.php';
require '../includes/functions.php';

$error = '';
$success = '';

$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($csrfToken) || !verify_csrf_token($csrfToken)) {
        $error = 'Token CSRF tidak valid. Silakan coba lagi.';
    }

    // Ambil data form
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi kosong
    if (
        $error === '' &&
        (
            $name === '' ||
            $email === '' ||
            $password === '' ||
            $confirm_password === ''
        )
    ) {

        $error = 'Semua field wajib diisi.';

    // Validasi keamanan password
    } elseif (
        $error === '' &&
        (
            strlen($password) < 8 ||
            !preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/\d/', $password)
        )
    ) {

        $error = 'Password minimal 8 karakter dan harus mengandung minimal 1 huruf serta 1 angka.';

    // Validasi konfirmasi password
    } elseif ($error === '' && $password !== $confirm_password) {

        $error = 'Konfirmasi password tidak cocok.';

    // Validasi email sudah ada
    } elseif ($error === '') {

        $checkQuery = 'SELECT id FROM users WHERE email = ? LIMIT 1';
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$email]);

        if ($checkStmt->fetch()) {

            $error = 'Email sudah digunakan.';

        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertQuery = '
                INSERT INTO users
                (name, email, password)
                VALUES (?, ?, ?)
            ';

            $insertStmt = $pdo->prepare($insertQuery);
            $isInserted = $insertStmt->execute([
                $name,
                $email,
                $hashedPassword,
            ]);

            if ($isInserted) {
                $success = 'Register berhasil. Mengarahkan ke halaman login...';
                header('Refresh: 2; url=login.php');
            } else {
                $error = 'Terjadi kesalahan saat menyimpan data.';
            }

        }

    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <?php include '../includes/head.php'; ?>

</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md bg-white p-8 rounded-xl shadow">

        <h1 class="text-2xl font-bold mb-6 text-center">
            Register
        </h1>

        <?php if ($error): ?>

            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>

        <?php endif; ?>

        <?php if ($success): ?>

            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            </div>

        <?php endif; ?>

        <form method="POST">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"
            >

            <div class="mb-4">

                <label class="block mb-2">
                    Nama
                </label>

                <input
                    type="text"
                    name="name"
                    class="w-full border rounded px-4 py-2"
                    value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
                >

            </div>

            <div class="mb-4">

                <label class="block mb-2">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    class="w-full border rounded px-4 py-2"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                >

            </div>

            <div class="mb-4">

                <label class="block mb-2">
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    class="w-full border rounded px-4 py-2"
                >

            </div>

            <div class="mb-6">

                <label class="block mb-2">
                    Confirm Password
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    class="w-full border rounded px-4 py-2"
                >

            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition"
            >
                Register
            </button>

        </form>

        <p class="text-center mt-4 text-sm">

            Sudah punya akun?

            <a
                href="login.php"
                class="text-blue-600 hover:underline"
            >
                Login
            </a>

        </p>

    </div>

</body>
</html>
