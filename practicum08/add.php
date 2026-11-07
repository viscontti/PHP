<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Додавання транзакції: POST -> авторизація -> CSRF -> серверна валідація -> INSERT
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requirePost();
requireCsrf();             // CSRF перевіряється першим: без дійсного токена — 403 для будь-кого
$userId = requireLogin();

[$data, $errors] = validateTransaction($_POST);

if ($data === null) {
    // Повертаємо помилки й введені значення у форму (через сесію)
    $_SESSION['flash'] = [
        'errors' => $errors,
        'old' => [
            'amount' => inputString($_POST, 'amount') ?? '',
            'category' => inputString($_POST, 'category') ?? '',
            'transaction_date' => inputString($_POST, 'transaction_date') ?? '',
        ],
    ];
    header('Location: index.php', true, 303);
    exit;
}

addTransaction($userId, $data);

header('Location: index.php', true, 303);
exit;
