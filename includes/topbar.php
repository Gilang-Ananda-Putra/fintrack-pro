<?php

declare(strict_types=1);

if (!defined('APP_NAME') || !defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$pageTitle = 'Dashboard';

if (str_contains($currentPath, '/transactions/')) {
    $pageTitle = 'Transactions';
} elseif (str_contains($currentPath, '/categories/')) {
    $pageTitle = 'Categories';
} elseif (str_contains($currentPath, '/reports/')) {
    $pageTitle = 'Reports';
}

$displayName = trim((string) ($_SESSION['name'] ?? $_SESSION['username'] ?? 'User'));
$avatarInitial = strtoupper(substr($displayName, 0, 1));
?>
<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <button id="sidebar-toggle" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Toggle navigation menu" aria-controls="app-sidebar" aria-expanded="false">
                <span class="material-symbols-outlined">menu</span>
            </button>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?></p>
                <h1 class="text-lg font-bold text-slate-900 sm:text-xl"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="text-xs text-slate-500">Premium Member</p>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                <?= htmlspecialchars($avatarInitial !== '' ? $avatarInitial : 'U', ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    </div>
</header>

<script>
(() => {
    const sidebar = document.getElementById('app-sidebar');
    const toggleButton = document.getElementById('sidebar-toggle');
    const backdrop = document.getElementById('sidebar-backdrop');

    if (!sidebar || !toggleButton || !backdrop) {
        return;
    }

    const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
        toggleButton.setAttribute('aria-expanded', 'false');
    };

    const openSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
        toggleButton.setAttribute('aria-expanded', 'true');
    };

    toggleButton.addEventListener('click', () => {
        if (sidebar.classList.contains('-translate-x-full')) {
            openSidebar();
            return;
        }

        closeSidebar();
    });

    backdrop.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            backdrop.classList.add('hidden');
            toggleButton.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>
