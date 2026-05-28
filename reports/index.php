<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 12],
]);
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 2000, 'max_range' => 2100],
]);

$currentMonth = (int) date('n');
$currentYear  = (int) date('Y');

$month = $month !== false && $month !== null ? (int) $month : $currentMonth;
$year  = $year !== false && $year !== null ? (int) $year : $currentYear;

$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate   = date('Y-m-d', strtotime($startDate . ' +1 month'));

$transactionStmt = $pdo->prepare(
    'SELECT
        t.id,
        t.title,
        t.amount,
        t.type,
        t.transaction_date,
        c.name AS category_name
     FROM transactions t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = :user_id
       AND t.transaction_date >= :start_date
       AND t.transaction_date < :end_date
     ORDER BY t.transaction_date DESC, t.id DESC'
);
$transactionStmt->execute([
    'user_id' => $userId,
    'start_date' => $startDate,
    'end_date' => $endDate,
]);
$transactions = $transactionStmt->fetchAll(PDO::FETCH_ASSOC);

$summaryStmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS total_expense
     FROM transactions t
     WHERE t.user_id = :user_id
       AND t.transaction_date >= :start_date
       AND t.transaction_date < :end_date"
);
$summaryStmt->execute([
    'user_id' => $userId,
    'start_date' => $startDate,
    'end_date' => $endDate,
]);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_income' => 0, 'total_expense' => 0];

$totalIncome  = (float) ($summary['total_income'] ?? 0);
$totalExpense = (float) ($summary['total_expense'] ?? 0);
$balance      = $totalIncome - $totalExpense;
$periodLabel  = date('F Y', strtotime($startDate));
$autoPrint    = isset($_GET['autoprint']) && $_GET['autoprint'] === '1';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Transaksi - <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        @page { margin: 14mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            margin: 0;
            line-height: 1.4;
            font-size: 13px;
        }
        .container { max-width: 980px; margin: 0 auto; }
        .no-print { margin: 12px 0 18px; }
        .btn-back {
            display: inline-block;
            text-decoration: none;
            color: #1d4ed8;
            border: 1px solid #93c5fd;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
        }
        h1 { margin: 0; font-size: 20px; }
        .period { margin: 4px 0 14px; color: #4b5563; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th, td {
            border: 1px solid #9ca3af;
            padding: 8px;
            vertical-align: top;
        }
        th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
        .summary {
            width: 340px;
            margin-left: auto;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="no-print">
        <a class="btn-back" href="../dashboard/index.php">&larr; Kembali</a>
    </div>

    <h1>Laporan Transaksi</h1>
    <p class="period">Periode: <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?></p>

    <table>
        <thead>
            <tr>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Tipe</th>
                <th>Tanggal</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($transactions === []): ?>
                <tr>
                    <td colspan="5">Tidak ada transaksi pada periode ini.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $trx): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $trx['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($trx['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $trx['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime((string) $trx['transaction_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-right"><?= htmlspecialchars(formatRupiah((float) $trx['amount']), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="summary">
        <tbody>
            <tr>
                <th>Total Income</th>
                <td class="text-right"><?= htmlspecialchars(formatRupiah($totalIncome), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
                <th>Total Expense</th>
                <td class="text-right"><?= htmlspecialchars(formatRupiah($totalExpense), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
                <th>Saldo</th>
                <td class="text-right"><?= htmlspecialchars(formatRupiah($balance), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<?php if ($autoPrint): ?>
<script>
window.addEventListener('load', function () {
    window.print();
});
</script>
<?php endif; ?>
</body>
</html>
