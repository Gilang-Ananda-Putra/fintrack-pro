<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$month = (int) ($_GET['month'] ?? 0);
$year = (int) ($_GET['year'] ?? 0);
$currentYear = (int) date('Y');

if ($month < 1 || $month > 12 || $year < 2000 || $year > ($currentYear + 1)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Parameter month/year tidak valid.';
    exit;
}

$statement = $pdo->prepare(
    "SELECT
        t.transaction_date,
        t.title,
        COALESCE(c.name, '-') AS category_name,
        t.type,
        t.amount
     FROM transactions t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = :user_id
       AND MONTH(t.transaction_date) = :month
       AND YEAR(t.transaction_date) = :year
     ORDER BY t.transaction_date ASC, t.id ASC"
);

$statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
$statement->bindValue(':month', $month, PDO::PARAM_INT);
$statement->bindValue(':year', $year, PDO::PARAM_INT);
$statement->execute();
$transactions = $statement->fetchAll(PDO::FETCH_ASSOC);

$totalIncome = 0.0;
$totalExpense = 0.0;

foreach ($transactions as $transaction) {
    $type = (string) ($transaction['type'] ?? '');
    $amount = (float) ($transaction['amount'] ?? 0);

    if ($type === 'income') {
        $totalIncome += $amount;
    } elseif ($type === 'expense') {
        $totalExpense += $amount;
    }
}

$filename = sprintf('report-%04d-%02d.xls', $year, $month);

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Report <?= htmlspecialchars(sprintf('%04d-%02d', $year, $month), ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #999; padding: 6px; }
        th { background: #e5e7eb; font-weight: bold; }
        .text-right { text-align: right; }
        .income { color: #047857; }
        .expense { color: #be123c; }
    </style>
</head>
<body>
<table>
    <tr>
        <th colspan="5">FinTrack Pro Report <?= htmlspecialchars(sprintf('%04d-%02d', $year, $month), ENT_QUOTES, 'UTF-8'); ?></th>
    </tr>
    <tr>
        <th>Tanggal</th>
        <th>Deskripsi</th>
        <th>Kategori</th>
        <th>Tipe</th>
        <th>Jumlah</th>
    </tr>
    <?php if ($transactions === []): ?>
        <tr>
            <td colspan="5">Tidak ada transaksi pada periode ini.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($transactions as $transaction): ?>
            <?php
            $type = (string) ($transaction['type'] ?? '');
            $amount = (float) ($transaction['amount'] ?? 0);
            ?>
            <tr>
                <td><?= htmlspecialchars((string) ($transaction['transaction_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($transaction['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($transaction['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="<?= $type === 'income' ? 'income' : 'expense'; ?>"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="text-right"><?= htmlspecialchars(number_format($amount, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    <tr>
        <th colspan="4" class="text-right">Total Income</th>
        <th class="text-right"><?= htmlspecialchars(number_format($totalIncome, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?></th>
    </tr>
    <tr>
        <th colspan="4" class="text-right">Total Expense</th>
        <th class="text-right"><?= htmlspecialchars(number_format($totalExpense, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?></th>
    </tr>
    <tr>
        <th colspan="4" class="text-right">Net</th>
        <th class="text-right"><?= htmlspecialchars(number_format($totalIncome - $totalExpense, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?></th>
    </tr>
</table>
</body>
</html>