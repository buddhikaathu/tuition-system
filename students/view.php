<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$u = current_user();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) {
    flash('danger', 'Student not found.');
    redirect('/students/list.php');
}

$stmt = $pdo->prepare("SELECT e.*, t.full_name AS teacher_name FROM enrollments e
                        JOIN teachers t ON t.id = e.teacher_id
                        WHERE e.student_id = ? ORDER BY e.subject");
$stmt->execute([$id]);
$enrollments = $stmt->fetchAll();

// restrict teacher view to their own subject's enrollment(s) only
if (!is_admin()) {
    $enrollments = array_values(array_filter($enrollments, fn($e) => $e['subject'] === $u['subject']));
    if (!$enrollments) {
        flash('danger', 'This student is not enrolled in your subject.');
        redirect('/students/list.php');
    }
}

$enrollmentIds = array_column($enrollments, 'id');
$payments = [];
if ($enrollmentIds) {
    $in = implode(',', array_fill(0, count($enrollmentIds), '?'));
    $stmt = $pdo->prepare("SELECT p.*, e.subject FROM payments p
                            JOIN enrollments e ON e.id = p.enrollment_id
                            WHERE p.enrollment_id IN ($in)
                            ORDER BY p.year DESC, p.month DESC");
    $stmt->execute($enrollmentIds);
    $payments = $stmt->fetchAll();
}

$pageTitle = $student['full_name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><?= e($student['full_name']) ?> <small class="text-muted"><?= e($student['student_code']) ?></small></h4>
  <div>
    <a href="<?= BASE_URL ?>/students/card.php?id=<?= $id ?>" class="btn btn-outline-dark btn-sm"><i class="bi bi-qr-code"></i> ID Card</a>
    <a href="<?= BASE_URL ?>/students/edit.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h6 class="text-muted">Student Details</h6>
      <table class="table table-sm mb-0">
        <tr><th>Grade</th><td>Grade <?= (int) $student['grade'] ?></td></tr>
        <tr><th>Contact</th><td><?= e($student['contact_number']) ?></td></tr>
        <tr><th>Parent/Guardian</th><td><?= e($student['parent_name'] ?: '-') ?></td></tr>
        <tr><th>Address</th><td><?= nl2br(e($student['address'] ?: '-')) ?></td></tr>
        <tr><th>Enrolled Since</th><td><?= e($student['enrollment_date']) ?></td></tr>
        <tr><th>Status</th><td><span class="badge bg-<?= $student['status']==='active'?'success':'secondary' ?>"><?= e($student['status']) ?></span></td></tr>
      </table>
    </div>

    <div class="card p-3 mt-3">
      <h6 class="text-muted">Subjects Enrolled</h6>
      <?php foreach ($enrollments as $en): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
          <div>
            <span class="badge <?= subject_badge_class($en['subject']) ?>"><?= e($en['subject']) ?></span>
            <div class="small text-muted">Teacher: <?= e($en['teacher_name']) ?></div>
          </div>
          <div class="text-end">
            <div class="fw-semibold"><?= format_money($en['monthly_fee']) ?>/mo</div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (is_admin()): ?>
      <a href="<?= BASE_URL ?>/enrollments/add.php?student_id=<?= $id ?>" class="btn btn-sm btn-outline-primary mt-3">
        <i class="bi bi-plus-circle"></i> Add Subject Enrollment
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="text-muted mb-0">Payment History</h6>
        <a href="<?= BASE_URL ?>/payments/add.php?student_id=<?= $id ?>" class="btn btn-sm btn-success"><i class="bi bi-plus-circle"></i> Record Payment</a>
      </div>
      <?php if (!$payments): ?>
        <p class="text-muted mb-0">No payment records yet.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Subject</th><th>Month</th><th>Amount</th><th>Status</th><th>Paid On</th><th>Method</th></tr></thead>
          <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td><span class="badge <?= subject_badge_class($p['subject']) ?>"><?= e($p['subject']) ?></span></td>
              <td><?= e(month_name($p['month'])) ?> <?= (int) $p['year'] ?></td>
              <td><?= format_money($p['amount']) ?></td>
              <td>
                <?php if ($p['status'] === 'paid'): ?>
                  <span class="badge bg-success">Paid</span>
                <?php else: ?>
                  <span class="badge bg-danger">Pending</span>
                <?php endif; ?>
              </td>
              <td><?= e($p['payment_date'] ?: '-') ?></td>
              <td><?= e($p['payment_method'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
