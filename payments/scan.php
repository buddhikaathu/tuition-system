<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Scan & Pay';
require_once __DIR__ . '/../includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-qr-code-scan"></i> Scan Student Card</h4>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card p-3">
      <p class="text-muted small">Point the camera at the QR code on the student's ID card. You can also type the student code manually below.</p>
      <div id="reader" style="width:100%;"></div>

      <form class="mt-3" onsubmit="return manualLookup(event)">
        <label class="form-label small">Or enter student code manually</label>
        <div class="input-group">
          <input type="text" id="manualCode" class="form-control" placeholder="e.g. TUT-0001">
          <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div id="resultBox" class="card p-4 text-center text-muted">
      <i class="bi bi-qr-code" style="font-size:2.5rem;"></i>
      <p class="mt-2 mb-0">Scan result will appear here.</p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const resultBox = document.getElementById('resultBox');
let scanning = true;

function renderLoading() {
  resultBox.innerHTML = '<div class="spinner-border text-primary" role="status"></div><p class="mt-2">Looking up student...</p>';
}

function renderError(msg) {
  resultBox.innerHTML = `<div class="alert alert-danger mb-0">${msg}</div>`;
}

function renderStudent(data) {
  let rows = data.enrollments.map(en => {
    const badgeClass = en.subject === 'Math' ? 'bg-primary' : en.subject === 'Science' ? 'bg-success' : 'bg-warning text-dark';
    const statusBadge = en.paid
      ? '<span class="badge bg-success">Paid</span>'
      : '<span class="badge bg-danger">Pending</span>';
    const payBtn = (!en.paid && en.can_manage)
      ? `<a class="btn btn-sm btn-success" href="<?= BASE_URL ?>/payments/add.php?student_id=${data.student_id}&subject=${en.subject}">Mark Paid</a>`
      : '';
    return `<tr>
      <td><span class="badge ${badgeClass}">${en.subject}</span></td>
      <td>${en.monthly_fee_fmt}</td>
      <td>${statusBadge}</td>
      <td class="text-end">${payBtn}</td>
    </tr>`;
  }).join('');

  resultBox.innerHTML = `
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h5 class="mb-0">${data.full_name}</h5>
        <div class="text-muted">${data.student_code} &middot; Grade ${data.grade}</div>
        <div class="text-muted small">${data.contact_number}</div>
      </div>
      <a href="<?= BASE_URL ?>/students/view.php?id=${data.student_id}" class="btn btn-sm btn-outline-primary">Full Profile</a>
    </div>
    <hr>
    <div class="text-start">
      <div class="small text-muted mb-1">${data.month_label} status:</div>
      <table class="table table-sm align-middle">
        <thead><tr><th>Subject</th><th>Fee</th><th>Status</th><th></th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
  `;
}

function lookup(code) {
  renderLoading();
  fetch('<?= BASE_URL ?>/payments/scan_lookup.php?code=' + encodeURIComponent(code))
    .then(r => r.json())
    .then(data => {
      if (data.error) {
        renderError(data.error);
      } else {
        renderStudent(data);
      }
    })
    .catch(() => renderError('Something went wrong looking up this student. Please try again.'));
}

function manualLookup(ev) {
  ev.preventDefault();
  const code = document.getElementById('manualCode').value.trim();
  if (code) lookup(code);
  return false;
}

// Camera scanner
const html5QrCode = new Html5Qrcode("reader");
Html5Qrcode.getCameras().then(cameras => {
  if (cameras && cameras.length) {
    const cameraId = cameras[0].id;
    html5QrCode.start(
      cameraId,
      { fps: 10, qrbox: 220 },
      (decodedText) => {
        if (scanning) {
          scanning = false;
          lookup(decodedText.trim());
          setTimeout(() => { scanning = true; }, 2500); // brief cooldown to avoid duplicate scans
        }
      },
      () => { /* ignore per-frame scan failures */ }
    ).catch(() => {
      resultBox.innerHTML = '<div class="alert alert-warning mb-0">Could not access the camera. You can still search by student code manually.</div>';
    });
  }
}).catch(() => {
  resultBox.innerHTML = '<div class="alert alert-warning mb-0">No camera found. You can still search by student code manually.</div>';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
