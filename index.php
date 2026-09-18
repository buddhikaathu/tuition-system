<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$u = current_user();
$month = (int) date('n');
$year  = (int) date('Y');

// Build subject filter: admin sees all subjects, teacher sees only their own
$subjectFilter = '';
$params = [':month' => $month, ':year' => $year];
if (!is_admin()) {
    $subjectFilter = ' AND e.subject = :subject';
    $params[':subject'] = $u['subject'];


// Total active students (in at least one active enrollment matching filter)
$sql = "SELECT COUNT(DISTINCT s.id) FROM students s
        JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
        WHERE s.status = 'active' $subjectFilter";
$stmt = $pdo->prepare($sql);
$stmt->execute(is_admin() ? [] : [':subject' => $u['subject']]);
$totalStudents = (int) $stmt->fetchColumn();

// Total active enrollments this scope
$sql = "SELECT COUNT(*) FROM enrollments e WHERE e.status='active' $subjectFilter";
$stmt = $pdo->prepare($sql);
$stmt->execute(is_admin() ? [] : [':subject' => $u['subject']]);
$totalEnrollments = (int) $stmt->fetchColumn();

// Paid vs pending this month
$sql = "SELECT p.status, COUNT(*) as cnt, COALESCE(SUM(p.amount),0) as total
        FROM payments p
        JOIN enrollments e ON e.id = p.enrollment_id
        WHERE p.month = :month AND p.year = :year $subjectFilter
        GROUP BY p.status";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$paidCount = 0; $paidTotal = 0; $pendingCount = 0; $pendingTotal = 0;
foreach ($stmt->fetchAll() as $row) {
    if ($row['status'] === 'paid') { $paidCount = $row['cnt']; $paidTotal = $row['total']; }
    else { $pendingCount = $row['cnt']; $pendingTotal = $row['total']; }
}

// Enrollments with NO payment record at all for this month (never invoiced/paid)
$sql = "SELECT COUNT(*) FROM enrollments e
        WHERE e.status = 'active' $subjectFilter
        AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.enrollment_id = e.id AND p.month = :month AND p.year = :year)";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$noRecord = (int) $stmt->fetchColumn();

// Recent payments
$sql = "SELECT p.*, s.id as student_id, s.full_name, s.student_code, e.subject
        FROM payments p
        JOIN enrollments e ON e.id = p.enrollment_id
        JOIN students s ON s.id = e.student_id
        WHERE p.status='paid' $subjectFilter
        ORDER BY p.payment_date DESC, p.id DESC LIMIT 8";
$stmt = $pdo->prepare($sql);
$stmt->execute(is_admin() ? [] : [':subject' => $u['subject']]);
$recentPayments = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Welcome, <?= e($u['full_name']) ?> 👋</h4>
  <span class="text-muted"><?= e(month_name($month)) ?> <?= $year ?></span>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="card stat-card bg-primary text-white p-3">
      <div class="stat-number"><?= $totalStudents ?></div>
      <div>Active Students</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card stat-card bg-info text-white p-3">
      <div class="stat-number"><?= $totalEnrollments ?></div>
      <div>Active Enrollments</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card stat-card bg-success text-white p-3">
      <div class="stat-number"><?= $paidCount ?></div>
      <div>Paid this month (<?= format_money($paidTotal) ?>)</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card stat-card bg-danger text-white p-3">
      <div class="stat-number"><?= $pendingCount + $noRecord ?></div>
      <div>Pending / Unpaid this month</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-3">
      <h5>Recent Payments</h5>
      <?php if (!$recentPayments): ?>
        <p class="text-muted mb-0">No payments recorded yet.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Student</th><th>Subject</th><th>Month</th><th>Amount</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($recentPayments as $p): ?>
            <tr>
              <td><a href="<?= BASE_URL ?>/students/view.php?id=<?= (int) $p['student_id'] ?>">
                  <?= e($p['full_name']) ?> <small class="text-muted"><?= e($p['student_code']) ?></small></a></td>
              <td><span class="badge <?= subject_badge_class($p['subject']) ?>"><?= e($p['subject']) ?></span></td>
              <td><?= e(month_name($p['month'])) ?> <?= (int)$p['year'] ?></td>
              <td><?= format_money($p['amount']) ?></td>
              <td><?= e($p['payment_date']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <h5>Quick Actions</h5>
      <div class="d-grid gap-2">
        <a href="<?= BASE_URL ?>/students/add.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add New Student</a>
        <a href="<?= BASE_URL ?>/payments/scan.php" class="btn btn-success"><i class="bi bi-qr-code-scan"></i> Scan Card to Pay</a>
        <a href="<?= BASE_URL ?>/payments/list.php" class="btn btn-outline-secondary"><i class="bi bi-list-check"></i> View All Payments</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
