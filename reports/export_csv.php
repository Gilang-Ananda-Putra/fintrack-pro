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

$filename = sprintf('report-%04d-%02d.csv', $year, $month);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'wb');

if ($output === false) {
    http_response_code(500);
    exit;
}

fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, ['Tanggal', 'Deskripsi', 'Kategori', 'Tipe', 'Jumlah']);

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

    fputcsv($output, [
        (string) ($transaction['transaction_date'] ?? ''),
        (string) ($transaction['title'] ?? ''),
        (string) ($transaction['category_name'] ?? '-'),
        $type,
        number_format($amount, 2, '.', ''),
    ]);
}

$net = $totalIncome - $totalExpense;

fputcsv($output, []);
fputcsv($output, ['Ringkasan', '', '', '', '']);
fputcsv($output, ['Total Pemasukan', '', '', '', number_format($totalIncome, 2, '.', '')]);
fputcsv($output, ['Total Pengeluaran', '', '', '', number_format($totalExpense, 2, '.', '')]);
fputcsv($output, ['Net', '', '', '', number_format($net, 2, '.', '')]);

fclose($output);
exit;
