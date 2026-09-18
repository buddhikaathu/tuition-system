<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$u = current_user();
$studentId = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();
if (!$student) {
    flash('danger', 'Student not found.');
    redirect('/students/list.php');
}

$stmt = $pdo->prepare("SELECT e.*, t.full_name AS teacher_name FROM enrollments e
                        JOIN teachers t ON t.id = e.teacher_id
                        WHERE e.student_id = ? AND e.status = 'active' ORDER BY e.subject");
$stmt->execute([$studentId]);
$enrollments = $stmt->fetchAll();

// A teacher may only record payments for their own subject
if (!is_admin()) {
    $enrollments = array_values(array_filter($enrollments, fn($e) => $e['subject'] === $u['subject']));
}

if (!$enrollments) {
    flash('danger', 'No subject enrollment available for you to record a payment for this student.');
    redirect('/students/view.php?id=' . $studentId);
}

$defaultSubject = $_GET['subject'] ?? $enrollments[0]['subject'];
$defaultMonth = (int) ($_GET['month'] ?? date('n'));
$defaultYear = (int) ($_GET['year'] ?? date('Y'));

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $enrollmentId = (int) ($_POST['enrollment_id'] ?? 0);
    $month = (int) ($_POST['month'] ?? 0);
    $year = (int) ($_POST['year'] ?? 0);
    $amount = $_POST['amount'] ?? '';
    $method = $_POST['payment_method'] ?? 'Cash';
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');

    // Make sure the enrollment belongs to this student and this teacher is allowed to touch it
    $validIds = array_column($enrollments, 'id');
    if (!in_array($enrollmentId, $validIds, true)) $errors[] = 'Invalid subject/enrollment selected.';
    if ($month < 1 || $month > 12) $errors[] = 'Invalid month.';
    if ($year < 2000) $errors[] = 'Invalid year.';
    if ($amount === '' || (float) $amount < 0) $errors[] = 'Please enter a valid amount.';

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO payments (enrollment_id, month, year, amount, payment_date, payment_method, status, recorded_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, 'paid', ?, ?)
            ON DUPLICATE KEY UPDATE amount = VALUES(amount), payment_date = VALUES(payment_date),
            payment_method = VALUES(payment_method), status = 'paid', recorded_by = VALUES(recorded_by), notes = VALUES(notes)");
        $stmt->execute([$enrollmentId, $month, $year, (float) $amount, $paymentDate, $method, $u['id'], $notes]);

        flash('success', 'Payment recorded successfully.');
        redirect('/payments/receipt.php?enrollment_id=' . $enrollmentId . '&month=' . $month . '&year=' . $year);
    }
}

$pageTitle = 'Record Payment';
require_once __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3">Record Payment: <?= e($student['full_name']) ?> <small class="text-muted"><?= e($student['student_code']) ?></small></h4>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="card p-4">
  <?= csrf_field() ?>
  <input type="hidden" name="student_id" value="<?= $studentId ?>">
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Subject *</label>
      <select name="enrollment_id" id="enrollmentSel" class="form-select" required>
        <?php foreach ($enrollments as $en): ?>
          <option value="<?= (int) $en['id'] ?>" data-fee="<?= e($en['monthly_fee']) ?>" <?= $en['subject'] === $defaultSubject ? 'selected' : '' ?>>
            <?= e($en['subject']) ?> (<?= e($en['teacher_name']) ?>) - <?= format_money($en['monthly_fee']) ?>/mo
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Month *</label>
      <select name="month" class="form-select" required>
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $m == $defaultMonth ? 'selected' : '' ?>><?= month_name($m) ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Year *</label>
      <input type="number" name="year" class="form-control" required value="<?= $defaultYear ?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">Amount (Rs.) *</label>
      <input type="number" step="0.01" min="0" name="amount" id="amountInput" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Payment Method</label>
      <select name="payment_method" class="form-select">
        <option value="Cash">Cash</option>
        <option value="Bank Transfer">Bank Transfer</option>
        <option value="Online">Online</option>
        <option value="Other">Other</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Payment Date</label>
      <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Notes</label>
      <input type="text" name="notes" class="form-control" placeholder="Optional">
    </div>
  </div>
  <div class="mt-3">
    <button type="submit" class="btn btn-success"><i class="bi bi-cash-coin"></i> Confirm Payment</button>
    <a href="<?= BASE_URL ?>/students/view.php?id=<?= $studentId ?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<script>
const enrollmentSel = document.getElementById('enrollmentSel');
const amountInput = document.getElementById('amountInput');
function syncFee() {
  const opt = enrollmentSel.options[enrollmentSel.selectedIndex];
  amountInput.value = opt ? opt.dataset.fee : '';
}
enrollmentSel.addEventListener('change', syncFee);
syncFee();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
