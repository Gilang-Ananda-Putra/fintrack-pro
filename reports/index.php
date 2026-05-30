<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

$currentMonth = (int) date('n');
$currentYear = (int) date('Y');

$monthParam = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 12],
]);
$yearParam = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1970, 'max_range' => 9999],
]);

$selectedMonth = $monthParam !== false && $monthParam !== null ? $monthParam : $currentMonth;
$selectedYear = $yearParam !== false && $yearParam !== null ? $yearParam : $currentYear;

$yearRangeStmt = $pdo->prepare(
    "SELECT
        MIN(YEAR(transaction_date)) AS min_year,
        MAX(YEAR(transaction_date)) AS max_year
     FROM transactions
     WHERE user_id = :user_id"
);
$yearRangeStmt->execute(['user_id' => $userId]);
$yearRange = $yearRangeStmt->fetch() ?: [];

$minYear = isset($yearRange['min_year']) && $yearRange['min_year'] !== null ? (int) $yearRange['min_year'] : $currentYear;
$maxYear = isset($yearRange['max_year']) && $yearRange['max_year'] !== null ? (int) $yearRange['max_year'] : $currentYear;

if ($selectedYear < $minYear || $selectedYear > $maxYear) {
    $selectedYear = $currentYear;
}

$availableYears = range($maxYear, $minYear);

$reportStmt = $pdo->prepare(
    "SELECT
        t.id,
        t.title,
        t.amount,
        t.type,
        t.transaction_date,
        c.name AS category_name
     FROM transactions t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = :user_id
       AND MONTH(t.transaction_date) = :month
       AND YEAR(t.transaction_date) = :year
     ORDER BY t.transaction_date DESC, t.id DESC"
);
$reportStmt->execute([
    'user_id' => $userId,
    'month' => $selectedMonth,
    'year' => $selectedYear,
]);
$transactions = $reportStmt->fetchAll();

$totalIncome = 0.0;
$totalExpense = 0.0;

foreach ($transactions as $transaction) {
    $amount = (float) $transaction['amount'];

    if (($transaction['type'] ?? '') === 'income') {
        $totalIncome += $amount;
        continue;
    }

    if (($transaction['type'] ?? '') === 'expense') {
        $totalExpense += $amount;
    }
}

$net = $totalIncome - $totalExpense;

$monthOptions = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
];

$exportQuery = http_build_query([
    'month' => $selectedMonth,
    'year' => $selectedYear,
]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php
    $pageTitle = 'Reports — ' . APP_NAME;
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
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Laporan Keuangan</h1>
                        <p class="text-sm text-slate-500 mt-1">Ringkasan pemasukan dan pengeluaran per periode.</p>
                    </div>
                </div>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
                    <form method="GET" action="index.php" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                        <div>
                            <label for="month" class="block text-sm font-medium text-slate-700 mb-1">Bulan</label>
                            <select id="month" name="month" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <?php foreach ($monthOptions as $monthNumber => $monthLabel): ?>
                                    <option value="<?= htmlspecialchars((string) $monthNumber, ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedMonth === $monthNumber ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="year" class="block text-sm font-medium text-slate-700 mb-1">Tahun</label>
                            <select id="year" name="year" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <?php foreach ($availableYears as $yearOption): ?>
                                    <option value="<?= htmlspecialchars((string) $yearOption, ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedYear === $yearOption ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars((string) $yearOption, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flex gap-2 md:col-span-2">
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition">
                                Terapkan Filter
                            </button>
                            <a href="index.php" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition">
                                Reset
                            </a>
                        </div>
                    </form>
                </section>

                <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl border border-slate-200 p-4">
                        <p class="text-sm text-slate-500">Total Pemasukan</p>
                        <p class="text-2xl font-bold text-emerald-600">Rp <?= htmlspecialchars(number_format($totalIncome, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4">
                        <p class="text-sm text-slate-500">Total Pengeluaran</p>
                        <p class="text-2xl font-bold text-rose-600">Rp <?= htmlspecialchars(number_format($totalExpense, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4">
                        <p class="text-sm text-slate-500">Selisih (Net)</p>
                        <p class="text-2xl font-bold <?= $net >= 0 ? 'text-blue-600' : 'text-amber-600'; ?>">Rp <?= htmlspecialchars(number_format($net, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-200">
                        <h2 class="font-semibold text-slate-900">Detail Transaksi</h2>
                        <div class="flex gap-2">
                            <a href="export_csv.php?<?= htmlspecialchars($exportQuery, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                Export CSV
                            </a>
                            <a href="export_pdf.php?<?= htmlspecialchars($exportQuery, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                                Export PDF
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-100 text-slate-700">
                                <tr>
                                    <th class="text-left px-4 py-3 font-semibold">Tanggal</th>
                                    <th class="text-left px-4 py-3 font-semibold">Judul</th>
                                    <th class="text-left px-4 py-3 font-semibold">Kategori</th>
                                    <th class="text-left px-4 py-3 font-semibold">Tipe</th>
                                    <th class="text-right px-4 py-3 font-semibold">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                            <?php if ($transactions === []): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">Tidak ada transaksi pada periode ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string) $transaction['transaction_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3 text-slate-900 font-medium"><?= htmlspecialchars((string) $transaction['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string) ($transaction['category_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium <?= $transaction['type'] === 'income' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'; ?>">
                                                <?= htmlspecialchars((string) $transaction['type'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold <?= $transaction['type'] === 'income' ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                            Rp <?= htmlspecialchars(number_format((float) $transaction['amount'], 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
</html>