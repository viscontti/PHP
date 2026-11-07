<?php
// Практична робота №7, варіант 13 — Трекер особистих витрат
// Ліниве підключення PDO + лічильник SQL-запитів (крок 3) + обробка помилок у форматі API
declare(strict_types=1);

ini_set('display_errors', '0');

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Помилка сервера'], JSON_UNESCAPED_UNICODE);
});

// Крок 3. Лічильник SQL-запитів: обгортка над PDO рахує кожен query(), а власний клас
// PDOStatement — кожен execute() підготовленого запиту
final class QueryCounter
{
    public static int $count = 0;
}

final class CountingPDO extends PDO
{
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        QueryCounter::$count++;
        return $fetchMode === null
            ? parent::query($query)
            : parent::query($query, $fetchMode, ...$fetchModeArgs);
    }
}

final class CountingStatement extends PDOStatement
{
    protected function __construct()
    {
    }

    public function execute(?array $params = null): bool
    {
        QueryCounter::$count++;
        return parent::execute($params);
    }
}

// Ліниве підключення: з'єднання з MySQL відкривається лише при першому виклику db().
// Завдяки цьому відповідь із кешу (крок 6) взагалі не звертається до бази даних.
function db(): PDO
{
    static $pdo = null;

    return $pdo ??= new CountingPDO(
        'mysql:host=127.0.0.1;port=3306;dbname=practicum7;charset=utf8mb4',
        'root',
        'root',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STATEMENT_CLASS => [CountingStatement::class],
        ]
    );
}
