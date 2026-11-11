<?php
require_once __DIR__ . '/../config.php';
requireSuperAdminLogin();

$errors = [];
$success = null;

// ---- Handle "Create Admin" submission ------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!csrfCheck()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (!in_array($role, ['Admin', 'SuperAdmin'], true)) {
            $errors[] = 'Please select a valid role.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An admin with that email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare(
                    'INSERT INTO admins (full_name, email, password, role) VALUES (?, ?, ?, ?)'
                );
                $insert->execute([$fullName, $email, $hash, $role]);

                logActivity(
                    $pdo,
                    $_SESSION['admin_id'],
                    $_SESSION['admin_name'],
                    sprintf('SuperAdmin %s created new admin account: %s (%s) with role %s', $_SESSION['admin_name'], $fullName, $email, $role)
                );

                $success = 'Admin account for "' . $fullName . '" was created successfully as ' . $role . '.';
            }
        }
    }
}

// ---- Handle "Delete Admin" submission -------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!csrfCheck()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $targetId = (int)($_POST['admin_id'] ?? 0);

        $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();

        if (!$target) {
            $errors[] = 'That admin account no longer exists.';
        } elseif ($target['id'] === (int)$_SESSION['admin_id']) {
            $errors[] = 'You cannot delete your own account.';
        } elseif ($target['role'] === 'SuperAdmin') {
            $errors[] = 'SuperAdmin accounts cannot be deleted from this panel.';
        } else {
            $delete = $pdo->prepare('DELETE FROM admins WHERE id = ?');
            $delete->execute([$targetId]);

            logActivity(
                $pdo,
                $_SESSION['admin_id'],
                $_SESSION['admin_name'],
                sprintf('SuperAdmin %s deleted admin account: %s (%s)', $_SESSION['admin_name'], $target['full_name'], $target['email'])
            );

            $success = 'Admin account for "' . $target['full_name'] . '" was deleted.';
        }
    }
}

$admins = $pdo->query('SELECT * FROM admins ORDER BY role DESC, full_name ASC')->fetchAll();

$pageTitle = 'Manage Admins';
require __DIR__ . '/../includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold text-fulafia-forest mb-1 tracking-tight">Manage Admins</h1>
  <p class="text-slate-500 mb-6">SuperAdmin console &mdash; create and remove administrative accounts.</p>

  <?php if ($success): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3 mb-6">
      <?= e($success) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-6">
      <ul class="list-disc pl-4 space-y-1">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="grid md:grid-cols-3 gap-6">
    <!-- Existing admins table -->
    <div class="md:col-span-2 bg-white rounded-2xl shadow-xl shadow-slate-200/50 overflow-hidden">
      <div class="px-5 py-3.5 border-b border-slate-100">
        <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Administrative Accounts</h2>
      </div>
      <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-left">
          <tr>
            <th class="px-4 py-2.5 font-semibold text-slate-500 text-xs uppercase tracking-wider">Name</th>
            <th class="px-4 py-2.5 font-semibold text-slate-500 text-xs uppercase tracking-wider">Email</th>
            <th class="px-4 py-2.5 font-semibold text-slate-500 text-xs uppercase tracking-wider">Role</th>
            <th class="px-4 py-2.5 font-semibold text-slate-500 text-xs uppercase tracking-wider"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($admins as $i => $a): ?>
            <tr class="<?= $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' ?> hover:bg-emerald-50/50 transition">
              <td class="px-4 py-3 font-medium text-slate-800">
                <?= e($a['full_name']) ?>
                <?php if ((int)$a['id'] === (int)$_SESSION['admin_id']): ?>
                  <span class="text-xs text-slate-400">(you)</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-slate-600"><?= e($a['email']) ?></td>
              <td class="px-4 py-3">
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                  <?= $a['role'] === 'SuperAdmin' ? 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-300' : 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200' ?>">
                  <?= e($a['role']) ?>
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <?php if ($a['role'] !== 'SuperAdmin' && (int)$a['id'] !== (int)$_SESSION['admin_id']): ?>
                  <form method="POST" onsubmit="return confirm('Delete admin account for <?= e(addslashes($a['full_name'])) ?>? This cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="admin_id" value="<?= (int)$a['id'] ?>">
                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-semibold underline underline-offset-2">
                      Delete
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

    <!-- Create admin form -->
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-5 h-fit">
      <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Create New Admin</h2>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="create">

        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Full Name</label>
          <input type="text" name="full_name" required
                 class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Email</label>
          <input type="email" name="email" required
                 class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Password</label>
          <input type="password" name="password" required minlength="8"
                 class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Role</label>
          <select name="role" required
                  class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm transition focus:ring-2 focus:ring-fulafia-emerald focus:outline-none">
            <option value="Admin" selected>Admin</option>
            <option value="SuperAdmin">SuperAdmin</option>
          </select>
        </div>
        <button type="submit"
                class="w-full bg-fulafia-forest hover:bg-fulafia-forest2 text-white font-semibold py-2.5 rounded-lg text-sm shadow-md transition transform hover:-translate-y-0.5">
          Create Admin
        </button>
        <p class="text-xs text-slate-400">SuperAdmins can manage other admin accounts; regular Admins cannot.</p>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
