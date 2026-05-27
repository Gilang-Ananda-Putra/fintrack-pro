<?php

session_start();

require '../config/database.php';
require '../config/app.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Ambil data form
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi kosong
    if (
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $error = "Semua field wajib diisi.";

    }

    // Validasi password
    elseif ($password !== $confirm_password) {

        $error = "Konfirmasi password tidak cocok.";

    }

    // Validasi email sudah ada
    else {

        $checkQuery = "SELECT id FROM users WHERE email = ?";

        $checkStmt = $pdo->prepare($checkQuery);

        $checkStmt->execute([$email]);

        $existingUser = $checkStmt->fetch();

        if ($existingUser) {

            $error = "Email sudah digunakan.";

        } else {

            // Hash password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert user
            $insertQuery = "
                INSERT INTO users
                (name, email, password)
                VALUES (?, ?, ?)
            ";

            $insertStmt = $pdo->prepare($insertQuery);

            $insertStmt->execute([
                $name,
                $email,
                $hashedPassword
            ]);

            $success = "Register berhasil.";

            // Redirect ke login setelah 2 detik
            header("refresh:2;url=login.php");

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

        <!-- Error Message -->
        <?php if ($error): ?>

            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <?= $error; ?>
            </div>

        <?php endif; ?>

        <!-- Success Message -->
        <?php if ($success): ?>

            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                <?= $success; ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="mb-4">

                <label class="block mb-2">
                    Nama
                </label>

                <input
                    type="text"
                    name="name"
                    class="w-full border rounded px-4 py-2"
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