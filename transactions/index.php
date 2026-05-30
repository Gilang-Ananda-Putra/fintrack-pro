<?php

declare(strict_types=1);

/**
 * FinTrack Pro — Daftar Transaksi
 *
 * Menampilkan semua transaksi user dengan fitur:
 * - Pencarian berdasarkan title / kategori
 * - Filter berdasarkan tipe (income / expense)
 * - Pagination
 * - Tombol Edit dan Delete per baris
 * - Flash message (success / error) dari session
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

// ── Flash Messages ──────────────────────────────────────────────────────────
$flashSuccess = '';
$flashError   = '';

if (isset($_SESSION['success_message']) && is_string($_SESSION['success_message'])) {
    $flashSuccess = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message']) && is_string($_SESSION['error_message'])) {
    $flashError = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// ── Filter & Search ─────────────────────────────────────────────────────────
$search = trim((string) ($_GET['search'] ?? ''));
$type   = trim((string) ($_GET['type'] ?? ''));

if (!in_array($type, ['', 'income', 'expense'], true)) {
    $type = '';
}

// ── Pagination ───────────────────────────────────────────────────────────────
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// ── Build WHERE Clause ────────────────────────────────────────────────────────
$whereClauses = ['t.user_id = :user_id'];
$params       = ['user_id' => $userId];

if ($search !== '') {
    $whereClauses[] = '(t.title LIKE :search OR c.name LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($type !== '') {
    $whereClauses[] = 't.type = :type';
    $params['type'] = $type;
}

$whereSql = implode(' AND ', $whereClauses);

// ── Hitung Total Baris ────────────────────────────────────────────────────────
$countStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM transactions t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE {$whereSql}"
);
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

// Koreksi jika halaman melebihi total
if ($page > $totalPages) {
    $page   = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// ── Ambil Data Transaksi ──────────────────────────────────────────────────────
$listStmt = $pdo->prepare(
    "SELECT
        t.id,
        t.title,
        t.amount,
        t.type,
        t.transaction_date,
        c.name AS category_name
     FROM transactions t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE {$whereSql}
     ORDER BY t.transaction_date DESC, t.id DESC
     LIMIT :limit OFFSET :offset"
);

// Bind parameter filter terlebih dahulu
foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $listStmt->bindValue(':' . $key, $value, $paramType);
}

// Bind LIMIT dan OFFSET secara terpisah (wajib PARAM_INT)
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$transactions = $listStmt->fetchAll();

// ── Helper: Bangun URL Halaman ────────────────────────────────────────────────
$queryBase = [
    'search' => $search,
    'type'   => $type,
];

$buildPageUrl = static function (int $targetPage) use ($queryBase): string {
    $query = array_filter(
        [
            'search' => $queryBase['search'],
            'type'   => $queryBase['type'],
            'page'   => $targetPage,
        ],
        static fn ($value) => $value !== '' && $value !== null
    );

    return 'index.php' . (!empty($query) ? '?' . http_build_query($query) : '');
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php
    $pageTitle = 'Transactions — ' . APP_NAME;
    include __DIR__ . '/../includes/head.php';
    ?>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 min-w-0">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>

        <main class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-6xl mx-auto space-y-6">

                <!-- ── Header ────────────────────────────────────────────── -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Transactions</h1>
                        <p class="text-sm text-slate-500 mt-1">Kelola dan cari transaksi Anda.</p>
                    </div>
                    <a
                        href="create.php"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"
                    >
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Tambah Transaksi
                    </a>
                </div>

                <!-- ── Flash Messages ────────────────────────────────────── -->
                <?php if ($flashSuccess !== ''): ?>
                    <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        <span class="material-symbols-outlined text-emerald-500 text-[18px] mt-0.5 shrink-0">check_circle</span>
                        <span><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($flashError !== ''): ?>
                    <div class="flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                        <span class="material-symbols-outlined text-rose-500 text-[18px] mt-0.5 shrink-0">error</span>
                        <span><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>

                <!-- ── Filter & Search ───────────────────────────────────── -->
                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
                    <form method="GET" action="index.php" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-slate-700 mb-1">Cari Transaksi</label>
                            <input
                                id="search"
                                name="search"
                                type="text"
                                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="Cari berdasarkan judul / kategori…"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                            >
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Tipe Transaksi</label>
                            <select
                                id="type"
                                name="type"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                            >
                                <option value="" <?= $type === '' ? 'selected' : ''; ?>>Semua Tipe</option>
                                <option value="income" <?= $type === 'income' ? 'selected' : ''; ?>>Income</option>
                                <option value="expense" <?= $type === 'expense' ? 'selected' : ''; ?>>Expense</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="flex-1 inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition"
                            >
                                Filter
                            </button>
                            <a
                                href="index.php"
                                class="flex-1 inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition"
                            >
                                Reset
                            </a>
                        </div>
                    </form>
                </section>

                <!-- ── Tabel Transaksi ───────────────────────────────────── -->
                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-100 text-slate-700">
                                <tr>
                                    <th class="text-left px-4 py-3 font-semibold">Judul</th>
                                    <th class="text-left px-4 py-3 font-semibold">Kategori</th>
                                    <th class="text-left px-4 py-3 font-semibold">Tipe</th>
                                    <th class="text-left px-4 py-3 font-semibold">Tanggal</th>
                                    <th class="text-right px-4 py-3 font-semibold">Jumlah</th>
                                    <th class="text-center px-4 py-3 font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php if ($transactions === []): ?>
                                    <tr>
                                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                            <span class="material-symbols-outlined text-slate-300 text-5xl block mb-2">receipt_long</span>
                                            Tidak ada transaksi yang ditemukan.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $trx): ?>
                                        <?php
                                        $isIncome = $trx['type'] === 'income';
                                        $amount   = (float) $trx['amount'];
                                        $trxId    = (int) $trx['id'];
                                        ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <!-- Judul -->
                                            <td class="px-4 py-3 font-medium text-slate-900">
                                                <?= htmlspecialchars((string) $trx['title'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>

                                            <!-- Kategori -->
                                            <td class="px-4 py-3 text-slate-600">
                                                <?= htmlspecialchars((string) ($trx['category_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?>
                                            </td>

                                            <!-- Tipe (badge) -->
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold <?= $isIncome ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'; ?>">
                                                    <span class="material-symbols-outlined text-[13px]">
                                                        <?= $isIncome ? 'arrow_downward' : 'arrow_upward'; ?>
                                                    </span>
                                                    <?= $isIncome ? 'Income' : 'Expense'; ?>
                                                </span>
                                            </td>

                                            <!-- Tanggal -->
                                            <td class="px-4 py-3 text-slate-600">
                                                <?= htmlspecialchars((string) $trx['transaction_date'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>

                                            <!-- Jumlah -->
                                            <td class="px-4 py-3 text-right font-semibold <?= $isIncome ? 'text-emerald-700' : 'text-rose-700'; ?>">
                                                <?= $isIncome ? '+' : '−'; ?>
                                                <?= htmlspecialchars(formatRupiah($amount), ENT_QUOTES, 'UTF-8'); ?>
                                            </td>

                                            <!-- Aksi: Edit & Delete -->
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-center gap-2">
                                                    <!-- Tombol Edit -->
                                                    <a
                                                        href="edit.php?id=<?= $trxId; ?>"
                                                        title="Edit transaksi"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition"
                                                    >
                                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                                    </a>

                                                    <!-- Tombol Delete (form POST) -->
                                                    <form
                                                        method="POST"
                                                        action="delete.php"
                                                        onsubmit="return confirm('Yakin ingin menghapus transaksi ini?')"
                                                    >
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="id" value="<?= $trxId; ?>">
                                                        <button
                                                            type="submit"
                                                            title="Hapus transaksi"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700 transition"
                                                        >
                                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- ── Pagination ──────────────────────────────────────── -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm">
                        <p class="text-slate-600">
                            Menampilkan
                            <span class="font-medium text-slate-900">
                                <?= $totalRows === 0 ? 0 : $offset + 1; ?>–<?= min($offset + $perPage, $totalRows); ?>
                            </span>
                            dari
                            <span class="font-medium text-slate-900"><?= $totalRows; ?></span>
                            transaksi
                        </p>

                        <div class="flex items-center gap-2">
                            <a
                                href="<?= htmlspecialchars($buildPageUrl(max(1, $page - 1)), ENT_QUOTES, 'UTF-8'); ?>"
                                class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-100 transition <?= $page <= 1 ? 'pointer-events-none opacity-40' : ''; ?>"
                                aria-disabled="<?= $page <= 1 ? 'true' : 'false'; ?>"
                            >
                                <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                                Prev
                            </a>
                            <span class="px-2 text-slate-600">
                                Halaman <?= $page; ?> / <?= $totalPages; ?>
                            </span>
                            <a
                                href="<?= htmlspecialchars($buildPageUrl(min($totalPages, $page + 1)), ENT_QUOTES, 'UTF-8'); ?>"
                                class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-100 transition <?= $page >= $totalPages ? 'pointer-events-none opacity-40' : ''; ?>"
                                aria-disabled="<?= $page >= $totalPages ? 'true' : 'false'; ?>"
                            >
                                Next
                                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                            </a>
                        </div>
                    </div>
                </section>

            </div>
        </main>
    </div>
</div>
</body>
</html>
