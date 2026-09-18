<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $teacher = $stmt->fetch();

    if ($teacher && $teacher['status'] === 'active' && password_verify($password, $teacher['password'])) {
        $_SESSION['user'] = [
            'id'        => $teacher['id'],
            'username'  => $teacher['username'],
            'full_name' => $teacher['full_name'],
            'subject'   => $teacher['subject'],
            'role'      => $teacher['role'],
        ];
        redirect('/index.php');
    } else {
        $error = 'Invalid username or password.';
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mt-5">
  <div class="col-md-5 col-lg-4">
    <div class="text-center mb-4">
      <i class="bi bi-mortarboard-fill" style="font-size:3rem;color:#2563eb;"></i>
      <h3 class="mt-2"><?= e(APP_NAME) ?></h3>
      <p class="text-muted">Sign in to manage students &amp; payments</p>
    </div>
    <div class="card">
      <div class="card-body p-4">
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
