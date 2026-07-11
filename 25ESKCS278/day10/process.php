<?php
declare(strict_types=1);

/**
 * process.php
 * ------------------------------------------------------------
 * Single controller for all form submissions. Every write
 * operation uses a prepared statement with bound parameters.
 * On completion it redirects back to index.php with a flash
 * message stored in the session.
 * ------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/db_connect.php';

/** Store a flash message and redirect to the dashboard. */
function redirect_with_message(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: index.php');
    exit;
}

/** Guard: only logged-in admins may proceed. */
function require_admin(): void
{
    if (empty($_SESSION['is_admin'])) {
        redirect_with_message('error', 'Unauthorized. Please log in as admin first.');
    }
}

/** Shared validation for the student fields used by register & update. */
function validate_student_fields(array $data): array
{
    $errors = [];

    if ($data['full_name'] === '' || mb_strlen($data['full_name']) > 100) {
        $errors[] = 'Full name is required (max 100 characters).';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($data['phone'] === '' || !preg_match('/^[0-9+\-\s()]{7,20}$/', $data['phone'])) {
        $errors[] = 'A valid phone number is required.';
    }
    if (!DateTime::createFromFormat('Y-m-d', $data['date_of_birth'])) {
        $errors[] = 'A valid date of birth is required.';
    } elseif (new DateTime($data['date_of_birth']) > new DateTime()) {
        $errors[] = 'Date of birth cannot be in the future.';
    }
    if (!in_array($data['gender'], ['Male', 'Female', 'Other'], true)) {
        $errors[] = 'Please select a valid gender.';
    }
    if ($data['course'] === '' || mb_strlen($data['course']) > 100) {
        $errors[] = 'Course / program is required.';
    }

    return $errors;
}

/** Collect + trim the shared student fields from $_POST. */
function collect_student_input(): array
{
    return [
        'full_name'     => trim((string)($_POST['full_name'] ?? '')),
        'email'         => trim((string)($_POST['email'] ?? '')),
        'phone'         => trim((string)($_POST['phone'] ?? '')),
        'date_of_birth' => trim((string)($_POST['date_of_birth'] ?? '')),
        'gender'        => trim((string)($_POST['gender'] ?? '')),
        'course'        => trim((string)($_POST['course'] ?? '')),
        'address'       => trim((string)($_POST['address'] ?? '')),
    ];
}

function handle_register(PDO $pdo): void
{
    $data   = collect_student_input();
    $errors = validate_student_fields($data);

    if (!empty($errors)) {
        redirect_with_message('error', implode(' ', $errors));
    }

    try {
        $admissionNumber = 'STU' . date('Y') . strtoupper(bin2hex(random_bytes(3)));

        $stmt = $pdo->prepare(
            'INSERT INTO students
                (admission_number, full_name, email, phone, date_of_birth, gender, course, address)
             VALUES
                (:admission_number, :full_name, :email, :phone, :date_of_birth, :gender, :course, :address)'
        );

        $stmt->execute([
            ':admission_number' => $admissionNumber,
            ':full_name'        => $data['full_name'],
            ':email'            => $data['email'],
            ':phone'            => $data['phone'],
            ':date_of_birth'    => $data['date_of_birth'],
            ':gender'           => $data['gender'],
            ':course'           => $data['course'],
            ':address'          => $data['address'],
        ]);

        redirect_with_message(
            'success',
            "Thank you! Your details were submitted successfully. Your admission number is {$admissionNumber}."
        );
    } catch (PDOException $e) {
        if ((int)$e->getCode() === 23000 || $e->getCode() === '23000') {
            redirect_with_message('error', 'This email address is already registered.');
        }
        error_log('Insert Error: ' . $e->getMessage());
        redirect_with_message('error', 'A database error occurred. Please try again.');
    }
}

function handle_login(): void
{
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (hash_equals(ADMIN_USERNAME, $username) && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        redirect_with_message('success', 'Welcome back, admin.');
    }

    redirect_with_message('error', 'Invalid admin username or password.');
}

function handle_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain']);
    }
    session_destroy();
    header('Location: index.php');
    exit;
}

function handle_update(PDO $pdo): void
{
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        redirect_with_message('error', 'Invalid student record.');
    }

    $data   = collect_student_input();
    $errors = validate_student_fields($data);

    if (!empty($errors)) {
        redirect_with_message('error', implode(' ', $errors));
    }

    try {
        $stmt = $pdo->prepare(
            'UPDATE students SET
                full_name = :full_name,
                email = :email,
                phone = :phone,
                date_of_birth = :date_of_birth,
                gender = :gender,
                course = :course,
                address = :address
             WHERE id = :id'
        );

        $stmt->execute([
            ':full_name'     => $data['full_name'],
            ':email'         => $data['email'],
            ':phone'         => $data['phone'],
            ':date_of_birth' => $data['date_of_birth'],
            ':gender'        => $data['gender'],
            ':course'        => $data['course'],
            ':address'       => $data['address'],
            ':id'            => $id,
        ]);

        redirect_with_message('success', 'Student record updated successfully.');
    } catch (PDOException $e) {
        if ((int)$e->getCode() === 23000 || $e->getCode() === '23000') {
            redirect_with_message('error', 'Another student is already registered with this email.');
        }
        error_log('Update Error: ' . $e->getMessage());
        redirect_with_message('error', 'A database error occurred while updating.');
    }
}

function handle_delete(PDO $pdo): void
{
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        redirect_with_message('error', 'Invalid student record.');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            redirect_with_message('success', 'Student record deleted successfully.');
        }
        redirect_with_message('error', 'Student record not found (it may already be deleted).');
    } catch (PDOException $e) {
        error_log('Delete Error: ' . $e->getMessage());
        redirect_with_message('error', 'A database error occurred while deleting.');
    }
}

// ------------------------------------------------------------
// Router
// ------------------------------------------------------------
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'register':
        handle_register($pdo);
        break;

    case 'login':
        handle_login();
        break;

    case 'logout':
        handle_logout();
        break;

    case 'update':
        require_admin();
        handle_update($pdo);
        break;

    case 'delete':
        require_admin();
        handle_delete($pdo);
        break;

    default:
        redirect_with_message('error', 'Invalid or missing action.');
}