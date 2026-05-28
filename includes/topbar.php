<?php

declare(strict_types=1);

/**
 * FinTrack Pro — Top Navigation Bar
 *
 * Menampilkan header atas dengan judul halaman aktif
 * dan informasi user yang sedang login.
 * Memerlukan session aktif dan BASE_URL/APP_NAME sudah terdefinisi.
 */

if (!defined('APP_NAME') || !defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

// Deteksi judul halaman berdasarkan URL path aktif
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

$topbarTitle = match (true) {
    str_contains($currentPath, '/dashboard/')                          => 'Dashboard',
    str_contains($currentPath, '/transactions/create')                 => 'Tambah Transaksi',
    str_contains($currentPath, '/transactions/edit')                   => 'Edit Transaksi',
    str_contains($currentPath, '/transactions/')                       => 'Transactions',
    str_contains($currentPath, '/categories/create')                   => 'Tambah Kategori',
    str_contains($currentPath, '/categories/edit')                     => 'Edit Kategori',
    str_contains($currentPath, '/categories/')                         => 'Categories',
    str_contains($currentPath, '/reports/')                            => 'Reports',
    str_contains($currentPath, '/settings/')                           => 'Settings',
    default                                                            => APP_NAME,
};

// Data user dari session
$displayName  = trim((string) ($_SESSION['name'] ?? $_SESSION['username'] ?? 'User'));
$avatarLetter = $displayName !== '' ? strtoupper(mb_substr($displayName, 0, 1)) : 'U';
?>
<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

        <!-- Kiri: Toggle Sidebar (mobile) + Judul -->
        <div class="flex items-center gap-3">
            <button
                id="sidebar-toggle"
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 md:hidden"
                aria-label="Buka/tutup menu navigasi"
                aria-controls="app-sidebar"
                aria-expanded="false"
            >
                <span class="material-symbols-outlined">menu</span>
            </button>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <h1 class="text-base font-bold text-slate-900 sm:text-lg leading-tight">
                    <?= htmlspecialchars($topbarTitle, ENT_QUOTES, 'UTF-8'); ?>
                </h1>
            </div>
        </div>

        <!-- Kanan: Info User -->
        <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
                <p class="text-sm font-semibold text-slate-900 leading-tight">
                    <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <p class="text-xs text-slate-500">Member Aktif</p>
            </div>
            <!-- Avatar dengan inisial -->
            <div
                class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white select-none"
                title="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>"
            >
                <?= htmlspecialchars($avatarLetter, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    </div>
</header>

<script>
(() => {
    const sidebar      = document.getElementById('app-sidebar');
    const toggleButton = document.getElementById('sidebar-toggle');
    const backdrop     = document.getElementById('sidebar-backdrop');

    if (!sidebar || !toggleButton || !backdrop) return;

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
        sidebar.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
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
