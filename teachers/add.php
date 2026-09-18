<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $subject = $_POST['subject'] ?? '';
    $role = $_POST['role'] ?? 'teacher';
    $phone = trim($_POST['phone'] ?? '');

    if ($username === '') $errors[] = 'Username is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($full_name === '') $errors[] = 'Full name is required.';
    if (!in_array($subject, ['Math', 'Science', 'English'], true)) $errors[] = 'Please select a subject.';

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) $errors[] = 'That username is already taken.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO teachers (username, password, full_name, subject, role, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $full_name, $subject, $role, $phone]);
        flash('success', 'Teacher account created.');
        redirect('/teachers/list.php');
    }
}

$pageTitle = 'Add Teacher';
require_once __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3">Add Teacher Account</h4>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="card p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Full Name *</label>
      <input type="text" name="full_name" class="form-control" required value="<?= e($_POST['full_name'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Username *</label>
      <input type="text" name="username" class="form-control" required value="<?= e($_POST['username'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Password *</label>
      <input type="password" name="password" class="form-control" required minlength="6">
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Subject *</label>
      <select name="subject" class="form-select" required>
        <option value="">Select</option>
        <option value="Math">Math</option>
        <option value="Science">Science</option>
        <option value="English">English</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Role</label>
      <select name="role" class="form-select">
        <option value="teacher">Teacher (manages only their own subject)</option>
        <option value="admin">Admin (full access, all subjects)</option>
      </select>
    </div>
  </div>
  <div class="mt-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Create Account</button>
    <a href="<?= BASE_URL ?>/teachers/list.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
