<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Головна: транзакції поточного користувача, фільтр за категорією, баланс, форма додавання
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/partials.php';

$userId = requireLogin();

// Крок 1. Фільтр за категорією — значення йде в підготовлений запит лише як параметр
$filter = inputString($_GET, 'category');
$filter = $filter === '' ? null : $filter;

$transactions = getUserTransactions($userId, $filter);
$balance = balance($userId);
$categoryTotal = $filter !== null ? totalByCategory($userId, $filter) : null;

$flash = takeFlash();
$errors = $flash['errors'];
$old = $flash['old'];

pageStart('Гаманець — Практикум №8');
?>
    <main class="layout">
        <section class="card">
            <h2>Транзакції</h2>

            <form method="get" class="inline-form filter">
                <div class="field">
                    <label for="category-filter">Категорія</label>
                    <input type="text" id="category-filter" name="category" maxlength="100" value="<?= e($filter ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-small">Показати</button>
                <?php if ($filter !== null): ?>
                    <a href="index.php" class="btn btn-small btn-ghost">Усі</a>
                <?php endif; ?>
            </form>

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
                            <td><?= (int) $row['id'] ?></td>
                            <td><?= e(formatDate($row['transaction_date'])) ?></td>
                            <!-- Крок 2. Категорію вводить користувач — тільки через e() -->
                            <td><?= e($row['category']) ?></td>
                            <td class="num <?= $row['amount'] > 0 ? 'plus' : 'minus' ?>">
                                <?= e(formatMoney((float) $row['amount'])) ?>
                            </td>
                            <td class="actions">
                                <a href="edit.php?id=<?= (int) $row['id'] ?>">Редагувати</a>
                                <!-- Крок 5. Видалення — POST + CSRF-токен + підтвердження (app.js) -->
                                <form action="delete.php" method="post" class="js-confirm"
                                      data-confirm="Видалити транзакцію #<?= (int) $row['id'] ?>?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <button type="submit" class="link-danger">Видалити</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($transactions === []): ?>
                        <tr><td colspan="5" class="empty">Нічого не знайдено</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <aside class="side">
            <div class="receipt">
                <h2>Баланс</h2>
                <p class="receipt-total"><?= e(formatMoney($balance)) ?></p>
                <?php if ($categoryTotal !== null): ?>
                    <dl>
                        <div class="receipt-line">
                            <dt><?= e($filter) ?></dt>
                            <dd><?= e(formatMoney($categoryTotal)) ?></dd>
                        </div>
                    </dl>
                <?php endif; ?>
            </div>

            <!-- Крок 3. Форма, що змінює дані, — з прихованим CSRF-токеном -->
            <form action="add.php" method="post" class="card">
                <h2>Нова транзакція</h2>
                <?= csrfField() ?>
                <div class="field">
                    <label for="amount">Сума, грн</label>
                    <input type="number" id="amount" name="amount" step="0.01" required value="<?= e($old['amount'] ?? '') ?>">
                    <?php if (isset($errors['amount'])): ?><p class="error"><?= e($errors['amount']) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label for="category">Категорія</label>
                    <input type="text" id="category" name="category" required maxlength="100" value="<?= e($old['category'] ?? '') ?>">
                    <?php if (isset($errors['category'])): ?><p class="error"><?= e($errors['category']) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label for="date">Дата</label>
                    <input type="date" id="date" name="transaction_date" required value="<?= e($old['transaction_date'] ?? date('Y-m-d')) ?>">
                    <?php if (isset($errors['transaction_date'])): ?><p class="error"><?= e($errors['transaction_date']) ?></p><?php endif; ?>
                </div>
                <button type="submit" class="btn">Додати</button>
            </form>
        </aside>
    </main>
<?php
pageEnd();
