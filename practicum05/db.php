<?php
// Практична робота №5, варіант 13 — Трекер особистих витрат
// Підключення до бази даних через PDO (без змін з практикуму №4)
declare(strict_types=1);

// 127.0.0.1, а не localhost: MySQL працює в Docker, підключення йде по TCP
$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=practicum4;charset=utf8mb4';

$pdo = new PDO($dsn, 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // помилки SQL — винятки PDOException
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // рядки — асоціативні масиви
]);

return $pdo;
