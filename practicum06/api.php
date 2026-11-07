<?php
// Практична робота №6, варіант 13 — Трекер особистих витрат
// Власний API: єдина точка входу з маршрутизацією за параметрами URL і методом HTTP
//
// Крок 1. Контракт API (докладніше — у файлі API.md)
//   GET  api.php?resource=transactions                 -> 200, список транзакцій
//   GET  api.php?resource=transactions&id=N            -> 200, одна транзакція | 404, якщо немає
//   POST api.php?resource=transactions                 -> 201, створена транзакція | 400, якщо поле відсутнє/некоректне
//        поля: amount (число, не 0), category (рядок до 100 символів), transaction_date (YYYY-MM-DD)
//   POST api.php?resource=transactions&action=balance  -> 200, {"balance": SUM(amount)}
//   Невідомий ресурс -> 404; непідтримуваний метод -> 405; помилка сервера -> 500
//   Формат відповіді: {"success": true, "data": ...} або {"success": false, "error": "..."}
declare(strict_types=1);

// Крок 2. Єдина точка входу: заголовок JSON одразу на початку
header('Content-Type: application/json; charset=utf-8');

$pdo = require_once __DIR__ . '/db.php';

// ---------- Відповіді в узгодженому форматі ----------

function sendData(mixed $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError(string $message, int $status): never
{
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// PDO повертає числа як рядки — приводимо типи, щоб у JSON були числа
function formatRow(array $row): array
{
    $row['id'] = (int) $row['id'];
    $row['amount'] = (float) $row['amount'];
    return $row;
}

// ---------- Обробники ----------

// Крок 4. GET — список
function listTransactions(PDO $pdo): never
{
    $rows = $pdo->query('SELECT * FROM transactions ORDER BY transaction_date, id')->fetchAll();
    sendData(array_map('formatRow', $rows));
}

// Крок 4. GET — один запис або 404
function getTransaction(PDO $pdo, int $id): never
{
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if ($row === false) {
        sendError("Транзакцію з id $id не знайдено", 404);
    }
    sendData(formatRow($row));
}

// Тіло POST: JSON (php://input) або звичайна форма ($_POST)
function readBody(): array
{
    if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            sendError('Тіло запиту не є коректним JSON-об\'єктом', 400);
        }
        return $data;
    }
    return $_POST;
}

// Крок 5. POST — створення запису
function createTransaction(PDO $pdo): never
{
    $body = readBody();

    // Обов'язкові поля: відсутнє або порожнє -> 400 з назвою поля
    foreach (['amount', 'category', 'transaction_date'] as $field) {
        if (!isset($body[$field]) || (is_string($body[$field]) && trim($body[$field]) === '')) {
            sendError("Поле $field є обов'язковим", 400);
        }
        // Масив/об'єкт/логічне значення замість рядка чи числа (amount[]=..., {"amount": [1]})
        if (!is_string($body[$field]) && !is_int($body[$field]) && !is_float($body[$field])) {
            sendError("Поле $field має бути рядком або числом", 400);
        }
    }

    // Сума: число, не 0, у межах колонки DECIMAL(10,2)
    $amount = str_replace(',', '.', trim((string) $body['amount']));
    $amount = is_numeric($amount) ? round((float) $amount, 2) : 0.0;
    if ($amount == 0 || abs($amount) > 99999999.99) {
        sendError('Поле amount має бути ненульовим числом, не більшим за 99 999 999,99 за модулем', 400);
    }

    $category = trim((string) $body['category']);
    if (mb_strlen($category) > 100) {
        sendError('Поле category має містити не більше 100 символів', 400);
    }

    $date = trim((string) $body['transaction_date']);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        sendError('Поле transaction_date має бути датою у форматі YYYY-MM-DD', 400);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO transactions (amount, category, transaction_date) VALUES (:amount, :category, :date)'
    );
    $stmt->execute([':amount' => $amount, ':category' => $category, ':date' => $date]);

    sendData([
        'id' => (int) $pdo->lastInsertId(),
        'amount' => $amount,
        'category' => $category,
        'transaction_date' => $date,
    ], 201);
}

// Крок 6. Доменна дія варіанта 13 — поточний баланс
function balance(PDO $pdo): never
{
    $balance = (float) $pdo->query('SELECT SUM(amount) AS balance FROM transactions')->fetch()['balance'];
    sendData(['balance' => $balance]);
}

// ---------- Крок 3. Маршрутизація ----------

$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? null;
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($resource !== 'transactions') {
    sendError('Невідомий ресурс', 404);
}

// Крок 7. id має бути додатним цілим числом
if ($id !== null && (!is_string($id) || !ctype_digit($id) || (int) $id === 0)) {
    sendError('Параметр id має бути додатним цілим числом', 400);
}
if ($action !== null && !is_string($action)) {
    sendError('Параметр action має бути рядком', 400);
}

match (true) {
    $method === 'GET' && $action !== null => sendError('Дії виконуються лише методом POST', 405),
    $method === 'GET' && $id === null => listTransactions($pdo),
    $method === 'GET' => getTransaction($pdo, (int) $id),
    $method === 'POST' && $action === null => createTransaction($pdo),
    $method === 'POST' && $action === 'balance' => balance($pdo),
    $method === 'POST' => sendError("Невідома дія: $action", 400),
    default => (function (): never {
        // Крок 7. Метод, який ресурс не підтримує
        header('Allow: GET, POST');
        sendError('Метод не підтримується для цього ресурсу', 405);
    })(),
};
