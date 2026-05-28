<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$search = trim((string) ($_GET['search'] ?? ''));
$type = trim((string) ($_GET['type'] ?? ''));

if (!in_array($type, ['', 'income', 'expense'], true)) {
    $type = '';
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereClauses = ['t.user_id = :user_id'];
$params = ['user_id' => $userId];

if ($search !== '') {
    $whereClauses[] = '(t.title LIKE :search OR c.name LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($type !== '') {
    $whereClauses[] = 't.type = :type';
    $params['type'] = $type;
}

$whereSql = implode(' AND ', $whereClauses);

$countStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM transactions t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE {$whereSql}"
);
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$listStmt = $pdo->prepare(
    "SELECT t.title, t.amount, t.type, t.transaction_date, c.name AS category_name
     FROM transactions t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE {$whereSql}
     ORDER BY t.transaction_date DESC, t.id DESC
     LIMIT :limit OFFSET :offset"
);

foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $listStmt->bindValue(':' . $key, $value, $paramType);
}

$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$transactions = $listStmt->fetchAll();

$queryBase = [
    'search' => $search,
    'type' => $type,
];

$buildPageUrl = static function (int $targetPage) use ($queryBase): string {
    $query = array_filter(
        [
            'search' => $queryBase['search'],
            'type' => $queryBase['type'],
            'page' => $targetPage,
        ],
        static fn($value) => $value !== '' && $value !== null
    );

    return 'index.php' . (!empty($query) ? '?' . http_build_query($query) : '');
};
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
            <div class="max-w-6xl mx-auto space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Transactions</h1>
                        <p class="text-sm text-slate-500 mt-1">Kelola dan cari transaksi Anda.</p>
                    </div>
                    <a href="create.php" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        + Add New
                    </a>
                </div>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-slate-700 mb-1">Search transaksi</label>
                            <input
                                id="search"
                                name="search"
                                type="text"
                                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="Cari title / category"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            >
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Filter type</label>
                            <select id="type" name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                <option value="" <?= $type === '' ? 'selected' : ''; ?>>Semua</option>
                                <option value="income" <?= $type === 'income' ? 'selected' : ''; ?>>Income</option>
                                <option value="expense" <?= $type === 'expense' ? 'selected' : ''; ?>>Expense</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Apply</button>
                            <a href="index.php" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Reset</a>
                        </div>
                    </form>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th class="text-left px-4 py-3 font-semibold">Title</th>
                                <th class="text-left px-4 py-3 font-semibold">Amount</th>
                                <th class="text-left px-4 py-3 font-semibold">Category</th>
                                <th class="text-left px-4 py-3 font-semibold">Type</th>
                                <th class="text-left px-4 py-3 font-semibold">Transaction Date</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                            <?php if ($transactions === []): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">Tidak ada transaksi ditemukan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <?php $isIncome = $transaction['type'] === 'income'; ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars((string) $transaction['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3 <?= $isIncome ? 'text-emerald-700' : 'text-rose-700'; ?> font-semibold">
                                            <?= $isIncome ? '+' : '-'; ?>Rp <?= number_format((float) $transaction['amount'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string) ($transaction['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= $isIncome ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'; ?>">
                                                <?= htmlspecialchars(ucfirst((string) $transaction['type']), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string) $transaction['transaction_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm">
                        <p class="text-slate-600">Total: <?= $totalRows; ?> transaksi</p>
                        <div class="flex items-center gap-2">
                            <a href="<?= htmlspecialchars($buildPageUrl(max(1, $page - 1)), ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-100 <?= $page <= 1 ? 'pointer-events-none opacity-50' : ''; ?>">Prev</a>
                            <span class="text-slate-600">Page <?= $page; ?> / <?= $totalPages; ?></span>
                            <a href="<?= htmlspecialchars($buildPageUrl(min($totalPages, $page + 1)), ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-100 <?= $page >= $totalPages ? 'pointer-events-none opacity-50' : ''; ?>">Next</a>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
</html>
