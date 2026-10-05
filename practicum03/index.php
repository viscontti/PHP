<?php
// Практична робота №3, варіант 13 — Трекер особистих витрат
// Крок 6. Головний файл: підключення класів і бібліотеки, виведення даних
declare(strict_types=1);

// Показувати всі помилки PHP (тільки на час розробки)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Підключення "бібліотек": кожен файл підключиться лише один раз
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/classes/Transaction.php';
require_once __DIR__ . '/classes/RecurringExpense.php';
require_once __DIR__ . '/classes/Wallet.php';

// ---------------------------------------------------------------
// Створення об'єктів базового і похідного класу та додавання в менеджер
// ---------------------------------------------------------------
$wallet = new Wallet();

$wallet->addTransaction(new Transaction(18000.00, 'Зарплата', '2026-09-01'));
$wallet->addTransaction(new Transaction(-245.50, 'Продукти', '2026-09-02'));
$wallet->addTransaction(new RecurringExpense(1850.00, 'Комунальні послуги', '2026-09-03', 'щомісяця'));
$wallet->addTransaction(new Transaction(-120.00, 'Транспорт', '2026-09-04'));
$wallet->addTransaction(new RecurringExpense(1000.00, 'Спорт', '2026-09-05', 'щомісяця'));
$wallet->addTransaction(new Transaction(-480.00, 'Кафе', '2026-09-08'));
$wallet->addTransaction(new Transaction(-610.30, 'Продукти', '2026-09-12'));
$wallet->addTransaction(new RecurringExpense(199.00, 'Підписки', '2026-09-15', 'щомісяця'));
$wallet->addTransaction(new Transaction(2500.00, 'Фріланс', '2026-09-20'));

// Категорії витрат для підсумку totalByCategory()
$categories = ['Продукти', 'Комунальні послуги', 'Транспорт', 'Спорт', 'Кафе', 'Підписки'];

$balance = $wallet->balance();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Гаманець — Практикум №3</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Unbounded:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="page-header">
        <h1>Гаманець</h1>
        <p>Трекер особистих витрат · <?= monthName('2026-09-01') ?> 2026</p>
    </header>

    <main class="layout">
        <section class="card">
            <h2>Транзакції</h2>
            <table class="tx-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Клас</th>
                        <th>Місяць</th>
                        <th class="num">Сума</th>
                        <th>getInfo()</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wallet->getTransactions() as $i => $transaction): ?>
                        <tr class="<?= $transaction instanceof RecurringExpense ? 'recurring' : '' ?>">
                            <td><?= $i + 1 ?></td>
                            <td><span class="badge"><?= get_class($transaction) ?></span></td>
                            <td><?= monthName($transaction->getDate()) ?></td>
                            <td class="num <?= $transaction->getAmount() > 0 ? 'plus' : 'minus' ?>">
                                <?= formatMoney($transaction->getAmount()) ?>
                            </td>
                            <td><?= e($transaction->getInfo()) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <aside class="receipt">
            <h2>Підсумок</h2>
            <p class="receipt-total-label">Баланс — balance()</p>
            <p class="receipt-total"><?= formatMoney($balance) ?></p>

            <p class="receipt-total-label section-label">Витрати за категоріями — totalByCategory()</p>
            <dl>
                <?php foreach ($categories as $category): ?>
                    <div class="receipt-line">
                        <dt><?= e($category) ?></dt>
                        <dd><?= formatMoney($wallet->totalByCategory($category)) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
            <p class="receipt-note">Рядки з класом RecurringExpense підсвічені — їхній getInfo() доповнює опис базового класу.</p>
        </aside>
    </main>

    <footer class="page-footer">
        Практична робота №3 · Варіант 13 · PHP <?= PHP_VERSION ?>
    </footer>
</body>
</html>
