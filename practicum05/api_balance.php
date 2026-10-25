<?php
// Практична робота №5, варіант 13 — Трекер особистих витрат
// Поточний баланс: SUM(amount) по всій таблиці
declare(strict_types=1);

$pdo = require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$balance = (float) $pdo->query('SELECT SUM(amount) AS balance FROM transactions')->fetch()['balance'];

echo json_encode(['balance' => $balance]);
