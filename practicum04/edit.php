<?php
// Практична робота №4, варіант 13 — Трекер особистих витрат
// Крок 5. Редагування запису (SELECT ... WHERE id = :id, потім UPDATE)
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$pdo = require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$transaction = getTransaction($pdo, $id);

if ($transaction === null) {
    http_response_code(404);
    exit('Транзакцію не знайдено');
}

$hasError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = readTransactionForm();
    if ($data !== null) {
        updateTransaction($pdo, $id, $data['amount'], $data['category'], $data['date']);
        header('Location: index.php');
        exit;
    }
    $hasError = true;
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редагування — Практикум №4</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Unbounded:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="page-header">
        <h1>Транзакція #<?= $transaction['id'] ?></h1>
        <p><a href="index.php">← До списку</a></p>
    </header>

    <main class="layout layout-narrow">
        <!-- Поля заповнені поточними значеннями з бази -->
        <form method="post" class="card">
            <h2>Редагування</h2>
            <?php if ($hasError): ?>
                <p class="alert alert-error">Перевірте введені дані</p>
            <?php endif; ?>
            <div class="field">
                <label for="amount">Сума, грн</label>
                <input type="number" id="amount" name="amount" step="0.01" required value="<?= e((string) $transaction['amount']) ?>">
            </div>
            <div class="field">
                <label for="category">Категорія</label>
                <input type="text" id="category" name="category" required maxlength="100" value="<?= e($transaction['category']) ?>">
            </div>
            <div class="field">
                <label for="date">Дата</label>
                <input type="date" id="date" name="transaction_date" required value="<?= e($transaction['transaction_date']) ?>">
            </div>
            <button type="submit" class="btn">Зберегти</button>
        </form>
    </main>

    <footer class="page-footer">
        Практична робота №4 · Варіант 13 · PHP <?= PHP_VERSION ?>
    </footer>
</body>
</html>
