<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$u = current_user();
$errors = [];

// Teachers grouped by subject, for the enrollment dropdowns
$teachersBySubject = ['Math' => [], 'Science' => [], 'English' => []];
foreach ($pdo->query("SELECT id, full_name, subject FROM teachers WHERE status='active'") as $t) {
    $teachersBySubject[$t['subject']][] = $t;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $full_name = trim($_POST['full_name'] ?? '');
    $grade = (int) ($_POST['grade'] ?? 0);
    $parent_name = trim($_POST['parent_name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $enrollment_date = $_POST['enrollment_date'] ?? date('Y-m-d');
    $chosenSubjects = $_POST['subjects'] ?? []; // array of subject => on
    $fees = $_POST['fee'] ?? [];                // subject => amount
    $teacherChoice = $_POST['teacher'] ?? [];    // subject => teacher_id

    if ($full_name === '') $errors[] = 'Student name is required.';
    if ($grade < 4 || $grade > 11) $errors[] = 'Please select a valid grade (4-11).';
    if ($contact_number === '') $errors[] = 'Contact number is required.';

    $allowedSubjects = subjects_for_grade($grade);
    $selected = array_intersect(array_keys($chosenSubjects), $allowedSubjects);

    // Non-admin teachers can only enroll a student into their own subject on creation
    if (!is_admin()) {
        $selected = array_intersect($selected, [$u['subject']]);
    }

    if (!$selected) $errors[] = 'Please select at least one subject to enroll the student in.';

    foreach ($selected as $subj) {
        if (empty($teacherChoice[$subj])) $errors[] = "Please choose a teacher for $subj.";
        if (!isset($fees[$subj]) || $fees[$subj] === '' || (float) $fees[$subj] < 0) {
            $errors[] = "Please enter a valid monthly fee for $subj.";
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $code = generate_student_code($pdo);

            $stmt = $pdo->prepare("INSERT INTO students
                (student_code, full_name, grade, parent_name, contact_number, address, enrollment_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $full_name, $grade, $parent_name, $contact_number, $address, $enrollment_date, $u['id']]);
            $studentId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, subject, teacher_id, monthly_fee, start_date)
                VALUES (?, ?, ?, ?, ?)");
            foreach ($selected as $subj) {
                $stmt->execute([$studentId, $subj, (int) $teacherChoice[$subj], (float) $fees[$subj], $enrollment_date]);
            }

            $pdo->commit();
            flash('success', "Student added successfully. Student code: $code");
            redirect('/students/card.php?id=' . $studentId);
        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong: ' . $ex->getMessage();
        }
    }
}

$pageTitle = 'Add Student';
require_once __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3">Add New Student</h4>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="card p-4" id="studentForm">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Full Name *</label>
      <input type="text" name="full_name" class="form-control" required value="<?= e($_POST['full_name'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Grade *</label>
      <select name="grade" id="gradeSelect" class="form-select" required>
        <option value="">Select</option>
        <?php for ($g = 4; $g <= 11; $g++): ?>
          <option value="<?= $g ?>" <?= (isset($_POST['grade']) && $_POST['grade'] == $g) ? 'selected' : '' ?>>Grade <?= $g ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Enrollment Date</label>
      <input type="date" name="enrollment_date" class="form-control" value="<?= e($_POST['enrollment_date'] ?? date('Y-m-d')) ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Contact Number *</label>
      <input type="text" name="contact_number" class="form-control" required value="<?= e($_POST['contact_number'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Parent / Guardian Name</label>
      <input type="text" name="parent_name" class="form-control" value="<?= e($_POST['parent_name'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Address</label>
      <textarea name="address" class="form-control" rows="2"><?= e($_POST['address'] ?? '') ?></textarea>
    </div>
  </div>

  <hr class="my-4">
  <h5>Subject Enrollment</h5>
  <p class="text-muted small">Math is offered for grades 4-11. Science &amp; English are offered for grades 10-11 only. Available subjects appear automatically once you pick a grade.</p>

  <div id="subjectBlocks">
    <?php foreach (['Math', 'Science', 'English'] as $subj): ?>
      <div class="subject-block border rounded p-3 mb-2 d-none" data-subject="<?= $subj ?>">
        <div class="form-check mb-2">
          <input class="form-check-input subject-check" type="checkbox" name="subjects[<?= $subj ?>]" id="chk<?= $subj ?>" value="1"
            <?= (!is_admin() && $u['subject'] !== $subj) ? 'disabled' : '' ?>>
          <label class="form-check-label fw-semibold" for="chk<?= $subj ?>">
            <span class="badge <?= subject_badge_class($subj) ?>"><?= $subj ?></span> Enroll in <?= $subj ?>
          </label>
        </div>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label small">Teacher</label>
            <select name="teacher[<?= $subj ?>]" class="form-select form-select-sm">
              <option value="">Select teacher</option>
              <?php foreach ($teachersBySubject[$subj] as $t): ?>
                <option value="<?= (int) $t['id'] ?>"><?= e($t['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small">Monthly Fee (Rs.)</label>
            <input type="number" step="0.01" min="0" name="fee[<?= $subj ?>]" class="form-control form-control-sm" placeholder="e.g. 3000">
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Student</button>
    <a href="<?= BASE_URL ?>/students/list.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<script>
const gradeSelect = document.getElementById('gradeSelect');
const blocks = document.querySelectorAll('.subject-block');

function updateSubjectVisibility() {
  const grade = parseInt(gradeSelect.value || '0', 10);
  const allowed = [];
  if (grade >= 4 && grade <= 11) allowed.push('Math');
  if (grade >= 10 && grade <= 11) { allowed.push('Science'); allowed.push('English'); }

  blocks.forEach(block => {
    const subj = block.dataset.subject;
    if (allowed.includes(subj)) {
      block.classList.remove('d-none');
    } else {
      block.classList.add('d-none');
      block.querySelector('.subject-check').checked = false;
    }
  });
}

gradeSelect.addEventListener('change', updateSubjectVisibility);
updateSubjectVisibility();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
