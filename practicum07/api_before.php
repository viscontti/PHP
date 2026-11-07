<?php
// Практична робота №7, варіант 13 — Трекер особистих витрат
// Версія API «ЯК Є» (базова лінія для замірів «до»): API з практикуму №6, у якому список
// для кожної транзакції додатково повертає суму по її категорії (category_total).
// Проблема N+1: сума рахується ОКРЕМИМ запитом для кожного рядка в циклі; баланс не кешується.
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib.php';

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

    // ПОВІЛЬНО: N+1 — окремий запит суми для кожної транзакції в циклі
    foreach ($rows as &$row) {
        $stmt = db()->prepare('SELECT SUM(amount) FROM transactions WHERE category = :category');
        $stmt->execute([':category' => $row['category']]);
        $row = formatRow($row);
        $row['category_total'] = (float) $stmt->fetchColumn();
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
    sendData(insertTransaction(validateTransaction(readBody())), 201);
}

// Баланс щоразу рахується запитом до БД
function balance(): never
{
    $balance = (float) db()->query('SELECT SUM(amount) FROM transactions')->fetchColumn();
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
