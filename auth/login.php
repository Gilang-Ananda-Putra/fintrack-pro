<?php

session_start();

require '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $query = 'SELECT id, password FROM users WHERE email = ? LIMIT 1';
        $stmt = $pdo->prepare($query);
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];

            header('Location: ../dashboard/index.php');
            exit;
        }

        $error = 'Email atau password salah.';
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
        <h1 class="text-2xl font-bold mb-6 text-center">Login</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block mb-2" for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    class="w-full border rounded px-4 py-2"
                    required
                >
            </div>

            <div class="mb-6">
                <label class="block mb-2" for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="w-full border rounded px-4 py-2"
                    required
                >
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition"
            >
                Login
            </button>
        </form>

        <p class="text-center mt-4 text-sm">
            Belum punya akun?
            <a href="register.php" class="text-blue-600 hover:underline">Register</a>
        </p>
    </div>
</body>
</html>
