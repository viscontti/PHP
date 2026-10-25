<?php
// Практична робота №4, варіант 13 — Трекер особистих витрат
// Крок 4. Додавання запису (INSERT через підготовлений запит)
declare(strict_types=1);

$pdo = require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$data = readTransactionForm();
if ($data === null) {
    header('Location: index.php?error=1');
    exit;
}

addTransaction($pdo, $data['amount'], $data['category'], $data['date']);

header('Location: index.php');
exit;
