-- Практична робота №4, варіант 13 — Трекер особистих витрат
-- Крок 1. База даних і таблиця transactions

CREATE DATABASE IF NOT EXISTS practicum4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE practicum4;

CREATE TABLE transactions (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    amount           DECIMAL(10, 2) NOT NULL,   -- > 0 — дохід, < 0 — витрата
    category         VARCHAR(100)   NOT NULL,
    transaction_date DATE           NOT NULL
);

-- Початкові дані
INSERT INTO transactions (amount, category, transaction_date) VALUES
    (18000.00, 'Зарплата',           '2026-10-01'),
    (-245.50,  'Продукти',           '2026-10-02'),
    (-1850.00, 'Комунальні послуги', '2026-10-03'),
    (-120.00,  'Транспорт',          '2026-10-04'),
    (-480.00,  'Кафе',               '2026-10-08'),
    (-610.30,  'Продукти',           '2026-10-12');
