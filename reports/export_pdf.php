<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 12],
]);
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);

$currentYear = (int) date('Y');

if ($userId <= 0) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'User tidak valid.';
    exit;
}

if ($month === false || $month === null || $year === false || $year === null || $year < 2000 || $year > ($currentYear + 1)) {
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

$net = $totalIncome - $totalExpense;

$monthNames = [
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

$periodLabel = sprintf('%s %04d', $monthNames[$month], $year);
$generatedAt = date('d/m/Y H:i');

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?> — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        :root {
            color-scheme: light;
            --border: #d7dde8;
            --muted: #64748b;
            --text: #0f172a;
            --income: #047857;
            --expense: #be123c;
            --net: #1d4ed8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f8fafc;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        .page {
            width: min(100%, 960px);
            margin: 24px auto;
            padding: 32px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 20px;
        }

        .button {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            cursor: pointer;
            display: inline-block;
            font-weight: 700;
            padding: 9px 14px;
            text-decoration: none;
        }

        .button.secondary {
            background: #ffffff;
            color: #334155;
        }

        header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 20px;
        }

        h1 {
            font-size: 28px;
            margin: 0 0 6px;
        }

        .meta {
            color: var(--muted);
            margin: 0;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 24px 0;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
        }

        .card p {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin: 0 0 8px;
            text-transform: uppercase;
        }

        .card strong {
            display: block;
            font-size: 20px;
        }

        .income {
            color: var(--income);
        }

        .expense {
            color: var(--expense);
        }

        .net {
            color: var(--net);
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid var(--border);
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
            color: #334155;
            font-size: 12px;
            text-transform: uppercase;
        }

        .amount {
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        .empty {
            color: var(--muted);
            padding: 24px 8px;
            text-align: center;
        }

        footer {
            color: var(--muted);
            font-size: 12px;
            margin-top: 24px;
            text-align: right;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .page {
                border: 0;
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
            }

            .toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="toolbar" aria-label="Aksi laporan">
            <a class="button secondary" href="index.php?month=<?= htmlspecialchars((string) $month, ENT_QUOTES, 'UTF-8'); ?>&amp;year=<?= htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8'); ?>">Kembali</a>
            <button class="button" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        </div>

        <header>
            <div>
                <h1>Laporan Keuangan</h1>
                <p class="meta">Periode <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <p class="meta">Dibuat: <?= htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8'); ?></p>
        </header>

        <section class="summary" aria-label="Ringkasan laporan">
            <div class="card">
                <p>Total Pemasukan</p>
                <strong class="income">Rp <?= htmlspecialchars(number_format($totalIncome, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div class="card">
                <p>Total Pengeluaran</p>
                <strong class="expense">Rp <?= htmlspecialchars(number_format($totalExpense, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div class="card">
                <p>Net</p>
                <strong class="net">Rp <?= htmlspecialchars(number_format($net, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Kategori</th>
                    <th>Tipe</th>
                    <th class="amount">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions === []): ?>
                    <tr>
                        <td class="empty" colspan="5">Tidak ada transaksi pada periode ini.</td>
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
                            <td><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="amount <?= $type === 'income' ? 'income' : 'expense'; ?>">Rp <?= htmlspecialchars(number_format($amount, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <footer>
            <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?> — Halaman HTML print-friendly untuk cetak atau simpan sebagai PDF.
        </footer>
    </main>
</body>
