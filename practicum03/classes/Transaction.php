<?php
// Практична робота №3, варіант 13 — Трекер особистих витрат
// Крок 2. Базовий клас Transaction: amount, category, date
declare(strict_types=1);

class Transaction
{
    // protected — доступні в цьому класі та в нащадках (RecurringExpense), але не ззовні
    protected float $amount;     // > 0 — дохід, < 0 — витрата
    protected string $category;
    protected string $date;      // формат РРРР-ММ-ДД

    public function __construct(float $amount, string $category, string $date)
    {
        $this->amount = $amount;
        $this->category = $category;
        $this->date = $date;
    }

    // Геттери — читати властивості ззовні можна, змінювати напряму не можна
    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    // Текстовий опис транзакції, напр. "Витрата: Продукти, -245,50 грн, 01.09.2026"
    public function getInfo(): string
    {
        $type = $this->amount > 0 ? 'Дохід' : 'Витрата';

        return "{$type}: {$this->category}, " . formatMoney($this->amount)
            . ', ' . date('d.m.Y', strtotime($this->date));
    }
}
