-- Практична робота №7, варіант 13 — Трекер особистих витрат
-- Крок 4. Міграція: індекс на стовпець фільтрації category
-- (використовується у WHERE category = :category і в GROUP BY category)
USE practicum7;

CREATE INDEX idx_transactions_category ON transactions (category);
