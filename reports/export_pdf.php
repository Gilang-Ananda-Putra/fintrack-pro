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

$periodLabel = sprintf('%04d-%02d', $year, $month);

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinTrack Pro Report <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { color: #0f172a; font-family: Arial, sans-serif; margin: 24px; }
        .toolbar { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 16px; }
        button { background: #0f172a; border: 0; border-radius: 8px; color: #fff; cursor: pointer; padding: 10px 14px; }
        h1 { font-size: 24px; margin: 0; }
        .muted { color: #64748b; margin-top: 4px; }
        .summary { display: grid; gap: 12px; grid-template-columns: repeat(3, 1fr); margin: 24px 0; }
        .card { border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px; }
        .label { color: #64748b; font-size: 12px; margin-bottom: 6px; }
        .value { font-size: 20px; font-weight: 700; }
        .income { color: #047857; }
        .expense { color: #be123c; }
        .net { color: #2563eb; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        th { background: #f1f5f9; }
        .text-right { text-align: right; }
        @media print {
            body { margin: 0; }
            .toolbar { display: none; }
            .card, table { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <header>
        <h1>FinTrack Pro Report</h1>
        <p class="muted">Periode: <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?></p>
    </header>

    <section class="summary">
        <div class="card">
            <div class="label">Total Income</div>
            <div class="value income">Rp <?= htmlspecialchars(number_format($totalIncome, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div class="card">
            <div class="label">Total Expense</div>
            <div class="value expense">Rp <?= htmlspecialchars(number_format($totalExpense, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div class="card">
            <div class="label">Net</div>
            <div class="value net">Rp <?= htmlspecialchars(number_format($totalIncome - $totalExpense, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </section>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th>Kategori</th>
                <th>Tipe</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
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
                        <td class="text-right">Rp <?= htmlspecialchars(number_format($amount, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
    window.addEventListener('load', () => {
        if (window.location.search.includes('autoprint=1')) {
            window.print();
        }
    });
    </script>
</body>
</html>