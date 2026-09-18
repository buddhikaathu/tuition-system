<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$u = current_user();
$month = (int) ($_GET['month'] ?? date('n'));
$year  = (int) ($_GET['year'] ?? date('Y'));
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT p.*, s.id AS student_id, s.full_name, s.student_code, e.subject, e.monthly_fee
        FROM enrollments e
        JOIN students s ON s.id = e.student_id
        LEFT JOIN payments p ON p.enrollment_id = e.id AND p.month = :month AND p.year = :year
        WHERE e.status = 'active'";
$params = [':month' => $month, ':year' => $year];

if (!is_admin()) {
    $sql .= " AND e.subject = :subj";
    $params[':subj'] = $u['subject'];
}

$sql .= " ORDER BY s.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

if ($statusFilter === 'paid') {
    $rows = array_filter($rows, fn($r) => $r['status'] === 'paid');
} elseif ($statusFilter === 'pending') {
    $rows = array_filter($rows, fn($r) => $r['status'] !== 'paid');
}

$pageTitle = 'Payments';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Payments</h4>
  <a href="<?= BASE_URL ?>/payments/scan.php" class="btn btn-success"><i class="bi bi-qr-code-scan"></i> Scan &amp; Pay</a>
</div>

<div class="card p-3 mb-3">
  <form class="row g-2" method="get">
    <div class="col-md-3">
      <select name="month" class="form-select">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= month_name($m) ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-2">
      <input type="number" name="year" class="form-control" value="<?= $year ?>">
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">All Statuses</option>
        <option value="paid" <?= $statusFilter==='paid'?'selected':'' ?>>Paid</option>
        <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </div>
  </form>
</div>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th>Student</th><th>Subject</th><th>Expected Fee</th><th>Status</th><th>Paid Date</th><th></th></tr></thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No enrollments found for this filter.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/students/view.php?id=<?= (int) $r['student_id'] ?>"><?= e($r['full_name']) ?></a>
              <div class="small text-muted"><?= e($r['student_code']) ?></div></td>
          <td><span class="badge <?= subject_badge_class($r['subject']) ?>"><?= e($r['subject']) ?></span></td>
          <td><?= format_money($r['monthly_fee']) ?></td>
          <td>
            <?php if ($r['status'] === 'paid'): ?>
              <span class="badge bg-success">Paid</span>
            <?php else: ?>
              <span class="badge bg-danger">Pending</span>
            <?php endif; ?>
          </td>
          <td><?= e($r['payment_date'] ?: '-') ?></td>
          <td class="text-end">
            <?php if ($r['status'] !== 'paid'): ?>
              <a href="<?= BASE_URL ?>/payments/add.php?student_id=<?= (int) $r['student_id'] ?>&subject=<?= e($r['subject']) ?>&month=<?= $month ?>&year=<?= $year ?>"
                 class="btn btn-sm btn-success">Mark Paid</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
