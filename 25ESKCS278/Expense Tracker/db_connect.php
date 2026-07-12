<?php


$DB_HOST = 'localhost';
$DB_NAME = 'expense_tracker';
$DB_USER = 'root';
$DB_PASS = '';        
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]));
}

// Categories used across the app (dropdowns, chart colors, icons)
$CATEGORIES = [
    'Food & Dining'      => ['icon' => 'bi-cup-hot',        'color' => '#E8823D'],
    'Groceries'           => ['icon' => 'bi-basket',         'color' => '#16A34A'],
    'Transportation'      => ['icon' => 'bi-car-front',      'color' => '#2563EB'],
    'Shopping'            => ['icon' => 'bi-bag',            'color' => '#D4A72C'],
    'Entertainment'       => ['icon' => 'bi-film',           'color' => '#9333EA'],
    'Bills & Utilities'   => ['icon' => 'bi-lightning-charge','color' => '#0891B2'],
    'Healthcare'          => ['icon' => 'bi-heart-pulse',    'color' => '#E11D48'],
    'Education'           => ['icon' => 'bi-mortarboard',    'color' => '#4F46E5'],
    'Travel'              => ['icon' => 'bi-airplane',       'color' => '#0D9488'],
    'Others'              => ['icon' => 'bi-three-dots',     'color' => '#6B7280'],
];

$PAYMENT_METHODS = ['Cash', 'Credit Card', 'Debit Card', 'UPI', 'Net Banking', 'Other'];