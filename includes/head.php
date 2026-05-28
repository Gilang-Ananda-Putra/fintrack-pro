<?php

declare(strict_types=1);

if (!defined('APP_NAME') || !defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

$pageTitle = $pageTitle ?? APP_NAME;
$headExtra = $headExtra ?? '';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars((string) $pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
</style>

<link rel="stylesheet" href="<?= rtrim(BASE_URL, '/'); ?>/assets/css/style.css">

<?= $headExtra; ?>
