<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: ../auth/login.php');
    exit;
}

$errors = [];
$errorMessage = '';

$transactionId = trim((string) ($_GET['id'] ?? $_POST['id'] ?? ''));
if (!ctype_digit($transactionId)) {
    $_SESSION['error_message'] = 'ID transaksi tidak valid.';
    header('Location: index.php');
    exit;
}

$transactionIdInt = (int) $transactionId;

$categoryStmt = $pdo->prepare(
    'SELECT id, name, type FROM categories WHERE user_id = :user_id ORDER BY type ASC, name ASC'
);
$categoryStmt->execute(['user_id' => $userId]);
$categories = $categoryStmt->fetchAll();

$transactionStmt = $pdo->prepare(
    'SELECT id, title, amount, type, category_id, note, transaction_date
     FROM transactions
     WHERE id = :id AND user_id = :user_id
     LIMIT 1'
);
$transactionStmt->execute([
    'id' => $transactionIdInt,
    'user_id' => $userId,
]);
$transaction = $transactionStmt->fetch();

if ($transaction === false) {
    $_SESSION['error_message'] = 'Transaksi tidak ditemukan atau bukan milik Anda.';
    header('Location: index.php');
    exit;
}

$title = (string) $transaction['title'];
$amount = (string) $transaction['amount'];
$type = (string) $transaction['type'];
$categoryId = (string) $transaction['category_id'];
$note = (string) ($transaction['note'] ?? '');
$transactionDate = (string) $transaction['transaction_date'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    $title = trim((string) ($_POST['title'] ?? ''));
    $amount = trim((string) ($_POST['amount'] ?? ''));
    $type = trim((string) ($_POST['type'] ?? ''));
    $categoryId = trim((string) ($_POST['category_id'] ?? ''));
    $note = trim((string) ($_POST['note'] ?? ''));
    $transactionDate = trim((string) ($_POST['transaction_date'] ?? ''));

    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    }

    if ($title === '') {
        $errors[] = 'Title wajib diisi.';
    }

    if ($amount === '') {
        $errors[] = 'Amount wajib diisi.';
    } elseif (!is_numeric($amount) || (float) $amount <= 0) {
        $errors[] = 'Amount harus berupa angka dan lebih dari 0.';
    }

    if (!in_array($type, ['income', 'expense'], true)) {
        $errors[] = 'Type wajib dipilih (income/expense).';
    }

    if ($categoryId === '') {
        $errors[] = 'Category wajib dipilih.';
    } elseif (!ctype_digit($categoryId)) {
        $errors[] = 'Category tidak valid.';
    }

    if ($transactionDate === '') {
        $errors[] = 'Tanggal transaksi wajib diisi.';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $transactionDate);
        $dateErrors = DateTime::getLastErrors();
        if ($dateErrors === false) {
            $dateErrors = ['warning_count' => 0, 'error_count' => 0];
        }

        if (
            $date === false
            || $dateErrors['warning_count'] > 0
            || $dateErrors['error_count'] > 0
            || $date->format('Y-m-d') !== $transactionDate
        ) {
            $errors[] = 'Format tanggal transaksi tidak valid.';
        }
    }

    if ($errors === []) {
        $categoryCheckStmt = $pdo->prepare(
            'SELECT id FROM categories WHERE id = :id AND user_id = :user_id AND type = :type LIMIT 1'
        );
        $categoryCheckStmt->execute([
            'id' => (int) $categoryId,
            'user_id' => $userId,
            'type' => $type,
        ]);
        $categoryExists = $categoryCheckStmt->fetchColumn();

        if ($categoryExists === false) {
            $errors[] = 'Kategori tidak ditemukan, bukan milik Anda, atau tidak sesuai dengan tipe transaksi.';
        }
    }

    if ($errors === []) {
        $updateStmt = $pdo->prepare(
            'UPDATE transactions
             SET category_id = :category_id,
                 title = :title,
                 amount = :amount,
                 type = :type,
                 note = :note,
                 transaction_date = :transaction_date
             WHERE id = :id AND user_id = :user_id'
        );

        $updateStmt->execute([
            'category_id' => (int) $categoryId,
            'title' => $title,
            'amount' => (float) $amount,
            'type' => $type,
            'note' => $note !== '' ? $note : null,
            'transaction_date' => $transactionDate,
            'id' => $transactionIdInt,
            'user_id' => $userId,
        ]);

        $_SESSION['success_message'] = 'Transaksi berhasil diperbarui.';
        header('Location: index.php');
        exit;
    }

    $errorMessage = implode(' ', $errors);
}
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
            <div class="max-w-3xl mx-auto space-y-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Edit Transaksi</h1>
                    <p class="text-sm text-slate-500 mt-1">Perbarui data transaksi Anda.</p>
                </div>

                <?php if ($errorMessage !== ''): ?>
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-700 text-sm">
                        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <form method="POST" action="" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $transactionIdInt, ENT_QUOTES, 'UTF-8'); ?>">

                        <div>
                            <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Title</label>
                            <input id="title" name="title" type="text" value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="amount" class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
                                <input id="amount" name="amount" type="number" step="0.01" min="0" value="<?= htmlspecialchars($amount, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                            </div>

                            <div>
                                <label for="transaction_date" class="block text-sm font-medium text-slate-700 mb-1">Transaction Date</label>
                                <input id="transaction_date" name="transaction_date" type="date" value="<?= htmlspecialchars($transactionDate, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                                <select id="type" name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                    <option value="">Pilih type</option>
                                    <option value="income" <?= $type === 'income' ? 'selected' : ''; ?>>Income</option>
                                    <option value="expense" <?= $type === 'expense' ? 'selected' : ''; ?>>Expense</option>
                                </select>
                            </div>

                            <div>
                                <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                                <select id="category_id" name="category_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                    <option value="">Pilih category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars((string) $category['id'], ENT_QUOTES, 'UTF-8'); ?>" data-type="<?= htmlspecialchars((string) $category['type'], ENT_QUOTES, 'UTF-8'); ?>" <?= $categoryId === (string) $category['id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars((string) $category['type'], ENT_QUOTES, 'UTF-8'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="note" class="block text-sm font-medium text-slate-700 mb-1">Note</label>
                            <textarea id="note" name="note" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                            <a href="index.php" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</a>
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Update Transaksi</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('type');
    const categorySelect = document.getElementById('category_id');

    if (!typeSelect || !categorySelect) {
        return;
    }

    const filterCategoryOptions = () => {
        const selectedType = typeSelect.value;

        Array.from(categorySelect.options).forEach((option) => {
            const optionType = option.dataset.type;

            if (!optionType) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const shouldShow = optionType === selectedType;
            option.hidden = !shouldShow;
            option.disabled = !shouldShow;
        });

        const selectedCategory = categorySelect.selectedOptions[0];

        if (selectedCategory && selectedCategory.dataset.type && selectedCategory.dataset.type !== selectedType) {
            categorySelect.value = '';
        }
    };

    typeSelect.addEventListener('change', filterCategoryOptions);
    filterCategoryOptions();
});
</script>
</body>
</html>
