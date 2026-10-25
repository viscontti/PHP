<?php
// Практична робота №5, варіант 13 — Трекер особистих витрат
// Крок 1. JSON-ендпоінт списку транзакцій (з фільтром за категорією ?q=...)
declare(strict_types=1);

$pdo = require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string) ($_GET['q'] ?? ''));

if ($q === '') {
    $rows = $pdo->query('SELECT * FROM transactions ORDER BY transaction_date, id')->fetchAll();
} else {
    // Пошук за частиною назви категорії — підготовлений запит (практикум №4)
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE category LIKE :q ORDER BY transaction_date, id');
    $stmt->execute([':q' => '%' . $q . '%']);
    $rows = $stmt->fetchAll();
}

// PDO повертає числа як рядки — приводимо типи, щоб у JSON були числа
foreach ($rows as &$row) {
    $row['id'] = (int) $row['id'];
    $row['amount'] = (float) $row['amount'];
}
unset($row);

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
