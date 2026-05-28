<?php

declare(strict_types=1);

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

$menuItems = [
    [
        'label' => 'Dashboard',
        'icon' => 'dashboard',
        'href' => rtrim(BASE_URL, '/') . '/dashboard/index.php',
        'match' => '/dashboard/index.php',
    ],
    [
        'label' => 'Transactions',
        'icon' => 'receipt_long',
        'href' => rtrim(BASE_URL, '/') . '/transactions/index.php',
        'match' => '/transactions/',
    ],
    [
        'label' => 'Categories',
        'icon' => 'category',
        'href' => rtrim(BASE_URL, '/') . '/categories/index.php',
        'match' => '/categories/',
    ],
    [
        'label' => 'Reports',
        'icon' => 'assessment',
        'href' => rtrim(BASE_URL, '/') . '/reports/index.php',
        'match' => '/reports/',
    ],
];
?>
<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full border-r border-slate-200 bg-white shadow-xl transition-transform duration-300 md:static md:translate-x-0 md:shadow-none">
    <div class="flex h-full flex-col">
        <div class="border-b border-slate-200 px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">FinTrack Pro</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Navigation</h2>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-3">
            <?php foreach ($menuItems as $item): ?>
                <?php $isActive = str_contains($currentPath, $item['match']); ?>
                <a
                    href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition <?= $isActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>"
                >
                    <span class="material-symbols-outlined text-[20px] <?= $isActive ? 'text-blue-600' : 'text-slate-500'; ?>"><?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="border-t border-slate-200 p-3">
            <a
                href="<?= htmlspecialchars(rtrim(BASE_URL, '/') . '/auth/logout.php', ENT_QUOTES, 'UTF-8'); ?>"
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50"
            >
                <span class="material-symbols-outlined text-[20px]">logout</span>
                <span>Logout</span>
            </a>
        </div>
    </div>
</aside>

<div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/40 md:hidden" aria-hidden="true"></div>
