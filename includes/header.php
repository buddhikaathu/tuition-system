<?php
$u = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php if ($u): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php"><i class="bi bi-mortarboard-fill"></i> <?= e(APP_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav1">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav1">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/students/list.php"><i class="bi bi-people-fill"></i> Students</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/payments/list.php"><i class="bi bi-cash-coin"></i> Payments</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/payments/scan.php"><i class="bi bi-qr-code-scan"></i> Scan &amp; Pay</a></li>
        <?php if (is_admin()): ?>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/teachers/list.php"><i class="bi bi-person-badge"></i> Teachers</a></li>
        <?php endif; ?>
      </ul>
      <span class="navbar-text me-3">
        <?= e($u['full_name']) ?> <span class="badge bg-secondary"><?= e($u['subject']) ?></span>
      </span>
      <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>
<?php endif; ?>

<div class="container pb-5">
<?php foreach (get_flashes() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
    <?= e($f['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endforeach; ?>
