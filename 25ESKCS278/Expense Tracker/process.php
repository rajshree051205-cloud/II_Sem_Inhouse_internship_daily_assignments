<?php


require_once 'db_connect.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    case 'add':
        addExpense($pdo);
        break;

    case 'update':
        updateExpense($pdo);
        break;

    case 'delete':
        deleteExpense($pdo);
        break;

    case 'get_one':
        getOneExpense($pdo);
        break;

    case 'list':
        listExpenses($pdo);
        break;

    case 'stats':
        getStats($pdo);
        break;

    case 'set_budget':
        setBudget($pdo);
        break;

    case 'list_categories':
        listCategories($pdo);
        break;

    default:
        respond(false, 'Unknown action.');
}


function addExpense(PDO $pdo): void
{
    $data = validateInput($_POST);
    if ($data['errors']) {
        respond(false, implode(' ', $data['errors']));
    }

    ensureCategoryExists($pdo, $data['category']);

    $sql = "INSERT INTO expenses (title, amount, category, payment_method, expense_date, notes)
            VALUES (:title, :amount, :category, :payment_method, :expense_date, :notes)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title'          => $data['title'],
        ':amount'         => $data['amount'],
        ':category'       => $data['category'],
        ':payment_method' => $data['payment_method'],
        ':expense_date'   => $data['expense_date'],
        ':notes'          => $data['notes'],
    ]);

    respond(true, 'Expense added successfully.', ['id' => $pdo->lastInsertId()]);
}

function updateExpense(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        respond(false, 'Invalid expense id.');
    }

    $data = validateInput($_POST);
    if ($data['errors']) {
        respond(false, implode(' ', $data['errors']));
    }

    ensureCategoryExists($pdo, $data['category']);

    $sql = "UPDATE expenses
            SET title = :title, amount = :amount, category = :category,
                payment_method = :payment_method, expense_date = :expense_date, notes = :notes
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title'          => $data['title'],
        ':amount'         => $data['amount'],
        ':category'       => $data['category'],
        ':payment_method' => $data['payment_method'],
        ':expense_date'   => $data['expense_date'],
        ':notes'          => $data['notes'],
        ':id'             => $id,
    ]);

    if ($stmt->rowCount() === 0) {
        // Could be "no change" or "not found" — check existence separately
        $check = $pdo->prepare("SELECT id FROM expenses WHERE id = :id");
        $check->execute([':id' => $id]);
        if (!$check->fetch()) {
            respond(false, 'Expense not found.');
        }
    }

    respond(true, 'Expense updated successfully.');
}

function deleteExpense(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        respond(false, 'Invalid expense id.');
    }

    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        respond(false, 'Expense not found or already deleted.');
    }

    respond(true, 'Expense deleted successfully.');
}

function getOneExpense(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respond(false, 'Expense not found.');
    }
    respond(true, 'OK', $row);
}

function listExpenses(PDO $pdo): void
{
    $where  = [];
    $params = [];

    if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
        $where[] = 'category = :category';
        $params[':category'] = $_GET['category'];
    }
    if (!empty($_GET['search'])) {
        $where[] = '(title LIKE :search OR notes LIKE :search)';
        $params[':search'] = '%' . $_GET['search'] . '%';
    }
    if (!empty($_GET['date_from'])) {
        $where[] = 'expense_date >= :date_from';
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'expense_date <= :date_to';
        $params[':date_to'] = $_GET['date_to'];
    }

    $sql = "SELECT * FROM expenses";
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $sortMap = [
        'date_desc'   => 'expense_date DESC, id DESC',
        'date_asc'    => 'expense_date ASC, id ASC',
        'amount_desc' => 'amount DESC',
        'amount_asc'  => 'amount ASC',
    ];
    $sort = $sortMap[$_GET['sort'] ?? 'date_desc'] ?? $sortMap['date_desc'];
    $sql .= " ORDER BY {$sort}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    respond(true, 'OK', $rows);
}

