<?php
// Практична робота №7, варіант 13 — Трекер особистих витрат
// Спільні функції для api_before.php і api.php: відповіді, профілювання, перевірка даних
declare(strict_types=1);

// Крок 1. Профілювання: час від початку обробки запиту
$GLOBALS['profileStart'] = microtime(true);

// Заміри додаються до кожної відповіді заголовками і пишуться в лог сервера
function sendProfile(): void
{
    $elapsed = (microtime(true) - $GLOBALS['profileStart']) * 1000;           // мс
    $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;                   // МБ, виділено системою (блоками по 2 МБ)
    $peakUsed = memory_get_peak_usage() / 1024;                                 // КБ, реально використано скриптом
    $queries = QueryCounter::$count;

    header(sprintf('X-Exec-Time-Ms: %.2f', $elapsed));
    header(sprintf('X-Peak-Memory-MB: %.2f', $peakMemory));
    header(sprintf('X-Peak-Memory-Used-KB: %.1f', $peakUsed));
    header('X-SQL-Queries: ' . $queries);
    error_log(sprintf('%s %s — час: %.2f мс, пам\'ять: %.2f МБ (використано %.1f КБ), SQL-запитів: %d',
        $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $elapsed, $peakMemory, $peakUsed, $queries));
}

function sendData(mixed $data, int $status = 200): never
{
    http_response_code($status);
    sendProfile();
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError(string $message, int $status): never
{
    http_response_code($status);
    sendProfile();
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function formatRow(array $row): array
{
    $row['id'] = (int) $row['id'];
    $row['amount'] = (float) $row['amount'];
    return $row;
}

// Тіло POST: JSON (php://input) або звичайна форма ($_POST)
function readBody(): array
{
    if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            sendError('Тіло запиту не є коректним JSON-об\'єктом', 400);
        }
        return $data;
    }
    return $_POST;
}

// Перевірка полів нової транзакції (як у практикумі №6); при помилці — 400 з назвою поля
function validateTransaction(array $body): array
{
    foreach (['amount', 'category', 'transaction_date'] as $field) {
        if (!isset($body[$field]) || (is_string($body[$field]) && trim($body[$field]) === '')) {
            sendError("Поле $field є обов'язковим", 400);
        }
        if (!is_string($body[$field]) && !is_int($body[$field]) && !is_float($body[$field])) {
            sendError("Поле $field має бути рядком або числом", 400);
        }
    }

    $amount = str_replace(',', '.', trim((string) $body['amount']));
    $amount = is_numeric($amount) ? round((float) $amount, 2) : 0.0;
    if ($amount == 0 || abs($amount) > 99999999.99) {
        sendError('Поле amount має бути ненульовим числом, не більшим за 99 999 999,99 за модулем', 400);
    }

    $category = trim((string) $body['category']);
    if (mb_strlen($category) > 100) {
        sendError('Поле category має містити не більше 100 символів', 400);
    }

    $date = trim((string) $body['transaction_date']);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        sendError('Поле transaction_date має бути датою у форматі YYYY-MM-DD', 400);
    }

    return ['amount' => $amount, 'category' => $category, 'transaction_date' => $date];
}

function insertTransaction(array $t): array
{
    $stmt = db()->prepare(
        'INSERT INTO transactions (amount, category, transaction_date) VALUES (:amount, :category, :date)'
    );
    $stmt->execute([':amount' => $t['amount'], ':category' => $t['category'], ':date' => $t['transaction_date']]);
    return ['id' => (int) db()->lastInsertId()] + $t;
}

// Параметри запиту, спільні для обох версій API
function readParams(): array
{
    $resource = $_GET['resource'] ?? null;
    $id = $_GET['id'] ?? null;
    $action = $_GET['action'] ?? null;
    $category = $_GET['category'] ?? null;

    if ($resource !== 'transactions') {
        sendError('Невідомий ресурс', 404);
    }
    if ($id !== null && (!is_string($id) || !ctype_digit($id) || (int) $id === 0)) {
        sendError('Параметр id має бути додатним цілим числом', 400);
    }
    if ($action !== null && !is_string($action)) {
        sendError('Параметр action має бути рядком', 400);
    }
    if ($category !== null && (!is_string($category) || trim($category) === '')) {
        sendError('Параметр category має бути непорожнім рядком', 400);
    }

    return [$_SERVER['REQUEST_METHOD'], $id === null ? null : (int) $id, $action, $category === null ? null : trim($category)];
}

function methodNotAllowed(): never
{
    header('Allow: GET, POST');
    sendError('Метод не підтримується для цього ресурсу', 405);
}
