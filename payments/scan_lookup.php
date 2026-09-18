<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in again.']);
    exit;
}

$u = current_user();
$code = trim($_GET['code'] ?? '');

if ($code === '') {
    echo json_encode(['error' => 'No code provided.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE student_code = ? LIMIT 1");
$stmt->execute([$code]);
$student = $stmt->fetch();

if (!$student) {
    echo json_encode(['error' => "No student found for code \"" . e($code) . "\"."]);
    exit;
}

$month = (int) date('n');
$year = (int) date('Y');

$stmt = $pdo->prepare("SELECT e.*, p.status AS payment_status
    FROM enrollments e
    LEFT JOIN payments p ON p.enrollment_id = e.id AND p.month = ? AND p.year = ?
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY e.subject");
$stmt->execute([$month, $year, $student['id']]);
$enrollments = $stmt->fetchAll();

$out = [];
foreach ($enrollments as $en) {
    $out[] = [
        'subject'         => $en['subject'],
        'monthly_fee_fmt' => format_money($en['monthly_fee']),
        'paid'            => $en['payment_status'] === 'paid',
        'can_manage'      => can_manage_subject($en['subject']),
    ];
}

echo json_encode([
    'student_id'     => (int) $student['id'],
    'student_code'   => $student['student_code'],
    'full_name'      => $student['full_name'],
    'grade'          => (int) $student['grade'],
    'contact_number' => $student['contact_number'],
    'month_label'    => month_name($month) . ' ' . $year,
    'enrollments'    => $out,
]);
