-- Практична робота №7, варіант 13 — Трекер особистих витрат
-- Окрема база для навантажувальних експериментів: та сама таблиця transactions, що й у практикумах №4–6,
-- але з 1000 тестових записів (на кількох рядках різниця до/після оптимізацій непомітна).
-- Індексу на category тут навмисно НЕМАЄ — його додає міграція 02_add_index_category.sql.

CREATE DATABASE IF NOT EXISTS practicum7 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE practicum7;

DROP TABLE IF EXISTS transactions;
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10,2) NOT NULL,          -- додатна — дохід, від'ємна — витрата
    category VARCHAR(100) NOT NULL,
    transaction_date DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1000 детермінованих записів: кожен 25-й — дохід «Зарплата», решта — витрати в 11 категоріях
SET SESSION cte_max_recursion_depth = 1000;
INSERT INTO transactions (amount, category, transaction_date)
WITH RECURSIVE seq (n) AS (
    SELECT 1
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 1000
)
SELECT
    IF(n % 25 = 0, 18000.00, -ROUND(20 + (n * 37) % 1500 + (n % 100) / 100, 2)),
    IF(n % 25 = 0, 'Зарплата',
       ELT(1 + n % 11, 'Продукти', 'Комунальні послуги', 'Кафе і ресторани', 'Підписки', 'Кав''ярня',
           'Аптека', 'Таксі', 'Кіно', 'Одяг', 'Спорт', 'Подарунки')),
    DATE_ADD('2026-01-01', INTERVAL n % 310 DAY)
FROM seq;
