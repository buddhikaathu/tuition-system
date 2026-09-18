<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) {
    flash('danger', 'Student not found.');
    redirect('/students/list.php');
}

$stmt = $pdo->prepare("SELECT subject FROM enrollments WHERE student_id = ? AND status='active' ORDER BY subject");
$stmt->execute([$id]);
$subjects = array_column($stmt->fetchAll(), 'subject');

$pageTitle = 'ID Card - ' . $student['full_name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="no-print d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Student ID Card</h4>
  <div>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Print Card</button>
    <a href="<?= BASE_URL ?>/students/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Back to Profile</a>
  </div>
</div>

<div class="no-print alert alert-info small">
  <i class="bi bi-info-circle"></i> This card's QR code stores the student's unique code (<strong><?= e($student['student_code']) ?></strong>).
  Print it, laminate it, and hand it to the student. Any teacher can scan it from the <strong>Scan &amp; Pay</strong> page to pull up the student and record a payment.
</div>

<div class="id-card mx-auto">
  <div class="id-card-header">
    <h5><?= e(APP_NAME) ?></h5>
    <div class="small">Student Identity Card</div>
  </div>
  <div class="id-card-body text-center">
    <div class="fw-bold fs-5"><?= e($student['full_name']) ?></div>
    <div class="text-muted mb-2">Grade <?= (int) $student['grade'] ?></div>

    <div class="qr-box" id="qrcode"></div>

    <div class="mt-2 student-code">Code: <?= e($student['student_code']) ?></div>
    <div class="small text-muted"><?= e($student['contact_number']) ?></div>

    <div class="subject-tags mt-2">
      <?php foreach ($subjects as $subj): ?>
        <span class="badge <?= subject_badge_class($subj) ?>"><?= e($subj) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
  new QRCode(document.getElementById("qrcode"), {
    text: <?= json_encode($student['student_code']) ?>,
    width: 140,
    height: 140,
    correctLevel: QRCode.CorrectLevel.M
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
