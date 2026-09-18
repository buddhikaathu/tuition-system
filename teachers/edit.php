<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$id]);
$teacher = $stmt->fetch();
if (!$teacher) {
    flash('danger', 'Teacher not found.');
    redirect('/teachers/list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $full_name = trim($_POST['full_name'] ?? '');
    $subject = $_POST['subject'] ?? '';
    $role = $_POST['role'] ?? 'teacher';
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $newPassword = $_POST['password'] ?? '';

    if ($full_name === '') $errors[] = 'Full name is required.';
    if (!in_array($subject, ['Math', 'Science', 'English'], true)) $errors[] = 'Please select a subject.';
    if ($newPassword !== '' && strlen($newPassword) < 6) $errors[] = 'New password must be at least 6 characters.';

    if (!$errors) {
        if ($newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE teachers SET full_name=?, subject=?, role=?, phone=?, status=?, password=? WHERE id=?");
            $stmt->execute([$full_name, $subject, $role, $phone, $status, $hash, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE teachers SET full_name=?, subject=?, role=?, phone=?, status=? WHERE id=?");
            $stmt->execute([$full_name, $subject, $role, $phone, $status, $id]);
        }
        flash('success', 'Teacher account updated.');
        redirect('/teachers/list.php');
    }
}

$pageTitle = 'Edit Teacher';
require_once __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3">Edit Teacher: <?= e($teacher['full_name']) ?></h4>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="card p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Full Name *</label>
      <input type="text" name="full_name" class="form-control" required value="<?= e($teacher['full_name']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Username</label>
      <input type="text" class="form-control" value="<?= e($teacher['username']) ?>" disabled>
    </div>
    <div class="col-md-6">
      <label class="form-label">Subject *</label>
      <select name="subject" class="form-select" required>
        <?php foreach (['Math','Science','English'] as $s): ?>
          <option value="<?= $s ?>" <?= $teacher['subject']===$s?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Role</label>
      <select name="role" class="form-select">
        <option value="teacher" <?= $teacher['role']==='teacher'?'selected':'' ?>>Teacher</option>
        <option value="admin" <?= $teacher['role']==='admin'?'selected':'' ?>>Admin</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="<?= e($teacher['phone']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active" <?= $teacher['status']==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $teacher['status']==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Reset Password</label>
      <input type="password" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep current password">
    </div>
  </div>
  <div class="mt-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Changes</button>
    <a href="<?= BASE_URL ?>/teachers/list.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
