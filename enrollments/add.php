<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin(); // only admin manages cross-subject enrollment

$studentId = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();
if (!$student) {
    flash('danger', 'Student not found.');
    redirect('/students/list.php');
}

$stmt = $pdo->prepare("SELECT subject FROM enrollments WHERE student_id = ?");
$stmt->execute([$studentId]);
$already = array_column($stmt->fetchAll(), 'subject');

$eligible = array_diff(subjects_for_grade($student['grade']), $already);

$teachersBySubject = ['Math' => [], 'Science' => [], 'English' => []];
foreach ($pdo->query("SELECT id, full_name, subject FROM teachers WHERE status='active'") as $t) {
    $teachersBySubject[$t['subject']][] = $t;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $subject = $_POST['subject'] ?? '';
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    $fee = $_POST['monthly_fee'] ?? '';
    $startDate = $_POST['start_date'] ?? date('Y-m-d');

    if (!in_array($subject, $eligible, true)) $errors[] = 'Invalid subject for this student\'s grade, or already enrolled.';
    if (!$teacherId) $errors[] = 'Please choose a teacher.';
    if ($fee === '' || (float) $fee < 0) $errors[] = 'Please enter a valid monthly fee.';

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, subject, teacher_id, monthly_fee, start_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$studentId, $subject, $teacherId, (float) $fee, $startDate]);
        flash('success', "$subject enrollment added.");
        redirect('/students/view.php?id=' . $studentId);
    }
}

$pageTitle = 'Add Enrollment';
require_once __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3">Add Subject Enrollment: <?= e($student['full_name']) ?></h4>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if (!$eligible): ?>
  <div class="alert alert-warning">This student is already enrolled in every subject available for Grade <?= (int) $student['grade'] ?>.</div>
  <a href="<?= BASE_URL ?>/students/view.php?id=<?= $studentId ?>" class="btn btn-outline-secondary">Back to Profile</a>
<?php else: ?>
<form method="post" class="card p-4">
  <?= csrf_field() ?>
  <input type="hidden" name="student_id" value="<?= $studentId ?>">
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Subject *</label>
      <select name="subject" id="subjectSel" class="form-select" required>
        <?php foreach ($eligible as $subj): ?>
          <option value="<?= $subj ?>"><?= $subj ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Teacher *</label>
      <select name="teacher_id" id="teacherSel" class="form-select" required></select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Monthly Fee (Rs.) *</label>
      <input type="number" step="0.01" min="0" name="monthly_fee" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Start Date</label>
      <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
    </div>
  </div>
  <div class="mt-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Add Enrollment</button>
    <a href="<?= BASE_URL ?>/students/view.php?id=<?= $studentId ?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<script>
const teachersBySubject = <?= json_encode($teachersBySubject) ?>;
const subjectSel = document.getElementById('subjectSel');
const teacherSel = document.getElementById('teacherSel');

function fillTeachers() {
  const subj = subjectSel.value;
  teacherSel.innerHTML = '<option value="">Select teacher</option>';
  (teachersBySubject[subj] || []).forEach(t => {
    const opt = document.createElement('option');
    opt.value = t.id;
    opt.textContent = t.full_name;
    teacherSel.appendChild(opt);
  });
}
subjectSel.addEventListener('change', fillTeachers);
fillTeachers();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
