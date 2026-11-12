<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

// ---- Handle wallet balance update (POST) ---------------------------------
// The refund status dropdown still posts to update_status.php exactly as
// before. Only the new wallet editor posts back to this page, and it always
// ends in a redirect so a refresh never re-submits the form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnUrl = 'dashboard.php' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');

    if (!csrfCheck()) {
        $_SESSION['flash_error'] = 'Your session expired. Please try again.';
    } else {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $rawWallet = trim($_POST['wallet_balance'] ?? '');

        if ($studentId <= 0) {
            $_SESSION['flash_error'] = 'Invalid request. Please try again.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $rawWallet)) {
            $_SESSION['flash_error'] = 'Wallet balance must be a positive amount with up to 2 decimal places (e.g. 150000 or 150000.50).';
        } else {
            $lookup = $pdo->prepare('SELECT matric_number, full_name FROM students WHERE id = ?');
            $lookup->execute([$studentId]);
            $target = $lookup->fetch();

            if (!$target) {
                $_SESSION['flash_error'] = 'That student no longer exists.';
            } else {
                try {
                    $update = $pdo->prepare('UPDATE students SET wallet_balance = ? WHERE id = ?');
                    $update->execute([$rawWallet, $studentId]);

                    $adminName = $_SESSION['admin_name'] ?? $_SESSION['admin_email'];

                    logActivity(
                        $pdo,
                        $_SESSION['admin_id'],
                        $adminName,
                        sprintf(
                            'Admin %s set Matric [%s] wallet balance to NGN %s',
                            $adminName,
                            $target['matric_number'],
                            number_format((float)$rawWallet, 2)
                        ),
                        $target['matric_number']
                    );

                    $_SESSION['flash_success'] = sprintf(
                        '%s wallet balance updated to NGN %s.',
                        $target['full_name'],
                        number_format((float)$rawWallet, 2)
                    );
                } catch (PDOException $ex) {
                    $_SESSION['flash_error'] = 'Could not save the wallet balance. Has the ALTER TABLE for wallet_balance been run?';
                }
            }
        }
    }

    redirect($returnUrl);
}

$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

// Optional search/filter
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$sql = 'SELECT * FROM students WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (full_name LIKE ? OR matric_number LIKE ? OR email LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if (in_array($statusFilter, ['Pending', 'Approved', 'Rejected'], true)) {
    $sql .= ' AND refund_status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Quick counts for summary cards
$counts = $pdo->query(
    "SELECT
        SUM(refund_status = 'Pending')  AS pending_count,
        SUM(refund_status = 'Approved') AS approved_count,
        SUM(refund_status = 'Rejected') AS rejected_count,
        COUNT(*) AS total_count
     FROM students"
)->fetch();

