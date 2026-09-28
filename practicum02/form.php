<?php
// Практична робота №2, варіант 13 — Трекер особистих витрат
// Форма «Нова транзакція»: amount, category (select), date
declare(strict_types=1);

// Показувати всі помилки PHP (тільки на час розробки)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------------------------------------------------------------
// Довідник категорій: ключ — значення value у <select>, значення — підпис
// Сервер приймає лише ці ключі (захист від підробленого значення)
// ---------------------------------------------------------------
const CATEGORIES = [
    'food'      => 'Продукти',
    'utilities' => 'Комунальні послуги',
    'transport' => 'Транспорт',
    'cafe'      => 'Кафе',
    'tech'      => 'Техніка',
    'sport'     => 'Спорт',
    'other'     => 'Інше',
];

// Екранує спецсимволи HTML, щоб дані користувача не "ламали" розмітку (захист від XSS)
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// 1850.5 -> "1 850,50 грн" (як у практикумі №1)
function formatMoney(float $amount): string
{
    return number_format($amount, 2, ',', "\u{00A0}") . "\u{00A0}грн";
}

// Перевіряє, що рядок — реальна дата у форматі РРРР-ММ-ДД (2026-02-30 — не дата)
function isValidDate(string $date): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    [$year, $month, $day] = array_map('intval', explode('-', $date));

    return checkdate($month, $day, $year);
}

// ---------------------------------------------------------------
// Крок 3. Серверна обробка й валідація
// ---------------------------------------------------------------
$errors = [];
$isSubmitted = $_SERVER['REQUEST_METHOD'] === 'POST';

// Значення полів: з $_POST після відправки або порожні / сьогоднішня дата
$amount   = trim((string) ($_POST['amount'] ?? ''));
$category = trim((string) ($_POST['category'] ?? ''));
$date     = trim((string) ($_POST['date'] ?? date('Y-m-d')));

if ($isSubmitted) {
    // amount — обов'язкове число більше 0 (кома як десятковий роздільник теж допускається)
    $amountNormalized = str_replace(',', '.', $amount);
    if ($amount === '') {
        $errors['amount'] = 'Вкажіть суму транзакції';
    } elseif (!is_numeric($amountNormalized)) {
        $errors['amount'] = 'Сума має бути числом';
    } elseif ((float) $amountNormalized <= 0) {
        $errors['amount'] = 'Сума має бути більшою за 0';
    }

    // category — обов'язкова і лише з дозволеного списку
    if ($category === '') {
        $errors['category'] = 'Оберіть категорію';
    } elseif (!array_key_exists($category, CATEGORIES)) {
        $errors['category'] = 'Такої категорії немає у списку';
    }

    // date — обов'язкова і коректна календарна дата
    if ($date === '') {
        $errors['date'] = 'Вкажіть дату транзакції';
    } elseif (!isValidDate($date)) {
        $errors['date'] = 'Некоректна дата (очікується реальна дата у форматі РРРР-ММ-ДД)';
    }
}

// Форма пройшла перевірку — готуємо дані для підтвердження
$isAccepted = $isSubmitted && empty($errors);
if ($isAccepted) {
    $transaction = [
        'amount'   => (float) str_replace(',', '.', $amount),
        'category' => CATEGORIES[$category],
        'date'     => date('d.m.Y', strtotime($date)),
    ];

    // Очищаємо форму для наступного запису; категорію підставить localStorage
    $amount   = '';
    $category = '';
    $date     = date('Y-m-d');
}

// Допоміжна функція: CSS-клас для поля з помилкою
function fieldClass(array $errors, string $field): string
{
    return isset($errors[$field]) ? 'field has-error' : 'field';
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Нова транзакція — трекер витрат</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Unbounded:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="page-header">
        <h1>Нова транзакція</h1>
        <p>Трекер особистих витрат · додавання запису</p>
    </header>

    <main class="layout">
        <!-- Крок 2. HTML-форма з HTML5-атрибутами валідації -->
        <section class="card">
            <h2>Дані транзакції</h2>

            <?php if (!empty($errors)): ?>
                <!-- Крок 4. Загальне повідомлення, якщо сервер знайшов помилки -->
                <div class="alert alert-error" role="alert">
                    Сервер не прийняв форму: виправте <?= count($errors) ?>
                    <?= count($errors) === 1 ? 'помилку' : 'помилки' ?> нижче.
                </div>
            <?php endif; ?>

            <form id="transaction-form" method="post" action="form.php">
                <div class="<?= fieldClass($errors, 'amount') ?>">
                    <label for="amount">Сума, грн</label>
                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        min="0.01"
                        step="0.01"
                        required
                        placeholder="Наприклад, 245.50"
                        value="<?= e($amount) ?>"
                    >
                    <!-- сюди виводиться помилка сервера або JavaScript -->
                    <p class="error" data-error-for="amount"><?= e($errors['amount'] ?? '') ?></p>
                </div>

                <div class="<?= fieldClass($errors, 'category') ?>">
                    <label for="category">Категорія</label>
                    <select id="category" name="category" required>
                        <option value="">— оберіть категорію —</option>
                        <?php foreach (CATEGORIES as $key => $title): ?>
                            <option value="<?= e($key) ?>" <?= $key === $category ? 'selected' : '' ?>>
                                <?= e($title) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="error" data-error-for="category"><?= e($errors['category'] ?? '') ?></p>
                    <p class="hint" id="category-hint"></p>
                </div>

                <div class="<?= fieldClass($errors, 'date') ?>">
                    <label for="date">Дата</label>
                    <input
                        type="date"
                        id="date"
                        name="date"
                        required
                        value="<?= e($date) ?>"
                    >
                    <p class="error" data-error-for="date"><?= e($errors['date'] ?? '') ?></p>
                </div>

                <button type="submit" class="btn">Додати транзакцію</button>
            </form>
        </section>

        <!-- Крок 4. Підтвердження з прийнятими даними -->
        <aside class="receipt">
            <?php if ($isAccepted): ?>
                <h2>Транзакцію додано</h2>
                <dl>
                    <div class="receipt-line">
                        <dt>Категорія</dt>
                        <dd><?= e($transaction['category']) ?></dd>
                    </div>
                    <div class="receipt-line">
                        <dt>Дата</dt>
                        <dd><?= e($transaction['date']) ?></dd>
                    </div>
                </dl>
                <p class="receipt-total-label">Сума</p>
                <p class="receipt-total"><?= formatMoney($transaction['amount']) ?></p>
            <?php else: ?>
                <h2>Підказка</h2>
                <ul class="rules">
                    <li>Сума — число більше 0</li>
                    <li>Категорія — обов'язкова, зі списку</li>
                    <li>Дата — коректна календарна дата</li>
                </ul>
                <p class="receipt-note">
                    Остання обрана категорія запам'ятовується в localStorage
                    і підставляється за замовчуванням.
                </p>
            <?php endif; ?>
        </aside>
    </main>

    <footer class="page-footer">
        Практична робота №2 · Варіант 13 · PHP <?= PHP_VERSION ?>
    </footer>

    <!-- Кроки 5–6. Клієнтська валідація і localStorage -->
    <script src="script.js"></script>
</body>
</html>
