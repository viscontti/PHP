<?php
// Практична робота №5, варіант 13 — Трекер особистих витрат
// Крок 5. Ендпоінт додавання: POST -> INSERT -> JSON з доданим записом і кодом 201
declare(strict_types=1);

$pdo = require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Дозволено лише POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Дані форми, надіслані з fetch як FormData, читаються через $_POST.
// Поле може прийти масивом (amount[]=...) — тоді вважаємо його порожнім
function field(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? trim($value) : '';
}

$amount = str_replace(',', '.', field('amount'));
$category = field('category');
$date = field('transaction_date');

$dateOk = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);

// Колонка amount — DECIMAL(10,2): до 99 999 999,99 за модулем, 2 знаки після коми
$amount = is_numeric($amount) ? round((float) $amount, 2) : 0.0;
$amountOk = $amount != 0 && abs($amount) <= 99999999.99;

if (!$amountOk || $category === '' || mb_strlen($category) > 100 || !$dateOk) {
    http_response_code(422);
    echo json_encode(['error' => 'Перевірте введені дані'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO transactions (amount, category, transaction_date) VALUES (:amount, :category, :date)'
);
$stmt->execute([':amount' => $amount, ':category' => $category, ':date' => $date]);

http_response_code(201);
echo json_encode([
    'id' => (int) $pdo->lastInsertId(),
    'amount' => $amount,
    'category' => $category,
    'transaction_date' => $date,
], JSON_UNESCAPED_UNICODE);
