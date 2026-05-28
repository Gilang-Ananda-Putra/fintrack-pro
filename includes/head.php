<?php

declare(strict_types=1);

if (!defined('APP_NAME') || !defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

$pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : APP_NAME;
$headExtra = isset($headExtra) && is_string($headExtra) ? $headExtra : '';
$metaDesc = isset($metaDesc) && is_string($metaDesc) ? $metaDesc : 'FinTrack Pro — Aplikasi pencatatan keuangan pribadi yang mudah dan aman.';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8'); ?>">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script>
    tailwind.config = {darkMode: 'class', theme: {extend: {colors: {surface: '#faf8ff', background: '#faf8ff', primary: '#003ec7', 'primary-container': '#0052ff', secondary: '#006e2f', outline: '#737688', 'outline-variant': '#c3c5d9', 'surface-container-low': '#f2f3ff', 'surface-container-lowest': '#ffffff', 'surface-container-high': '#e2e7ff', 'on-surface': '#131b2e', 'on-surface-variant': '#434656', 'on-primary': '#ffffff', error: '#ba1a1a'}}, fontFamily: {sans: ['Inter', 'sans-serif']}}}};
</script>
<style>
.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
.icon-fill { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
<link rel="stylesheet" href="<?= htmlspecialchars(rtrim(BASE_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
<?= $headExtra; ?>
