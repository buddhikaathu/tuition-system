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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $full_name = trim($_POST['full_name'] ?? '');
    $grade = (int) ($_POST['grade'] ?? 0);
    $parent_name = trim($_POST['parent_name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($full_name === '') $errors[] = 'Student name is required.';
    if ($grade < 4 || $grade > 11) $errors[] = 'Please select a valid grade.';
    if ($contact_number === '') $errors[] = 'Contact number is required.';

    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE students SET full_name=?, grade=?, parent_name=?, contact_number=?, address=?, status=? WHERE id=?");
        $stmt->execute([$full_name, $grade, $parent_name, $contact_number, $address, $status, $id]);
        flash('success', 'Student updated successfully.');
        redirect('/students/view.php?id=' . $id);
    }
}

$pageTitle = 'Edit Student';
require_once __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3">Edit Student: <?= e($student['full_name']) ?></h4>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="card p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Full Name *</label>
      <input type="text" name="full_name" class="form-control" required value="<?= e($student['full_name']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Grade *</label>
      <select name="grade" class="form-select" required>
        <?php for ($g = 4; $g <= 11; $g++): ?>
          <option value="<?= $g ?>" <?= $student['grade'] == $g ? 'selected' : '' ?>>Grade <?= $g ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active" <?= $student['status']==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $student['status']==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Contact Number *</label>
      <input type="text" name="contact_number" class="form-control" required value="<?= e($student['contact_number']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Parent / Guardian Name</label>
      <input type="text" name="parent_name" class="form-control" value="<?= e($student['parent_name']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Address</label>
      <textarea name="address" class="form-control" rows="2"><?= e($student['address']) ?></textarea>
    </div>
  </div>
  <div class="mt-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Changes</button>
    <a href="<?= BASE_URL ?>/students/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<p class="text-muted small mt-3">Note: grade changes do not automatically add/remove subject enrollments. Use "Add Subject Enrollment" on the student's profile page to enroll them in a newly-eligible subject.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
