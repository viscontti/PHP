<?php
// Практична робота №4, варіант 13 — Трекер особистих витрат
// Функції роботи з таблицею transactions (усі запити з параметрами — підготовлені)
declare(strict_types=1);

// Екранує спецсимволи HTML, щоб дані не "ламали" розмітку
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// -245.5 -> "-245,50 грн"
function formatMoney(float $amount): string
{
    return number_format($amount, 2, ',', "\u{00A0}") . "\u{00A0}грн";
}

// Читає й перевіряє поля форми з $_POST; повертає null, якщо дані некоректні
function readTransactionForm(): ?array
{
    $amount = str_replace(',', '.', trim((string) ($_POST['amount'] ?? '')));
    $category = trim((string) ($_POST['category'] ?? ''));
    $date = trim((string) ($_POST['transaction_date'] ?? ''));

    $dateOk = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);

    if (!is_numeric($amount) || (float) $amount == 0 || $category === '' || !$dateOk) {
        return null;
    }

    return ['amount' => (float) $amount, 'category' => $category, 'date' => $date];
}

// ---------- Вибірка ----------

// Усі записи (запит без даних користувача — достатньо query())
function getAllTransactions(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM transactions ORDER BY transaction_date, id')->fetchAll();
}

// Один запис за id (для форми редагування)
function getTransaction(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

function totalByCategory(PDO $pdo, string $category): float
{
    $stmt = $pdo->prepare('SELECT SUM(amount) AS total FROM transactions WHERE category = :category');
    $stmt->execute([':category' => $category]);

    return (float) $stmt->fetch()['total'];
}

function balance(PDO $pdo): float
{
    return (float) $pdo->query('SELECT SUM(amount) AS balance FROM transactions')->fetch()['balance'];
}

// ---------- Зміна даних ----------

function addTransaction(PDO $pdo, float $amount, string $category, string $date): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO transactions (amount, category, transaction_date) VALUES (:amount, :category, :date)'
    );
    $stmt->execute([':amount' => $amount, ':category' => $category, ':date' => $date]);

    return (int) $pdo->lastInsertId();
}

function updateTransaction(PDO $pdo, int $id, float $amount, string $category, string $date): int
{
    $stmt = $pdo->prepare(
        'UPDATE transactions SET amount = :amount, category = :category, transaction_date = :date WHERE id = :id'
    );
    $stmt->execute([':amount' => $amount, ':category' => $category, ':date' => $date, ':id' => $id]);

    return $stmt->rowCount();
}

function deleteTransaction(PDO $pdo, int $id): int
{
    $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = :id');
    $stmt->execute([':id' => $id]);

    return $stmt->rowCount();
}