function getStats(PDO $pdo): void
{
    $monthStart = date('Y-m-01');
    $monthEnd   = date('Y-m-t');

    // Total overall
    $total = $pdo->query("SELECT COALESCE(SUM(amount),0) AS t FROM expenses")->fetch()['t'];

    // This month total
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS t FROM expenses WHERE expense_date BETWEEN :s AND :e");
    $stmt->execute([':s' => $monthStart, ':e' => $monthEnd]);
    $monthTotal = $stmt->fetch()['t'];

    // Transaction count
    $count = $pdo->query("SELECT COUNT(*) AS c FROM expenses")->fetch()['c'];

    // Category breakdown (this month)
    $stmt = $pdo->prepare("SELECT category, SUM(amount) AS total FROM expenses
                            WHERE expense_date BETWEEN :s AND :e
                            GROUP BY category ORDER BY total DESC");
    $stmt->execute([':s' => $monthStart, ':e' => $monthEnd]);
    $byCategory = $stmt->fetchAll();

    // Last 6 months trend
    $stmt = $pdo->query("SELECT DATE_FORMAT(expense_date, '%Y-%m') AS ym, SUM(amount) AS total
                          FROM expenses
                          WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
                          GROUP BY ym ORDER BY ym ASC");
    $trend = $stmt->fetchAll();

    // Budget for current month
    $stmt = $pdo->prepare("SELECT monthly_limit FROM budget WHERE month_year = :ym");
    $stmt->execute([':ym' => date('Y-m')]);
    $budgetRow = $stmt->fetch();
    $budget = $budgetRow ? (float)$budgetRow['monthly_limit'] : 0;

    // Highest single category this month
    $topCategory = $byCategory[0]['category'] ?? null;

    respond(true, 'OK', [
        'total'        => (float)$total,
        'month_total'  => (float)$monthTotal,
        'count'        => (int)$count,
        'by_category'  => $byCategory,
        'trend'        => $trend,
        'budget'       => $budget,
        'top_category' => $topCategory,
    ]);
}

function listCategories(PDO $pdo): void
{
    $rows = $pdo->query("SELECT name, icon, color FROM categories ORDER BY name ASC")->fetchAll();
    respond(true, 'OK', $rows);
}

/**
 * If the user typed a category that doesn't exist yet, create it on the fly
 * with an auto-assigned color so it shows up correctly in the table/charts
 * and is remembered (suggested) next time.
 */
function ensureCategoryExists(PDO $pdo, string $category): void
{
    global $CATEGORY_COLOR_PALETTE;

    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name");
    $stmt->execute([':name' => $category]);
    if ($stmt->fetch()) {
        return; // already exists
    }

    $color = $CATEGORY_COLOR_PALETTE[crc32($category) % count($CATEGORY_COLOR_PALETTE)];
    $stmt = $pdo->prepare("INSERT INTO categories (name, icon, color) VALUES (:name, 'bi-tag', :color)
                            ON DUPLICATE KEY UPDATE name = name");
    $stmt->execute([':name' => $category, ':color' => $color]);
}

function setBudget(PDO $pdo): void
{
    $limit = (float)($_POST['monthly_limit'] ?? 0);
    if ($limit <= 0) {
        respond(false, 'Enter a valid budget amount.');
    }
    $ym = date('Y-m');
    $stmt = $pdo->prepare("INSERT INTO budget (month_year, monthly_limit) VALUES (:ym, :limit)
                            ON DUPLICATE KEY UPDATE monthly_limit = :limit2");
    $stmt->execute([':ym' => $ym, ':limit' => $limit, ':limit2' => $limit]);

    respond(true, 'Budget updated.');
}

// Helpers

function validateInput(array $in): array
{
    $errors = [];

    $title = trim($in['title'] ?? '');
    if ($title === '' || mb_strlen($title) > 150) {
        $errors[] = 'Title is required (max 150 characters).';
    }

    $amount = $in['amount'] ?? null;
    if (!is_numeric($amount) || (float)$amount <= 0) {
        $errors[] = 'Amount must be a positive number.';
    }

    // Category and payment method are free text — the user isn't limited to
    // a fixed list, they just can't be left blank or absurdly long.
    $category = trim($in['category'] ?? '');
    if ($category === '' || mb_strlen($category) > 50) {
        $errors[] = 'Category is required (max 50 characters).';
    }

    $paymentMethod = trim($in['payment_method'] ?? '');
    if ($paymentMethod === '' || mb_strlen($paymentMethod) > 50) {
        $errors[] = 'Payment method is required (max 50 characters).';
    }

    $date = $in['expense_date'] ?? '';
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        $errors[] = 'Please provide a valid date.';
    }

    $notes = trim($in['notes'] ?? '');
    if (mb_strlen($notes) > 255) {
        $errors[] = 'Notes must be under 255 characters.';
    }

    return [
        'errors'         => $errors,
        'title'          => $title,
        'amount'         => is_numeric($amount) ? round((float)$amount, 2) : 0,
        'category'       => $category,
        'payment_method' => $paymentMethod,
        'expense_date'   => $date,
        'notes'          => $notes !== '' ? $notes : null,
    ];
}

function respond(bool $success, string $message, $data = null): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ]);
    exit;
}