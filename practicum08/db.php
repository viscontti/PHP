<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Ліниве підключення PDO (з практикуму №7)
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    return $pdo ??= new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=practicum8;charset=utf8mb4',
        'root',
        'root',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,  // справжні підготовлені запити на боці MySQL
        ]
    );
}
