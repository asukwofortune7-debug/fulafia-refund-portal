<?php
require_once __DIR__ . '/config.php';

$errors = [];
$success = false;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

$student = null;
if ($token !== '') {
    $stmt = $pdo->prepare(
        'SELECT id, full_name, reset_token_expires FROM students
         WHERE reset_token = ? LIMIT 1'
    );
    $stmt->execute([$token]);
    $student = $stmt->fetch();

    if ($student && strtotime($student['reset_token_expires']) < time()) {
        $student = null; // expired
        $errors[] = 'This reset link has expired. Please request a new one.';
    } elseif (!$student) {
        $errors[] = 'This reset link is invalid. Please request a new one.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $student) {
    if (!csrfCheck()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare(
                'UPDATE students SET password = ?, reset_token = NULL, reset_token_expires = NULL
                 WHERE id = ?'
            );
            $update->execute([$hash, $student['id']]);
            $success = true;
        }
    }
}

$pageTitle = 'Reset Password';
require __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-64px)] flex items-center justify-center bg-gradient-to-br from-fulafia-forest via-fulafia-forest2 to-emerald-800 px-4 py-16">
  <div class="w-full max-w-md backdrop-blur-md bg-white/85 rounded-2xl shadow-xl shadow-black/20 border border-white/40 p-8">
    <img src="logo.png" alt="FULAFIA Logo" class="h-14 w-14 object-contain block mx-auto mb-4 rounded-full bg-white p-1 shadow-sm">
    <h1 class="text-xl font-bold text-fulafia-forest mb-1 text-center">Reset Password</h1>

    <?php if ($success): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3 mt-4 mb-2">
        Your password has been updated successfully.
      </div>
      <p class="text-sm mt-4 text-center">
        <a href="login.php" class="text-fulafia-forest font-medium hover:underline">Go to Login &rarr;</a>
      </p>

    <?php elseif ($student): ?>
      <p class="text-sm text-slate-500 mb-6 text-center">Hello <?= e($student['full_name']) ?>, choose a new password.</p>

      <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
          <ul class="list-disc pl-4 space-y-1">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
          <input type="password" name="password" required minlength="8"
                 class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Confirm New Password</label>
          <input type="password" name="confirm_password" required minlength="8"
                 class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
        </div>

        <button type="submit"
                class="w-full bg-fulafia-forest hover:bg-fulafia-forest2 text-white font-semibold py-2.5 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
          Update Password
        </button>
      </form>

    <?php else: ?>
      <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mt-4 mb-4">
        <ul class="list-disc pl-4 space-y-1">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <p class="text-sm text-center">
        <a href="forgot_password.php" class="text-fulafia-forest font-medium hover:underline">Request a new reset link</a>
      </p>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
