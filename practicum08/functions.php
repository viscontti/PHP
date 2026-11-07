<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Допоміжні функції, серверна валідація і запити до БД.
// Усі запити — підготовлені; кожен запит до transactions обмежений власником (user_id).
declare(strict_types=1);

// ---------- Крок 2. Екранування виводу (XSS) ----------

function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatMoney(float $amount): string
{
    return number_format($amount, 2, ',', "\u{00A0}") . "\u{00A0}грн";
}

function formatDate(string $date): string
{
    return date('d.m.Y', strtotime($date));
}

// ---------- Крок 4. Серверна валідація ----------

// Рядкове значення поля: масив (amount[]=...) або відсутнє поле -> null
function inputString(array $source, string $name): ?string
{
    $value = $source[$name] ?? null;
    if (is_int($value) || is_float($value)) {
        $value = (string) $value;     // число з JSON-тіла API
    }
    return is_string($value) ? trim($value) : null;
}

// Перевіряє поля транзакції; повертає [дані або null, помилки по полях]
function validateTransaction(array $source): array
{
    $errors = [];

    $amountRaw = inputString($source, 'amount');
    $amount = 0.0;
    if ($amountRaw === null || $amountRaw === '') {
        $errors['amount'] = 'Вкажіть суму';
    } else {
        $amountRaw = str_replace(',', '.', $amountRaw);
        $amount = is_numeric($amountRaw) ? round((float) $amountRaw, 2) : 0.0;
        if (!is_numeric($amountRaw)) {
            $errors['amount'] = 'Сума має бути числом';
        } elseif ($amount == 0) {
            $errors['amount'] = 'Сума не може дорівнювати нулю';
        } elseif (abs($amount) > 99999999.99) {
            $errors['amount'] = 'Сума за модулем не більша за 99 999 999,99';
        }
    }

    $category = inputString($source, 'category');
    if ($category === null || $category === '') {
        $errors['category'] = 'Вкажіть категорію';
    } elseif (mb_strlen($category) > 100) {
        $errors['category'] = 'Категорія — не більше 100 символів';
    } elseif (preg_match('/[\x00-\x1F\x7F]/u', $category) || !mb_check_encoding($category, 'UTF-8')) {
        $errors['category'] = 'Категорія містить недопустимі символи';
    }

    $date = inputString($source, 'transaction_date');
    if ($date === null || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)
        || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        $errors['transaction_date'] = 'Вкажіть коректну дату';
    }

    if ($errors !== []) {
        return [null, $errors];
    }
    return [['amount' => $amount, 'category' => $category, 'transaction_date' => $date], []];
}

// id з URL або форми: лише додатне ціле число, інакше null
function parseId(mixed $value): ?int
{
    if (!is_string($value) || !ctype_digit($value) || strlen($value) > 9 || (int) $value === 0) {
        return null;
    }
    return (int) $value;
}

// ---------- Запити (крок 1: лише підготовлені, значення — тільки через параметри) ----------

function findUserByUsername(string $username): ?array
{
    $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = :username');
    $stmt->execute([':username' => $username]);
    return $stmt->fetch() ?: null;
}

function getUserTransactions(int $userId, ?string $category = null): array
{
    if ($category === null) {
        $stmt = db()->prepare(
            'SELECT * FROM transactions WHERE user_id = :user_id ORDER BY transaction_date, id'
        );
        $stmt->execute([':user_id' => $userId]);
    } else {
        $stmt = db()->prepare(
            'SELECT * FROM transactions WHERE user_id = :user_id AND category = :category ORDER BY transaction_date, id'
        );
        $stmt->execute([':user_id' => $userId, ':category' => $category]);
    }
    return $stmt->fetchAll();
}

// Незахищена дія варіанта 13: запис шукається за id І власником — чужий запис «не існує»
function getUserTransaction(int $userId, int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM transactions WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
    return $stmt->fetch() ?: null;
}

function totalByCategory(int $userId, string $category): float
{
    $stmt = db()->prepare(
        'SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = :user_id AND category = :category'
    );
    $stmt->execute([':user_id' => $userId, ':category' => $category]);
    return (float) $stmt->fetchColumn();
}

function balance(int $userId): float
{
    $stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);
    return (float) $stmt->fetchColumn();
}

function addTransaction(int $userId, array $t): int
{
    $stmt = db()->prepare(
        'INSERT INTO transactions (user_id, amount, category, transaction_date)
         VALUES (:user_id, :amount, :category, :date)'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':amount' => $t['amount'],
        ':category' => $t['category'],
        ':date' => $t['transaction_date'],
    ]);
    return (int) db()->lastInsertId();
}

function updateTransaction(int $userId, int $id, array $t): void
{
    $stmt = db()->prepare(
        'UPDATE transactions SET amount = :amount, category = :category, transaction_date = :date
         WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
        ':amount' => $t['amount'],
        ':category' => $t['category'],
        ':date' => $t['transaction_date'],
        ':id' => $id,
        ':user_id' => $userId,
    ]);
}

function deleteTransaction(int $userId, int $id): int
{
    $stmt = db()->prepare('DELETE FROM transactions WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
    return $stmt->rowCount();
}
