<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

// Optional filters
$search = trim($_GET['search'] ?? '');
$adminFilter = $_GET['admin_id'] ?? '';

$sql = 'SELECT * FROM audit_logs WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (action_performed LIKE ? OR admin_name LIKE ? OR affected_student_matric LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($adminFilter !== '' && ctype_digit($adminFilter)) {
    $sql .= ' AND admin_id = ?';
    $params[] = (int)$adminFilter;
}

$sql .= ' ORDER BY timestamp DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// For the filter dropdown
$adminsList = $pdo->query('SELECT id, full_name FROM admins ORDER BY full_name ASC')->fetchAll();

$pageTitle = 'Activity Log';
require __DIR__ . '/../includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold text-fulafia-forest mb-1 tracking-tight">Administrative Activity Log</h1>
  <p class="text-slate-500 mb-6">Audit trail of admin logins, student status changes, and account management.</p>

  <!-- Search / filter -->
  <form method="GET" class="flex flex-wrap gap-3 mb-4">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search action, admin, or matric no..."
           class="flex-1 min-w-[220px] bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
    <select name="admin_id" class="bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm transition focus:ring-2 focus:ring-fulafia-emerald focus:outline-none">
      <option value="">All Admins</option>
      <?php foreach ($adminsList as $a): ?>
        <option value="<?= (int)$a['id'] ?>" <?= (string)$adminFilter === (string)$a['id'] ? 'selected' : '' ?>>
          <?= e($a['full_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="bg-fulafia-forest hover:bg-fulafia-forest2 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
      Filter
    </button>
    <?php if ($search !== '' || $adminFilter !== ''): ?>
      <a href="activity_log.php" class="text-sm text-slate-500 self-center hover:underline">Clear</a>
    <?php endif; ?>
  </form>

  <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200 text-left">
        <tr>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Timestamp</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Admin</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Action</th>
          <th class="px-4 py-3 font-semibold text-slate-500 text-xs uppercase tracking-wider">Matric No.</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($logs)): ?>
          <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">No activity recorded yet.</td></tr>
        <?php endif; ?>

        <?php foreach ($logs as $i => $log): ?>
          <tr class="<?= $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' ?> hover:bg-emerald-50/50 transition">
            <td class="px-4 py-3 text-slate-500 whitespace-nowrap text-xs">
              <?= e(date('d M Y, g:i A', strtotime($log['timestamp']))) ?>
            </td>
            <td class="px-4 py-3 font-medium text-slate-800 whitespace-nowrap">
              <?= e($log['admin_name'] ?? 'Deleted Admin') ?>
            </td>
            <td class="px-4 py-3 text-slate-600"><?= e($log['action_performed']) ?></td>
            <td class="px-4 py-3 text-slate-500 font-mono text-xs">
              <?= $log['affected_student_matric'] ? e($log['affected_student_matric']) : '—' ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

  <?php if (count($logs) === 200): ?>
    <p class="text-xs text-slate-400 mt-3">Showing the most recent 200 entries. Narrow your search to see older records.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
