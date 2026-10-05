<?php
// Практична робота №3, варіант 13 — Трекер особистих витрат
// Крок 5. Бібліотека допоміжних функцій
declare(strict_types=1);

// Екранує спецсимволи HTML, щоб дані не "ламали" розмітку
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// -245.5 -> "-245,50 грн"
// \u{00A0} — нерозривний пробіл, щоб "грн" не переносилось на новий рядок
function formatMoney(float $amount): string
{
    return number_format($amount, 2, ',', "\u{00A0}") . "\u{00A0}грн";
}

// "2026-09-03" -> "Вересень"
function monthName(string $date): string
{
    $months = [
        1 => 'Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень',
        'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень',
    ];
    $month = (int) date('n', strtotime($date));

    return $months[$month];
}
