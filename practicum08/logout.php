<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Вихід — лише POST із CSRF-токеном (інакше стороння сторінка могла б «розлогінити» користувача)
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requirePost();
requireCsrf();
logOut();

header('Location: login.php');
exit;
