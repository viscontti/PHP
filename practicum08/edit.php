<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Редагування: запис шукається за id І власником; чужий або неіснуючий id -> 404
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/partials.php';

$userId = requireLogin();

$id = parseId($_GET['id'] ?? null);
$transaction = $id !== null ? getUserTransaction($userId, $id) : null;

if ($transaction === null) {
    // Однакова відповідь для чужого й неіснуючого запису — не розкриваємо, що такий id існує
    http_response_code(404);
    renderMessagePage('Не знайдено', 'Транзакцію не знайдено.');
    exit;
}

$errors = [];
$values = $transaction;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    [$data, $errors] = validateTransaction($_POST);
    if ($data !== null) {
        updateTransaction($userId, $id, $data);
        header('Location: index.php', true, 303);
        exit;
    }
    $values = [
        'amount' => inputString($_POST, 'amount') ?? '',
        'category' => inputString($_POST, 'category') ?? '',
        'transaction_date' => inputString($_POST, 'transaction_date') ?? '',
    ];
    http_response_code(422);
}

pageStart('Редагування — Гаманець');
?>
    <main class="layout layout-narrow">
        <form method="post" class="card">
            <h2>Транзакція #<?= (int) $transaction['id'] ?></h2>
            <?= csrfField() ?>
            <div class="field">
                <label for="amount">Сума, грн</label>
                <input type="number" id="amount" name="amount" step="0.01" required value="<?= e((string) $values['amount']) ?>">
                <?php if (isset($errors['amount'])): ?><p class="error"><?= e($errors['amount']) ?></p><?php endif; ?>
            </div>
            <div class="field">
                <label for="category">Категорія</label>
                <!-- Поточне значення в атрибуті value — теж через e() (ENT_QUOTES екранує лапки) -->
                <input type="text" id="category" name="category" required maxlength="100" value="<?= e((string) $values['category']) ?>">
                <?php if (isset($errors['category'])): ?><p class="error"><?= e($errors['category']) ?></p><?php endif; ?>
            </div>
            <div class="field">
                <label for="date">Дата</label>
                <input type="date" id="date" name="transaction_date" required value="<?= e((string) $values['transaction_date']) ?>">
                <?php if (isset($errors['transaction_date'])): ?><p class="error"><?= e($errors['transaction_date']) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn">Зберегти</button>
            <a href="index.php" class="btn btn-ghost">Скасувати</a>
        </form>
    </main>
<?php
pageEnd();
