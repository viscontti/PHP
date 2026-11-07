-- Практична робота №8, варіант 13 — Трекер особистих витрат
-- Фінальна база застосунку: користувачі + транзакції, що належать користувачу (user_id).
-- Паролі зберігаються лише як хеш password_hash() (bcrypt).
-- Тестові облікові записи: veronika / veronika2026, olena / olena2026

CREATE DATABASE IF NOT EXISTS practicum8 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE practicum8;

DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                   -- власник запису
    amount DECIMAL(10,2) NOT NULL,          -- додатна — дохід, від'ємна — витрата
    category VARCHAR(100) NOT NULL,
    transaction_date DATE NOT NULL,
    CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_transactions_user (user_id),
    INDEX idx_transactions_category (category)   -- з практикуму №7
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, username, password_hash) VALUES
    (1, 'veronika', '$2y$12$1nkEZ2RMz6M10Rsut9jCfey6e7gKliSFmGw1SRtdN21HCbW4hAPg6'),
    (2, 'olena',    '$2y$12$APT.khZSTSh7OL4ybHJPCeNPBmwmFpH9hinqo6hMJuDrM52XL2SHu');

INSERT INTO transactions (user_id, amount, category, transaction_date) VALUES
    (1, 18000.00, 'Зарплата',           '2026-11-01'),
    (1,  -245.50, 'Продукти',           '2026-11-02'),
    (1, -1850.00, 'Комунальні послуги', '2026-11-03'),
    (1,  -520.00, 'Кафе і ресторани',   '2026-11-05'),
    (1,  -610.30, 'Продукти',           '2026-11-07'),
    (1,  -199.00, 'Підписки',           '2026-11-08'),
    (2, 25000.00, 'Зарплата',           '2026-11-01'),
    (2, -3200.00, 'Оренда',             '2026-11-02'),
    (2,  -890.00, 'Медицина',           '2026-11-04');
