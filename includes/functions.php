<?php
/**
 * Shared helper functions.
 */

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes() {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Invalid or expired form submission (CSRF check failed). Please go back and try again.');
    }
}

/**
 * Generates the next sequential student code, e.g. TUT-0001, TUT-0002 ...
 */
function generate_student_code(PDO $pdo) {
    $stmt = $pdo->query("SELECT student_code FROM students ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $next = 1;
    if ($last && preg_match('/(\d+)$/', $last, $m)) {
        $next = ((int) $m[1]) + 1;
    }
    return 'TUT-' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

function format_money($amount) {
    return 'Rs. ' . number_format((float) $amount, 2);
}

function month_name($m) {
    $names = ['', 'January', 'February', 'March', 'April', 'May', 'June',
              'July', 'August', 'September', 'October', 'November', 'December'];
    return $names[(int) $m] ?? '';
}

/**
 * Which subjects are offered for a given grade.
 * Math: grade 4-11. Science & English: grade 10-11 only.
 */
function subjects_for_grade($grade) {
    $grade = (int) $grade;
    $subjects = [];
    if ($grade >= 4 && $grade <= 11) {
        $subjects[] = 'Math';
    }
    if ($grade >= 10 && $grade <= 11) {
        $subjects[] = 'Science';
        $subjects[] = 'English';
    }
    return $subjects;
}

function subject_badge_class($subject) {
    return match ($subject) {
        'Math'    => 'bg-primary',
        'Science' => 'bg-success',
        'English' => 'bg-warning text-dark',
        default   => 'bg-secondary',
    };
}
