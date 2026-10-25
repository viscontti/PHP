<?php
// Практична робота №4, варіант 13 — Трекер особистих витрат
// Крок 6. Видалення запису (DELETE через підготовлений запит)
declare(strict_types=1);

$pdo = require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    deleteTransaction($pdo, (int) ($_POST['id'] ?? 0));
}

header('Location: index.php');
exit;
