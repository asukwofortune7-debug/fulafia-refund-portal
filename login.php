<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['student_id'])) {
    redirect('dashboard.php');
}

$errors = [];
$matricNumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $matricNumber = trim($_POST['matric_number'] ?? '');
        $password     = $_POST['password'] ?? '';

        if ($matricNumber === '' || $password === '') {
            $errors[] = 'Please enter both your matric number and password.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM students WHERE matric_number = ?');
            $stmt->execute([$matricNumber]);
            $student = $stmt->fetch();

            if ($student && password_verify($password, $student['password'])) {
                session_regenerate_id(true);
                $_SESSION['student_id']   = $student['id'];
                $_SESSION['student_name'] = $student['full_name'];
                redirect('dashboard.php');
            } else {
                $errors[] = 'Invalid matriculation number or password.';
            }
        }
    }
}

$pageTitle = 'Student Login';
require __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-64px)] flex items-center justify-center bg-gradient-to-br from-fulafia-forest via-fulafia-forest2 to-emerald-800 px-4 py-16">
  <div class="w-full max-w-md backdrop-blur-md bg-white/85 rounded-2xl shadow-xl shadow-black/20 border border-white/40 p-8">
    <img src="logo.png" alt="FULAFIA Logo" class="h-14 w-14 object-contain block mx-auto mb-4 rounded-full bg-white p-1 shadow-sm">
    <h1 class="text-xl font-bold text-fulafia-forest mb-1 text-center">Student Login</h1>
    <p class="text-sm text-slate-500 mb-6 text-center">Access your NELFUND refund dashboard.</p>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3 mb-4">
        <?= e($_SESSION['flash_success']) ?>
      </div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

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
        <label class="block text-sm font-medium text-slate-700 mb-1">Matriculation Number</label>
        <input type="text" name="matric_number" required value="<?= e($matricNumber) ?>"
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
        <input type="password" name="password" required
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
      </div>

      <button type="submit"
              class="w-full bg-fulafia-forest hover:bg-fulafia-forest2 text-white font-semibold py-2.5 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
        Log In
      </button>
    </form>

    <div class="flex items-center justify-between mt-5 text-sm">
      <a href="forgot_password.php" class="text-amber-700 font-medium hover:underline">Forgot Password?</a>
      <a href="signup.php" class="text-fulafia-forest font-medium hover:underline">Create Account</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
