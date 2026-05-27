<?php

session_start();

require '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = ?";

    $stmt = $pdo->prepare($query);

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['id'];

        header("Location: ../dashboard/index.php");
        exit;

    } else {

        $error = "Email atau password salah";

    }

}
?>