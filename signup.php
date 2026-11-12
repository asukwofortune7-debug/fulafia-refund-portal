<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['student_id'])) {
    redirect('dashboard.php');
}

// Traditional Nigerian commercial banks ONLY (no fintechs / microfinance banks).
// This one list drives both the dropdown below and the server-side
// whitelist check, so a tampered POST value can never be saved.
$banks = [
    'Access Bank',
    'Citibank Nigeria',
    'Ecobank Nigeria',
    'Fidelity Bank',
    'First Bank of Nigeria',
    'First City Monument Bank (FCMB)',
    'Globus Bank',
    'Guaranty Trust Bank (GTBank)',
    'Keystone Bank',
    'Optimus Bank',
    'Parallex Bank',
    'Polaris Bank',
    'PremiumTrust Bank',
    'Providus Bank',
    'Signature Bank',
    'Stanbic IBTC Bank',
    'Standard Chartered Bank',
    'Sterling Bank',
    'SunTrust Bank',
    'Titan Trust Bank',
    'Union Bank of Nigeria',
    'United Bank for Africa (UBA)',
    'Unity Bank',
    'Wema Bank',
    'Zenith Bank',
];

$errors = [];
$old = ['full_name' => '', 'matric_number' => '', 'email' => '', 'bank_name' => '', 'account_number' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old['full_name']      = trim($_POST['full_name'] ?? '');
        $old['matric_number']  = trim($_POST['matric_number'] ?? '');
        $old['email']          = trim($_POST['email'] ?? '');
        $old['bank_name']      = trim($_POST['bank_name'] ?? '');
        $old['account_number'] = trim($_POST['account_number'] ?? '');
        $password              = $_POST['password'] ?? '';
        $confirmPassword       = $_POST['confirm_password'] ?? '';

        if ($old['full_name'] === '') {
            $errors[] = 'Full name is required.';
        }
        if ($old['matric_number'] === '') {
            $errors[] = 'Matriculation number is required.';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!in_array($old['bank_name'], $banks, true)) {
            $errors[] = 'Please select your bank from the list.';
        }
        if (!preg_match('/^\d{10}$/', $old['account_number'])) {
            $errors[] = 'Account number must be exactly 10 digits.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            // Check email separately (it has its own UNIQUE constraint and
            // deserves a message that's specific to it).
            $stmt = $pdo->prepare('SELECT id FROM students WHERE email = ?');
            $stmt->execute([$old['email']]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email address already exists.';
            }
        }

        if (empty($errors)) {
            // Explicit duplicate check for matric number OR account number,
            // per the requested validation rule.
            $stmt = $pdo->prepare('SELECT id FROM students WHERE matric_number = ? OR account_number = ?');
            $stmt->execute([$old['matric_number'], $old['account_number']]);
            if ($stmt->fetch()) {
                $errors[] = 'This Matriculation Number or Account Number is already registered.';
            }
        }

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // wallet_balance (0.00) and refund_status ('Pending') are
                // filled in by the column defaults.
                $stmt = $pdo->prepare(
                    'INSERT INTO students (matric_number, full_name, email, bank_name, account_number, password)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $old['matric_number'],
                    $old['full_name'],
                    $old['email'],
                    $old['bank_name'],
                    $old['account_number'],
                    $hash,
                ]);

                $_SESSION['flash_success'] = 'Account created successfully. Please log in.';
                redirect('login.php');
            } catch (PDOException $ex) {
                // Defense in depth: if two signups race past the checks above
                // at the exact same moment, the database's own UNIQUE
                // constraints (matric_number, email) still catch it here
                // instead of leaking a raw SQL error to the page.
                if ($ex->getCode() === '23000') {
                    $errors[] = 'This Matriculation Number, Email, or Account Number is already registered.';
                } else {
                    $errors[] = 'Something went wrong while creating your account. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Create Account';
require __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-64px)] flex items-center justify-center bg-gradient-to-br from-fulafia-forest via-fulafia-forest2 to-emerald-800 px-4 py-12">
  <div class="w-full max-w-md backdrop-blur-md bg-white/85 rounded-2xl shadow-xl shadow-black/20 border border-white/40 p-8">
    <img src="logo.png" alt="FULAFIA Logo" class="h-14 w-14 object-contain block mx-auto mb-4 rounded-full bg-white p-1 shadow-sm">
    <h1 class="text-xl font-bold text-fulafia-forest mb-1 text-center">Create Your Account</h1>
    <p class="text-sm text-slate-500 mb-6 text-center">Register to submit your NELFUND refund request.</p>

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
        <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
        <input type="text" name="full_name" required value="<?= e($old['full_name']) ?>"
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Matriculation Number</label>
        <input type="text" name="matric_number" required value="<?= e($old['matric_number']) ?>"
               placeholder="e.g. 2011XXXXX9"
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
        <input type="email" name="email" required value="<?= e($old['email']) ?>"
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Bank Name</label>
        <select name="bank_name" required
                class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
          <option value="">Select your bank</option>
          <?php foreach ($banks as $bank): ?>
            <option value="<?= e($bank) ?>" <?= $old['bank_name'] === $bank ? 'selected' : '' ?>><?= e($bank) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-400 mt-1">Commercial banks only &mdash; fintech and microfinance accounts are not supported.</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Bank Account Number</label>
        <input type="text" name="account_number" required value="<?= e($old['account_number']) ?>"
               maxlength="10" placeholder="10-digit NUBAN"
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
        <input type="password" name="password" required minlength="8"
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
        <input type="password" name="confirm_password" required minlength="8"
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
      </div>

      <button type="submit"
              class="w-full bg-fulafia-forest hover:bg-fulafia-forest2 text-white font-semibold py-2.5 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
        Create Account
      </button>
    </form>

    <p class="text-sm text-slate-500 mt-5 text-center">
      Already have an account? <a href="login.php" class="text-fulafia-forest font-medium hover:underline">Log in</a>
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
