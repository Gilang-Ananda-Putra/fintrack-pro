<?php

declare(strict_types=1);

if (!defined('APP_NAME') || !defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?></title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= rtrim(BASE_URL, '/'); ?>/assets/css/style.css">
