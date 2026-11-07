<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Спільна ініціалізація всіх сторінок: обробка помилок, заголовки безпеки, сесія, CSRF, авторизація
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

// ---------- Крок 6. Обробка помилок ----------

// Користувач ніколи не бачить текст помилки PHP чи PDOException — лише загальне повідомлення.
// Деталі (файл, рядок, SQLSTATE, стек) пишуться в лог через error_log().
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/php_errors.log');
error_reporting(E_ALL);

if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0700, true);
}

// Попередження (наприклад, масив замість рядка) — теж винятки, а не «тихий» запис сміття
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (defined('JSON_API')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Помилка сервера'], JSON_UNESCAPED_UNICODE);
    } else {
        renderMessagePage('Щось пішло не так', 'Спробуйте пізніше.');
    }
});

// ---------- Заголовки безпеки ----------

// CSP: скрипти лише з власного сайту (інлайн-скрипти й обробники onclick/onerror заборонені —
// додатковий захист від XSS, якщо десь забудемо екранування); сторінку не можна вбудувати у фрейм
header("Content-Security-Policy: default-src 'self'; script-src 'self'; "
    . "style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; "
    . "img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header_remove('X-Powered-By');   // не розкриваємо версію PHP
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

// ---------- Сесія ----------

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,   // cookie недоступна з JavaScript (document.cookie)
    'samesite' => 'Lax',  // не надсилається з POST-запитами з інших сайтів
    'secure' => false,    // на реальному сервері з HTTPS — true
]);
session_name('WALLETSESSID');
session_start();

// ---------- Крок 3. CSRF ----------

// Токен генерується в сесії при першій видачі форми (і заново після входу/виходу)
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

// Перевірка токена при обробці POST. Запит відхиляється з кодом 403, якщо:
//  - у сесії токена НЕМАЄ взагалі (інакше hash_equals('', '') === true і перевірка проходила б завжди);
//  - токен не надіслано або надіслано не рядком;
//  - токен не збігається з сесійним (наприклад, старий токен з попередньої сесії).
// Токен береться з поля форми csrf_token або із заголовка X-CSRF-Token (для fetch/API).
function isValidCsrf(): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? null;
    $sentToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }
    if (!is_string($sentToken) || $sentToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $sentToken);
}

function requireCsrf(): void
{
    if (!isValidCsrf()) {
        error_log(sprintf('CSRF: відхилено %s %s (user_id=%s)',
            $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SESSION['user_id'] ?? '-'));
        http_response_code(403);
        if (defined('JSON_API')) {
            echo json_encode(['success' => false, 'error' => 'Недійсний CSRF-токен'], JSON_UNESCAPED_UNICODE);
        } else {
            renderMessagePage('Запит відхилено', 'Недійсний CSRF-токен. Оновіть сторінку й спробуйте ще раз.');
        }
        exit;
    }
}

// Дії, що змінюють дані, — лише POST (крок 5)
function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        renderMessagePage('Метод не дозволено', 'Ця дія виконується лише через форму.');
        exit;
    }
}

// ---------- Авторизація ----------

function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function requireLogin(): int
{
    $userId = currentUserId();
    if ($userId === null) {
        if (defined('JSON_API')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Потрібна авторизація'], JSON_UNESCAPED_UNICODE);
        } else {
            header('Location: login.php');
        }
        exit;
    }
    return $userId;
}

function logIn(array $user): void
{
    session_regenerate_id(true);                         // захист від фіксації сесії
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // новий токен — старі стають недійсними
}

function logOut(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

// Проста сторінка з повідомленням (помилка, 403, 404, 405) — без технічних деталей
function renderMessagePage(string $title, string $text): void
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    ?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — Гаманець</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Unbounded:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="page-header">
        <h1>Гаманець</h1>
        <p>Трекер особистих витрат</p>
    </header>
    <main class="layout layout-narrow">
        <section class="card">
            <h2><?= e($title) ?></h2>
            <p><?= e($text) ?></p>
            <a href="index.php" class="btn btn-ghost">На головну</a>
        </section>
    </main>
</body>
</html>
    <?php
}
