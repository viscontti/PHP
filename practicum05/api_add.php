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

// Дані форми, надіслані з fetch як FormData, читаються через $_POST
$amount = str_replace(',', '.', trim((string) ($_POST['amount'] ?? '')));
$category = trim((string) ($_POST['category'] ?? ''));
$date = trim((string) ($_POST['transaction_date'] ?? ''));

$dateOk = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);

if (!is_numeric($amount) || (float) $amount == 0 || $category === '' || mb_strlen($category) > 100 || !$dateOk) {
    http_response_code(422);
    echo json_encode(['error' => 'Перевірте введені дані'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO transactions (amount, category, transaction_date) VALUES (:amount, :category, :date)'
);
$stmt->execute([':amount' => (float) $amount, ':category' => $category, ':date' => $date]);

http_response_code(201);
echo json_encode([
    'id' => (int) $pdo->lastInsertId(),
    'amount' => (float) $amount,
    'category' => $category,
    'transaction_date' => $date,
], JSON_UNESCAPED_UNICODE);
