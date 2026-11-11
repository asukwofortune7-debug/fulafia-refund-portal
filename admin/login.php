<?php
require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $errors[] = 'Please enter both email and password.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']    = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_name']  = $admin['full_name'];
                $_SESSION['admin_role']  = $admin['role'];

                logActivity($pdo, $admin['id'], $admin['full_name'], 'Logged in');

                redirect('dashboard.php');
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}

$pageTitle = 'Admin Login';
require __DIR__ . '/../includes/header.php';
?>

<div class="min-h-[calc(100vh-64px)] flex items-center justify-center bg-gradient-to-br from-fulafia-slate via-slate-800 to-slate-900 px-4 py-16">
  <div class="w-full max-w-md backdrop-blur-md bg-white/85 rounded-2xl shadow-xl shadow-black/30 border border-white/40 p-8">
    <img src="<?= $root ?>logo.png" alt="FULAFIA Logo" class="h-14 w-14 object-contain block mx-auto mb-4 rounded-full bg-white p-1 shadow-sm">
    <div class="flex items-center justify-center gap-2 mb-1">
      <span class="text-[11px] font-bold uppercase tracking-wider text-fulafia-slate bg-slate-200 px-2.5 py-1 rounded-full">Restricted Access</span>
    </div>
    <h1 class="text-xl font-bold text-fulafia-slate mb-1 text-center">Admin Login</h1>
    <p class="text-sm text-slate-500 mb-6 text-center">Department staff only.</p>

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

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
        <input type="email" name="email" required value="<?= e($email) ?>"
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-slate-500 focus:border-slate-500 focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
        <input type="password" name="password" required
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-slate-500 focus:border-slate-500 focus:outline-none">
      </div>

      <button type="submit"
              class="w-full bg-fulafia-slate hover:bg-slate-900 text-white font-semibold py-2.5 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
        Log In as Admin
      </button>
    </form>

    <p class="text-sm text-slate-500 mt-5 text-center">
      <a href="<?= $root ?>login.php" class="text-fulafia-forest font-medium hover:underline">&larr; Student Login</a>
    </p>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
