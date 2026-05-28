<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

$totalIncome = 0.0;
$totalExpense = 0.0;
$currentBalance = 0.0;
$recentTransactions = [];

$summaryStmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense
     FROM transactions
     WHERE user_id = :user_id"
);
$summaryStmt->execute(['user_id' => $userId]);
$summary = $summaryStmt->fetch();

if (is_array($summary)) {
    $totalIncome = (float) ($summary['total_income'] ?? 0);
    $totalExpense = (float) ($summary['total_expense'] ?? 0);
}

$currentBalance = $totalIncome - $totalExpense;

$transactionStmt = $pdo->prepare(
    "SELECT
        t.title,
        t.amount,
        t.type,
        t.transaction_date,
        c.name AS category_name
     FROM transactions t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = :user_id
     ORDER BY t.transaction_date DESC, t.id DESC
     LIMIT 8"
);
$transactionStmt->execute(['user_id' => $userId]);
$recentTransactions = $transactionStmt->fetchAll();

$currency = static fn (float $amount): string => 'Rp ' . number_format($amount, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 min-w-0">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>

        <main class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Dashboard</h1>
                    <p class="text-sm text-slate-500 mt-1">Ringkasan keuangan berdasarkan data transaksi akun Anda.</p>
                </div>

                <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    <article class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">Total Income</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-600"><?= htmlspecialchars($currency($totalIncome), ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>

                    <article class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">Total Expense</p>
                        <p class="mt-2 text-2xl font-bold text-rose-600"><?= htmlspecialchars($currency($totalExpense), ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>

                    <article class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm sm:col-span-2 xl:col-span-1">
                        <p class="text-sm font-medium text-slate-500">Current Balance</p>
                        <p class="mt-2 text-2xl font-bold <?= $currentBalance >= 0 ? 'text-blue-600' : 'text-amber-600'; ?>">
                            <?= htmlspecialchars($currency($currentBalance), ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </article>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900">Recent Transactions</h2>
                        <a href="../transactions/index.php" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wide text-xs">
                            <tr>
                                <th class="text-left px-5 py-3">Title</th>
                                <th class="text-left px-5 py-3">Category</th>
                                <th class="text-left px-5 py-3">Date</th>
                                <th class="text-right px-5 py-3">Amount</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                            <?php if (count($recentTransactions) === 0): ?>
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-slate-500">Belum ada transaksi untuk ditampilkan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <?php
                                    $isIncome = ($transaction['type'] ?? '') === 'income';
                                    $amount = (float) ($transaction['amount'] ?? 0);
                                    $categoryName = $transaction['category_name'] ?? '-';
                                    $transactionDate = $transaction['transaction_date'] ?? '';
                                    ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-4 font-medium text-slate-900"><?= htmlspecialchars((string) ($transaction['title'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-5 py-4 text-slate-600"><?= htmlspecialchars((string) $categoryName, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-5 py-4 text-slate-600"><?= htmlspecialchars((string) $transactionDate, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-5 py-4 text-right font-semibold <?= $isIncome ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                            <?= $isIncome ? '+' : '-'; ?><?= htmlspecialchars($currency($amount), ENT_QUOTES, 'UTF-8'); ?>
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
