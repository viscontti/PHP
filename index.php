<?php
// Практична робота №1, варіант 13 — Трекер особистих витрат
declare(strict_types=1);

// Показувати всі помилки PHP (тільки на час розробки)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------------------------------------------------------------
// Крок 2. Масив даних: масив асоціативних масивів (транзакцій)
// Поля за варіантом: amount, category, date
// ---------------------------------------------------------------
$transactions = [
    ['amount' => 245.50,  'category' => 'Продукти',           'date' => '2026-09-01'],
    ['amount' => 1850.00, 'category' => 'Комунальні послуги', 'date' => '2026-09-03'],
    ['amount' => 120.00,  'category' => 'Транспорт',          'date' => '2026-09-04'],
    ['amount' => 3200.00, 'category' => 'Техніка',            'date' => '2026-09-06'],
    ['amount' => 480.00,  'category' => 'Кафе',               'date' => '2026-09-08'],
    ['amount' => 1000.00, 'category' => 'Спортзал',           'date' => '2026-09-10'],
];

// ---------------------------------------------------------------
// Крок 3. Функції форматування (типізовані параметри і результат)
// ---------------------------------------------------------------

// Екранує спецсимволи HTML, щоб дані не "ламали" розмітку
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// 1850.5 -> "1 850,50 грн"
// \u{00A0} — нерозривний пробіл, щоб "грн" не переносилось на новий рядок
function formatMoney(float $amount): string
{
    return number_format($amount, 2, ',', "\u{00A0}") . "\u{00A0}грн";
}

// "2026-09-03" -> "03.09.2026"
function formatDate(string $date): string
{
    return date('d.m.Y', strtotime($date));
}

// Короткий опис однієї транзакції для виводу на сторінку
function formatTransaction(array $transaction): string
{
    return $transaction['category'] . ', '
        . formatDate($transaction['date']) . ' — '
        . formatMoney($transaction['amount']);
}

// ---------------------------------------------------------------
// Крок 4. Умовна логіка: мітка «Велика витрата», якщо amount > 1000
// ---------------------------------------------------------------
function getExpenseLabel(float $amount): string
{
    if ($amount > 1000) {
        return 'Велика витрата';
    } else {
        return 'Звичайна';
    }
}

// ---------------------------------------------------------------
// Крок 6. Агрегатні показники
// ---------------------------------------------------------------

// Загальна сума витрат — рахуємо циклом foreach
$totalAmount = 0.0;
foreach ($transactions as $transaction) {
    $totalAmount += $transaction['amount'];
}
// Те саме одним рядком: array_sum(array_column($transactions, 'amount'));

// Додатково: кількість транзакцій і кількість великих витрат
$transactionsCount = count($transactions);
$bigExpenses = array_filter(
    $transactions,
    fn(array $t): bool => $t['amount'] > 1000
);
$bigExpensesCount = count($bigExpenses);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Трекер витрат — вересень 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Unbounded:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="page-header">
        <h1>Мої витрати</h1>
        <p>Вересень 2026 · записів: <?= $transactionsCount ?></p>
    </header>

    <main class="layout">
        <!-- Крок 5. Вивід усіх записів циклом foreach у HTML-таблицю -->
        <section class="ledger">
            <h2>Транзакції</h2>
            <table>
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Транзакція</th>
                        <th>Мітка</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $index => $transaction): ?>
                    <?php
                        $label = getExpenseLabel($transaction['amount']);
                        $isBig = $transaction['amount'] > 1000;
                    ?>
                    <tr class="<?= $isBig ? 'row-big' : '' ?>">
                        <td class="num"><?= $index + 1 ?></td>
                        <td><?= e(formatTransaction($transaction)) ?></td>
                        <td>
                            <span class="badge <?= $isBig ? 'badge-big' : 'badge-normal' ?>">
                                <?= e($label) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Крок 6. Окремий блок з агрегатним показником -->
        <aside class="receipt">
            <h2>Підсумок</h2>
            <dl>
                <div class="receipt-line">
                    <dt>Транзакцій</dt>
                    <dd><?= $transactionsCount ?></dd>
                </div>
                <div class="receipt-line">
                    <dt>Великих витрат</dt>
                    <dd><?= $bigExpensesCount ?></dd>
                </div>
            </dl>
            <p class="receipt-total-label">Загальна сума витрат</p>
            <p class="receipt-total"><?= formatMoney($totalAmount) ?></p>
        </aside>
    </main>

    <footer class="page-footer">
        Практична робота №1 · Варіант 13 · PHP <?= PHP_VERSION ?>
    </footer>
</body>
</html>