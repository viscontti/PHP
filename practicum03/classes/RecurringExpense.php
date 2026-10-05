<?php
// Практична робота №3, варіант 13 — Трекер особистих витрат
// Крок 3. Похідний клас RecurringExpense (регулярна витрата): додає frequency
declare(strict_types=1);

class RecurringExpense extends Transaction
{
    private string $frequency;   // напр. "щомісяця", "щотижня"

    public function __construct(float $amount, string $category, string $date, string $frequency)
    {
        // Витрата завжди від'ємна, тому передаємо в батьківський конструктор -|amount|
        parent::__construct(-abs($amount), $category, $date);
        $this->frequency = $frequency;
    }

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    // Перевизначений метод: бере опис з батьківського класу і доповнює його
    public function getInfo(): string
    {
        return parent::getInfo() . " — регулярний платіж ({$this->frequency})";
    }
}
