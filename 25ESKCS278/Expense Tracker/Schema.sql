

CREATE DATABASE IF NOT EXISTS expense_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE expense_tracker;

CREATE TABLE IF NOT EXISTS expenses (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150)      NOT NULL,
    amount          DECIMAL(10,2)     NOT NULL,
    category        VARCHAR(50)       NOT NULL,
    payment_method  VARCHAR(50)       NOT NULL DEFAULT 'Cash',
    expense_date    DATE              NOT NULL,
    notes           VARCHAR(255)      DEFAULT NULL,
    created_at      TIMESTAMP         DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP         DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (expense_date),
    INDEX idx_category (category)
) ENGINE=InnoDB;

-- Categories are no longer hard-coded in PHP — they live here so users
-- can add brand new ones straight from the Add Expense form.
CREATE TABLE IF NOT EXISTS categories (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(50)   NOT NULL UNIQUE,
    icon    VARCHAR(40)   NOT NULL DEFAULT 'bi-tag',
    color   VARCHAR(10)   NOT NULL DEFAULT '#6B7280'
) ENGINE=InnoDB;

-- A starter set — purely suggestions, the user can add/rename/remove freely
INSERT INTO categories (name, icon, color) VALUES
('Food & Dining',     'bi-cup-hot',            '#E8823D'),
('Groceries',          'bi-basket',              '#16A34A'),
('Transportation',     'bi-car-front',           '#2563EB'),
('Shopping',            'bi-bag',                 '#D4A72C'),
('Entertainment',       'bi-film',                '#9333EA'),
('Bills & Utilities',   'bi-lightning-charge',    '#0891B2'),
('Healthcare',          'bi-heart-pulse',         '#E11D48'),
('Education',           'bi-mortarboard',         '#4F46E5'),
('Travel',              'bi-airplane',            '#0D9488'),
('Others',              'bi-three-dots',          '#6B7280')
ON DUPLICATE KEY UPDATE name = name;

-- Optional: a monthly budget table, used by the dashboard "budget ring"
CREATE TABLE IF NOT EXISTS budget (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    month_year      VARCHAR(7)        NOT NULL UNIQUE, -- format: 2026-07
    monthly_limit   DECIMAL(10,2)     NOT NULL DEFAULT 20000.00
) ENGINE=InnoDB;

-- Seed a default budget for the current month so the ring has data on first run
INSERT INTO budget (month_year, monthly_limit)
VALUES (DATE_FORMAT(CURDATE(), '%Y-%m'), 20000.00)
ON DUPLICATE KEY UPDATE monthly_limit = monthly_limit;

-- Sample data (optional — delete if you want to start empty)
INSERT INTO expenses (title, amount, category, payment_method, expense_date, notes) VALUES
('Grocery shopping', 2450.00, 'Groceries', 'UPI', CURDATE() - INTERVAL 2 DAY, 'Weekly groceries from D-Mart'),
('Electricity bill', 1820.00, 'Bills & Utilities', 'Net Banking', CURDATE() - INTERVAL 4 DAY, NULL),
('Movie night', 650.00, 'Entertainment', 'Credit Card', CURDATE() - INTERVAL 6 DAY, 'PVR with friends'),
('Petrol', 1200.00, 'Transportation', 'Cash', CURDATE() - INTERVAL 1 DAY, NULL),
('Amazon order', 3299.00, 'Shopping', 'Credit Card', CURDATE() - INTERVAL 8 DAY, 'Headphones'),
('Doctor visit', 800.00, 'Healthcare', 'Cash', CURDATE() - INTERVAL 10 DAY, 'Routine checkup');