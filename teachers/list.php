<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$teachers = $pdo->query("SELECT * FROM teachers ORDER BY id")->fetchAll();

$pageTitle = 'Teachers';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Teacher Accounts</h4>
  <a href="<?= BASE_URL ?>/teachers/add.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add Teacher</a>
</div>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th>Username</th><th>Name</th><th>Subject</th><th>Role</th><th>Phone</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($teachers as $t): ?>
        <tr>
          <td><?= e($t['username']) ?></td>
          <td><?= e($t['full_name']) ?></td>
          <td><span class="badge <?= subject_badge_class($t['subject']) ?>"><?= e($t['subject']) ?></span></td>
          <td><?= e(ucfirst($t['role'])) ?></td>
          <td><?= e($t['phone'] ?: '-') ?></td>
          <td><span class="badge bg-<?= $t['status']==='active'?'success':'secondary' ?>"><?= e($t['status']) ?></span></td>
          <td class="text-end"><a href="<?= BASE_URL ?>/teachers/edit.php?id=<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
