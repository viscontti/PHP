<?php
// Практична робота №3, варіант 13 — Трекер особистих витрат
// Крок 4. Клас-менеджер Wallet: зберігає колекцію транзакцій
declare(strict_types=1);

class Wallet
{
    // Масив об'єктів Transaction і RecurringExpense
    private array $transactions = [];

    // Додати транзакцію (RecurringExpense теж підходить, бо він є Transaction)
    public function addTransaction(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;
    }

    public function getTransactions(): array
    {
        return $this->transactions;
    }

    // Сума витрат за категорією (додатне число)
    public function totalByCategory(string $category): float
    {
        $total = 0.0;
        foreach ($this->transactions as $transaction) {
            if ($transaction->getCategory() === $category && $transaction->getAmount() < 0) {
                $total += abs($transaction->getAmount());
            }
        }

        return $total;
    }

    // Баланс гаманця: доходи (+) плюс витрати (-)
    public function balance(): float
    {
        $balance = 0.0;
        foreach ($this->transactions as $transaction) {
            $balance += $transaction->getAmount();
        }

        return $balance;
    }
}
