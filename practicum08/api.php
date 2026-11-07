<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// API з практикуму №6 після аудиту безпеки:
//   - доступ лише для авторизованого користувача (сесія), інакше 401;
//   - GET ?id=N повертає лише власний запис (чужий -> 404);
//   - POST (створення, action=balance) — лише з CSRF-токеном у заголовку X-CSRF-Token, інакше 403;
//   - помилки сервера -> {"success": false, "error": "Помилка сервера"} без деталей.
declare(strict_types=1);

const JSON_API = true;
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function sendData(mixed $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    exit;
}

function sendError(string $message, int $status): never
{
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    exit;
}

function formatRow(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'amount' => (float) $row['amount'],
        'category' => $row['category'],
        'transaction_date' => $row['transaction_date'],
    ];
}

$userId = requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
if (($_GET['resource'] ?? null) !== 'transactions') {
    sendError('Невідомий ресурс', 404);
}

$idRaw = $_GET['id'] ?? null;
$id = parseId($idRaw);
if ($idRaw !== null && $id === null) {
    sendError('Параметр id має бути додатним цілим числом', 400);
}
$action = inputString($_GET, 'action');
$category = inputString($_GET, 'category');

if ($method === 'GET') {
    if ($action !== null) {
        sendError('Дії виконуються лише методом POST', 405);
    }
    if ($id !== null) {
        $row = getUserTransaction($userId, $id);
        $row !== null ? sendData(formatRow($row)) : sendError("Транзакцію з id $id не знайдено", 404);
    }
    sendData(array_map('formatRow', getUserTransactions($userId, $category === '' ? null : $category)));
}

if ($method === 'POST') {
    requireCsrf();

    if ($action === null) {
        $body = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
            ? json_decode(file_get_contents('php://input'), true)
            : $_POST;
        if (!is_array($body)) {
            sendError('Тіло запиту не є коректним JSON-об\'єктом', 400);
        }
        [$data, $errors] = validateTransaction($body);
        if ($data === null) {
            sendError(implode('; ', $errors), 400);
        }
        sendData(['id' => addTransaction($userId, $data)] + $data, 201);
    }
    if ($action === 'balance') {
        sendData(['balance' => balance($userId)]);
    }
    sendError('Невідома дія', 400);
}

header('Allow: GET, POST');
sendError('Метод не підтримується для цього ресурсу', 405);
