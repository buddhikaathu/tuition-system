<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$u = current_user();
$search = trim($_GET['q'] ?? '');
$gradeFilter = $_GET['grade'] ?? '';

$sql = "SELECT s.*,
        GROUP_CONCAT(DISTINCT e.subject ORDER BY e.subject SEPARATOR ',') AS subjects
        FROM students s
        LEFT JOIN enrollments e ON e.student_id = s.id AND e.status='active'
        WHERE 1=1";
$params = [];

if (!is_admin()) {
    // Teacher only sees students enrolled in their own subject
    $sql .= " AND s.id IN (SELECT student_id FROM enrollments WHERE subject = :subj AND status='active')";
    $params[':subj'] = $u['subject'];
}

if ($search !== '') {
    $sql .= " AND (s.full_name LIKE :q OR s.student_code LIKE :q OR s.contact_number LIKE :q)";
    $params[':q'] = "%$search%";
}

if ($gradeFilter !== '') {
    $sql .= " AND s.grade = :grade";
    $params[':grade'] = (int) $gradeFilter;
}

$sql .= " GROUP BY s.id ORDER BY s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = 'Students';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Students</h4>
  <a href="<?= BASE_URL ?>/students/add.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add Student</a>
</div>

<div class="card p-3 mb-3">
  <form class="row g-2" method="get">
    <div class="col-md-6">
      <input type="text" name="q" class="form-control" placeholder="Search by name, code or phone..." value="<?= e($search) ?>">
    </div>
    <div class="col-md-3">
      <select name="grade" class="form-select">
        <option value="">All Grades</option>
        <?php for ($g = 4; $g <= 11; $g++): ?>
          <option value="<?= $g ?>" <?= $gradeFilter == $g ? 'selected' : '' ?>>Grade <?= $g ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-3">
      <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-search"></i> Filter</button>
    </div>
  </form>
</div>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>Code</th><th>Name</th><th>Grade</th><th>Subjects</th><th>Contact</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$students): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No students found.</td></tr>
      <?php endif; ?>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><span class="fw-semibold"><?= e($s['student_code']) ?></span></td>
          <td><?= e($s['full_name']) ?></td>
          <td>Grade <?= (int) $s['grade'] ?></td>
          <td>
            <?php foreach (explode(',', $s['subjects'] ?? '') as $subj): if (!$subj) continue; ?>
              <span class="badge <?= subject_badge_class($subj) ?>"><?= e($subj) ?></span>
            <?php endforeach; ?>
          </td>
          <td><?= e($s['contact_number']) ?></td>
          <td>
            <span class="badge bg-<?= $s['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($s['status']) ?></span>
          </td>
          <td class="text-end">
            <a href="<?= BASE_URL ?>/students/view.php?id=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
            <a href="<?= BASE_URL ?>/students/card.php?id=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-dark" title="ID Card / QR"><i class="bi bi-qr-code"></i></a>
            <a href="<?= BASE_URL ?>/students/edit.php?id=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
