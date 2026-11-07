<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Маршрутизатор для вбудованого сервера: php -S localhost:8008 router.php
// Вбудований сервер віддає будь-який файл із папки, тому відкриваємо лише точки входу й статику.
// Логи (logs/), SQL зі схемою й хешами паролів (sql/), службові PHP-файли і dot-файли — 404.
// На Apache те саме робить .htaccess.
declare(strict_types=1);

const PUBLIC_FILES = [
    '/index.php', '/login.php', '/logout.php', '/add.php', '/edit.php', '/delete.php', '/api.php',
    '/style.css', '/app.js',
];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/') {
    header('Location: /index.php');
    return true;
}
if (in_array($path, ['/style.css', '/app.js'], true)) {
    return false;   // статичний файл віддає сам вбудований сервер
}
if (in_array($path, PUBLIC_FILES, true)) {
    require __DIR__ . $path;
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Не знайдено';
return true;
