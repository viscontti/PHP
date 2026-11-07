<?php
// Практична робота №6, варіант 13 — Трекер особистих витрат
// Підключення до бази даних через PDO + обробка помилок у форматі відповіді API
declare(strict_types=1);

// Помилки не виводяться у відповідь (там був би HTML зі шляхами і стеком)
ini_set('display_errors', '0');

// Попередження (Warning, Notice) перетворюємо на винятки
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Будь-який неперехоплений виняток (БД недоступна, помилка SQL) -> 500 у форматі API
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);  // деталі — лише в лог сервера
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Помилка сервера'], JSON_UNESCAPED_UNICODE);
});

// 127.0.0.1, а не localhost: MySQL працює в Docker, підключення йде по TCP
$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=practicum4;charset=utf8mb4';

$pdo = new PDO($dsn, 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // помилки SQL — винятки PDOException
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // рядки — асоціативні масиви
]);

return $pdo;
