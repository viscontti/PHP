-- Практична робота №7, варіант 13 — Трекер особистих витрат
-- Крок 4. План виконання запиту з фільтром за категорією (виконується до і після міграції 02)
USE practicum7;

EXPLAIN SELECT * FROM transactions WHERE category = 'Продукти';
EXPLAIN SELECT SUM(amount) FROM transactions WHERE category = 'Продукти';