$statusColors = [
    'Pending'  => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-300',
    'Approved' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-300',
    'Rejected' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-300',
];

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8">
  <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
      <h1 class="text-2xl font-bold text-fulafia-forest tracking-tight">Admin Dashboard</h1>
      <p class="text-slate-500">Review and manage student NELFUND refund submissions.</p>
    </div>
    <a href="export_csv.php"
       class="inline-flex items-center gap-2 bg-fulafia-emerald hover:bg-emerald-600 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
      </svg>
      Export Approved Refunds
    </a>
  </div>

  <?php if ($flashError): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-6">
      <?= e($flashError) ?>
    </div>
  <?php endif; ?>

  <?php if ($flashSuccess): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3 mb-6">
      <?= e($flashSuccess) ?>
    </div>
  <?php endif; ?>

  <!-- Summary cards -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-5 transition transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-slate-300/50">
      <div class="text-xs text-slate-400 uppercase font-semibold tracking-wider">Total</div>
      <div class="text-3xl font-bold text-fulafia-forest mt-1"><?= (int)$counts['total_count'] ?></div>
    </div>
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-5 transition transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-slate-300/50">
      <div class="text-xs text-slate-400 uppercase font-semibold tracking-wider">Pending</div>
      <div class="text-3xl font-bold text-amber-500 mt-1"><?= (int)$counts['pending_count'] ?></div>
    </div>
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-5 transition transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-slate-300/50">
      <div class="text-xs text-slate-400 uppercase font-semibold tracking-wider">Approved</div>
      <div class="text-3xl font-bold text-fulafia-emerald mt-1"><?= (int)$counts['approved_count'] ?></div>
    </div>
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-5 transition transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-slate-300/50">
      <div class="text-xs text-slate-400 uppercase font-semibold tracking-wider">Rejected</div>
      <div class="text-3xl font-bold text-red-500 mt-1"><?= (int)$counts['rejected_count'] ?></div>
    </div>
  </div>

  <!-- Search / filter -->
  <form method="GET" class="flex flex-wrap gap-3 mb-4">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, matric no, or email..."
           class="flex-1 min-w-[220px] bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
    <select name="status" class="bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm transition focus:ring-2 focus:ring-fulafia-emerald focus:outline-none">
      <option value="">All Statuses</option>
      <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
      <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
      <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
    </select>
    <button type="submit" class="bg-fulafia-forest hover:bg-fulafia-forest2 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
      Filter
    </button>
    <?php if ($search !== '' || $statusFilter !== ''): ?>
      <a href="dashboard.php" class="text-sm text-slate-500 self-center hover:underline">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Students table -->
  <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200 text-left">
        <tr>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Name</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Matric No.</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Email</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Bank</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Account No.</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Evidence</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Status</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Wallet (&#8358;)</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Payout</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Update</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($students)): ?>
          <tr><td colspan="10" class="px-4 py-10 text-center text-slate-400">No students found.</td></tr>
        <?php endif; ?>

        <?php foreach ($students as $i => $s): ?>
          <?php
            $bankName = trim((string)($s['bank_name'] ?? ''));
            $wallet   = (float)($s['wallet_balance'] ?? 0);
            // Exactly what gets copied for the bursary portal:
            $payout = implode(' | ', [
                $s['full_name'],
                $s['matric_number'],
                $bankName !== '' ? $bankName : 'N/A',
                $s['account_number'],
            ]);
          ?>
          <tr class="<?= $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' ?> hover:bg-emerald-50/50 transition">
            <td class="px-4 py-3 font-medium text-slate-800"><?= e($s['full_name']) ?></td>
            <td class="px-4 py-3 text-slate-600 font-mono text-xs"><?= e($s['matric_number']) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= e($s['email']) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= $bankName !== '' ? e($bankName) : '<span class="text-slate-400">&mdash;</span>' ?></td>
            <td class="px-4 py-3 text-slate-600 font-mono text-xs"><?= e($s['account_number']) ?></td>
            <td class="px-4 py-3">
              <?php if (!empty($s['nelfund_balance_img'])): ?>
                <button type="button"
                        onclick="openLightbox('../uploads/<?= e($s['nelfund_balance_img']) ?>', '<?= e(addslashes($s['full_name'])) ?>')"
                        class="text-fulafia-forest font-semibold hover:text-fulafia-emerald hover:underline underline-offset-2 transition">
                  View Image
                </button>
              <?php else: ?>
                <span class="text-slate-400 italic text-xs">Not uploaded</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3">
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusColors[$s['refund_status']] ?>">
                <?= e($s['refund_status']) ?>
              </span>
            </td>
            <td class="px-4 py-3">
              <form method="POST" class="flex items-center gap-1.5">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                <span class="text-slate-500 text-xs">&#8358;</span>
                <input type="number" name="wallet_balance" required min="0" max="99999999.99" step="0.01" inputmode="decimal"
                       value="<?= e(number_format($wallet, 2, '.', '')) ?>" title="Wallet balance (NGN)"
                       class="w-28 bg-white border border-slate-300 rounded-lg px-2 py-1.5 text-xs transition focus:ring-2 focus:ring-fulafia-emerald focus:outline-none">
                <button type="submit"
                        class="bg-fulafia-emerald hover:bg-emerald-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                  Save
                </button>
              </form>
            </td>
            <td class="px-4 py-3">
              <button type="button"
                      data-payout="<?= e($payout) ?>"
                      data-name="<?= e($s['full_name']) ?>"
                      onclick="copyPayout(this)"
                      class="bg-white border border-slate-300 hover:border-fulafia-emerald hover:text-fulafia-forest text-slate-600 text-xs font-semibold px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                &#128203; Copy Payout Info
              </button>
            </td>
            <td class="px-4 py-3">
              <form method="POST" action="update_status.php" class="flex items-center gap-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                <input type="hidden" name="return_query" value="<?= e($_SERVER['QUERY_STRING'] ?? '') ?>">
                <select name="refund_status"
                        class="bg-white border border-slate-300 rounded-lg px-2 py-1.5 text-xs transition focus:ring-2 focus:ring-fulafia-emerald focus:outline-none">
                  <option value="Pending" <?= $s['refund_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Approved" <?= $s['refund_status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                  <option value="Rejected" <?= $s['refund_status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
                <button type="submit"
                        class="bg-fulafia-forest hover:bg-fulafia-forest2 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                  Save
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- Lightbox -->
<div id="lightbox" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center z-50 p-4" onclick="closeLightbox(event)">
  <div class="max-w-3xl w-full">
    <div class="flex justify-between items-center mb-2">
      <span id="lightbox-title" class="text-white font-medium text-sm"></span>
      <button onclick="closeLightbox(event)" class="text-white text-2xl leading-none hover:text-emerald-300 transition">&times;</button>
    </div>
    <img id="lightbox-img" src="" alt="NELFUND balance evidence" class="w-full rounded-2xl border-4 border-white shadow-2xl">
  </div>
</div>

<!-- Copy confirmation toast -->
<div id="toast" role="status" aria-live="polite"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] max-w-[90vw] px-4 py-2.5 rounded-xl text-sm font-medium text-white shadow-2xl bg-fulafia-forest opacity-0 pointer-events-none transition-opacity duration-200"></div>

<script>
function openLightbox(src, name) {
  document.getElementById('lightbox-img').src = src;
  document.getElementById('lightbox-title').textContent = name + ' — NELFUND Balance Screenshot';
  const lb = document.getElementById('lightbox');
  lb.classList.remove('hidden');
  lb.classList.add('flex');
}
function closeLightbox(e) {
  if (e.target.id === 'lightbox' || e.target.tagName === 'BUTTON' || e.target.textContent === '×') {
    const lb = document.getElementById('lightbox');
    lb.classList.add('hidden');
    lb.classList.remove('flex');
  }
}

let toastTimer;
function showToast(message, isError) {
  const t = document.getElementById('toast');
  t.textContent = message;
  t.classList.toggle('bg-fulafia-forest', !isError);
  t.classList.toggle('bg-red-600', !!isError);
  t.classList.remove('opacity-0');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(function () { t.classList.add('opacity-0'); }, 2400);
}

// Copies "Full Name | Matric Number | Bank Name | Account Number".
function copyPayout(btn) {
  const text = btn.dataset.payout;
  const name = btn.dataset.name;
  // navigator.clipboard only works on HTTPS or 127.0.0.1/localhost, so
  // fall back to the older method on plain-HTTP addresses.
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(
      function () { showToast('Payout info copied for ' + name); },
      function () { fallbackCopy(text, name); }
    );
  } else {
    fallbackCopy(text, name);
  }
}
function fallbackCopy(text, name) {
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.setAttribute('readonly', '');
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  let ok = false;
  try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
  document.body.removeChild(ta);
  if (ok) {
    showToast('Payout info copied for ' + name);
  } else {
    showToast('Copy failed. Please copy the details manually.', true);
  }
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
