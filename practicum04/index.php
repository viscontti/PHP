<?php
// Практична робота №4, варіант 13 — Трекер особистих витрат
// Крок 3. Виведення всіх записів (SELECT) + форма додавання + підсумки
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$pdo = require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$transactions = getAllTransactions($pdo);
$balance = balance($pdo);

// Сума за категорією — категорія приходить від користувача ($_GET), запит підготовлений
$category = trim((string) ($_GET['category'] ?? ''));
$categoryTotal = $category !== '' ? totalByCategory($pdo, $category) : null;

$hasError = isset($_GET['error']);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Гаманець — Практикум №4</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Unbounded:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="page-header">
        <h1>Гаманець</h1>
        <p>Трекер особистих витрат</p>
    </header>

    <main class="layout">
        <section class="card">
            <h2>Транзакції</h2>
            <table class="tx-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Категорія</th>
                        <th class="num">Сума</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $row): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= date('d.m.Y', strtotime($row['transaction_date'])) ?></td>
                            <td><?= e($row['category']) ?></td>
                            <td class="num <?= $row['amount'] > 0 ? 'plus' : 'minus' ?>">
                                <?= formatMoney((float) $row['amount']) ?>
                            </td>
                            <td class="actions">
                                <a href="edit.php?id=<?= $row['id'] ?>">Редагувати</a>
                                <!-- Крок 6. Видалення з підтвердженням через confirm() -->
                                <form action="delete.php" method="post" onsubmit="return confirm('Видалити транзакцію #<?= $row['id'] ?>?');">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="link-danger">Видалити</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <aside class="side">
            <div class="receipt">
                <h2>Баланс</h2>
                <p class="receipt-total"><?= formatMoney($balance) ?></p>

                <form method="get" class="inline-form">
                    <div class="field">
                        <label for="category-filter">Сума за категорією</label>
                        <input type="text" id="category-filter" name="category" value="<?= e($category) ?>" placeholder="Продукти">
                    </div>
                    <button type="submit" class="btn btn-small">Порахувати</button>
                </form>
                <?php if ($categoryTotal !== null): ?>
                    <dl>
                        <div class="receipt-line">
                            <dt><?= e($category) ?></dt>
                            <dd><?= formatMoney($categoryTotal) ?></dd>
                        </div>
                    </dl>
                <?php endif; ?>
            </div>

            <!-- Крок 4. Форма додавання (INSERT виконує add.php) -->
            <form action="add.php" method="post" class="card">
                <h2>Нова транзакція</h2>
                <?php if ($hasError): ?>
                    <p class="alert alert-error">Перевірте введені дані</p>
                <?php endif; ?>
                <div class="field">
                    <label for="amount">Сума, грн</label>
                    <input type="number" id="amount" name="amount" step="0.01" required placeholder="-245.50">
                </div>
                <div class="field">
                    <label for="category">Категорія</label>
                    <input type="text" id="category" name="category" required maxlength="100">
                </div>
                <div class="field">
                    <label for="date">Дата</label>
                    <input type="date" id="date" name="transaction_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <button type="submit" class="btn">Додати</button>
            </form>
        </aside>
    </main>

    <footer class="page-footer">
        Практична робота №4 · Варіант 13 · PHP <?= PHP_VERSION ?>
    </footer>
</body>
</html>
