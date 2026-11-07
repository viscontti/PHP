<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Видалення: лише POST (GET -> 405) + авторизація + CSRF + перевірка власника в самому DELETE
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requirePost();
requireCsrf();             // CSRF перевіряється першим: без дійсного токена — 403 для будь-кого
$userId = requireLogin();

$id = parseId($_POST['id'] ?? null);
if ($id === null || deleteTransaction($userId, $id) === 0) {
    http_response_code(404);
    renderMessagePage('Не знайдено', 'Транзакцію не знайдено.');
    exit;
}

header('Location: index.php', true, 303);
exit;
