<?php
// Практична робота №7, варіант 13 — Трекер особистих витрат
// Оптимізована версія API (заміри «після»). Відмінності від api_before.php:
//   крок 4 — індекс idx_transactions_category (sql/02_add_index_category.sql);
//   крок 5 — суми по категоріях одним запитом totalByCategory() з GROUP BY замість N+1;
//   крок 6 — balance() кешується у файлі на 60 с і явно інвалідується після додавання транзакції.
// Результати до/після — у README.md і папці results/.
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/cache.php';

const BALANCE_CACHE_KEY = 'balance';
const BALANCE_CACHE_TTL = 60; // секунд

// Крок 5. Суми по категоріях ОДНИМ запитом: ['Продукти' => -61234.5, ...]
function totalByCategory(?string $category = null): array
{
    if ($category === null) {
        $stmt = db()->query('SELECT category, SUM(amount) FROM transactions GROUP BY category');
    } else {
        $stmt = db()->prepare('SELECT category, SUM(amount) FROM transactions WHERE category = :category GROUP BY category');
        $stmt->execute([':category' => $category]);
    }
    return array_map('floatval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
}

// GET ?resource=transactions[&category=...] — список + сума по категорії кожного запису
function listTransactions(?string $category): never
{
    if ($category === null) {
        $rows = db()->query('SELECT * FROM transactions ORDER BY transaction_date, id')->fetchAll();
    } else {
        $stmt = db()->prepare('SELECT * FROM transactions WHERE category = :category ORDER BY transaction_date, id');
        $stmt->execute([':category' => $category]);
        $rows = $stmt->fetchAll();
    }

    // ШВИДКО: 1 запит на список + 1 агрегатний запит замість 1 + N
    $totals = totalByCategory($category);
    foreach ($rows as &$row) {
        $row = formatRow($row);
        $row['category_total'] = $totals[$row['category']];
    }
    unset($row);

    sendData($rows);
}

function getTransaction(int $id): never
{
    $stmt = db()->prepare('SELECT * FROM transactions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row === false) {
        sendError("Транзакцію з id $id не знайдено", 404);
    }
    sendData(formatRow($row));
}

function createTransaction(): never
{
    $created = insertTransaction(validateTransaction(readBody()));
    // Крок 6. Дані змінились — кешований баланс застарів, видаляємо його явно
    forget(BALANCE_CACHE_KEY);
    sendData($created, 201);
}

// Крок 6. Баланс береться з кешу; запит до БД — лише якщо кешу немає або минув TTL
function balance(): never
{
    $balance = remember(BALANCE_CACHE_KEY, BALANCE_CACHE_TTL,
        fn () => (float) db()->query('SELECT SUM(amount) FROM transactions')->fetchColumn());
    sendData(['balance' => $balance]);
}

[$method, $id, $action, $category] = readParams();

match (true) {
    $method === 'GET' && $action !== null => sendError('Дії виконуються лише методом POST', 405),
    $method === 'GET' && $id === null => listTransactions($category),
    $method === 'GET' => getTransaction($id),
    $method === 'POST' && $action === null => createTransaction(),
    $method === 'POST' && $action === 'balance' => balance(),
    $method === 'POST' => sendError("Невідома дія: $action", 400),
    default => methodNotAllowed(),
};
