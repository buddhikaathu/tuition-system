<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$enrollmentId = (int) ($_GET['enrollment_id'] ?? 0);
$month = (int) ($_GET['month'] ?? 0);
$year = (int) ($_GET['year'] ?? 0);

$stmt = $pdo->prepare("SELECT p.*, e.subject, e.monthly_fee, s.full_name, s.student_code, s.grade, t.full_name AS teacher_name
    FROM payments p
    JOIN enrollments e ON e.id = p.enrollment_id
    JOIN students s ON s.id = e.student_id
    JOIN teachers t ON t.id = e.teacher_id
    WHERE p.enrollment_id = ? AND p.month = ? AND p.year = ?");
$stmt->execute([$enrollmentId, $month, $year]);
$payment = $stmt->fetch();

if (!$payment) {
    flash('danger', 'Payment record not found.');
    redirect('/payments/list.php');
}

$pageTitle = 'Receipt';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="no-print d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Payment Receipt</h4>
  <div>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Print</button>
    <a href="<?= BASE_URL ?>/payments/list.php" class="btn btn-outline-secondary btn-sm">Back to Payments</a>
  </div>
</div>

<div class="card p-4 mx-auto" style="max-width:480px;">
  <div class="text-center mb-3">
    <h5 class="mb-0"><?= e(APP_NAME) ?></h5>
    <div class="text-muted small">Official Payment Receipt</div>
  </div>
  <hr>
  <table class="table table-sm mb-0">
    <tr><th>Receipt No.</th><td>#<?= str_pad($payment['id'], 6, '0', STR_PAD_LEFT) ?></td></tr>
    <tr><th>Student</th><td><?= e($payment['full_name']) ?> (<?= e($payment['student_code']) ?>)</td></tr>
    <tr><th>Grade</th><td>Grade <?= (int) $payment['grade'] ?></td></tr>
    <tr><th>Subject</th><td><span class="badge <?= subject_badge_class($payment['subject']) ?>"><?= e($payment['subject']) ?></span></td></tr>
    <tr><th>Teacher</th><td><?= e($payment['teacher_name']) ?></td></tr>
    <tr><th>For Month</th><td><?= e(month_name($payment['month'])) ?> <?= (int) $payment['year'] ?></td></tr>
    <tr><th>Amount Paid</th><td class="fw-bold"><?= format_money($payment['amount']) ?></td></tr>
    <tr><th>Payment Method</th><td><?= e($payment['payment_method']) ?></td></tr>
    <tr><th>Payment Date</th><td><?= e($payment['payment_date']) ?></td></tr>
  </table>
  <hr>
  <p class="text-center text-muted small mb-0">Thank you!</p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
