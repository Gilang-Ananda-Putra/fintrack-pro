<?php

declare(strict_types=1);

/**
 * FinTrack Pro — Head Include
 *
 * Sisipkan di dalam tag <head> pada setiap halaman.
 * Variabel $pageTitle dan $headExtra dapat di-set sebelum include ini.
 */

if (!defined('APP_NAME') || !defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

$pageTitle   = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : APP_NAME;
$headExtra   = isset($headExtra) && is_string($headExtra) ? $headExtra : '';
$metaDesc    = isset($metaDesc)  && is_string($metaDesc)  ? $metaDesc  : 'FinTrack Pro — Aplikasi pencatatan keuangan pribadi yang mudah dan aman.';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8'); ?>">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<!-- Google Fonts: Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

<!-- Material Symbols (Google Icons) -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<style>
    /* Default icon style: outline, weight 400 */
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    /* Filled icon variant */
    .icon-fill {
        font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
</style>

<!-- Custom Stylesheet -->
<link rel="stylesheet" href="<?= htmlspecialchars(rtrim(BASE_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">

<?= $headExtra; ?>
