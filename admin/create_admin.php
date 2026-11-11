<?php
/**
 * admin/create_admin.php
 *
 * ONE-TIME SETUP SCRIPT.
 * Visit this file once in your browser to create the first admin
 * account, then DELETE THIS FILE from the server immediately.
 * Leaving it live would let anyone create admin accounts.
 */
require_once __DIR__ . '/../config.php';

$errors = [];
$success = false;

// ---- Security hardening ---------------------------------------------------
// This script is meant to run exactly once, before any admin exists. If
// someone forgets to delete it afterwards, this check turns it into a
// dead end instead of a standing "create unlimited unauthenticated Admin
// accounts" hole -- it refuses to do anything once at least one admin
// is already in the database.
$adminAlreadyExists = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;

if (!$adminAlreadyExists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullName === '') {
        $errors[] = 'Please enter a full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (empty($errors)) {
        // The very first (and, per the check above, only) admin account
        // this script can ever create is always a SuperAdmin, so someone
        // can access the Admin Management panel. Every admin created
        // afterwards goes through that panel instead and defaults to 'Admin'.
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare(
            'INSERT INTO admins (full_name, email, password, role) VALUES (?, ?, ?, "SuperAdmin")'
        );
        $insert->execute([$fullName, $email, $hash]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Admin Account</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          fulafia: {
            forest: '#0B3D2E',
            forest2: '#0F5132',
            emerald: '#10B981',
            slate: '#1E293B'
          }
        }
      }
    }
  }
</script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-fulafia-slate via-slate-800 to-slate-900 px-4 py-12">
<div class="max-w-md w-full">
  <div class="bg-amber-50 border border-amber-300 text-amber-800 text-sm rounded-xl p-3.5 mb-4 shadow-sm">
    <strong>Setup script:</strong> delete this file after creating your admin account.
    The account created here automatically becomes a <strong>SuperAdmin</strong>
    (able to manage other admin accounts). Every account created afterwards
    should go through the Manage Admins panel instead.
  </div>

  <div class="backdrop-blur-md bg-white/90 rounded-2xl shadow-xl shadow-black/30 border border-white/40 p-8">
    <img src="../logo.png" alt="FULAFIA Logo" class="h-14 w-14 object-contain block mx-auto mb-4 rounded-full bg-white p-1 shadow-sm">
    <h1 class="text-xl font-bold text-fulafia-slate mb-4 text-center">Create Admin Account</h1>

    <?php if ($adminAlreadyExists && !$success): ?>
      <div class="bg-slate-100 border border-slate-300 text-slate-600 text-sm rounded-lg p-4">
        An admin account already exists, so this setup script is now locked
        and will not create any more accounts. Use the
        <a href="login.php" class="underline font-medium">Admin Login</a> page,
        or ask a SuperAdmin to add you from the Manage Admins panel.
      </div>
    <?php elseif ($success): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3 mb-4">
        Admin account created. You can now <a href="login.php" class="underline font-medium">log in</a>.
        Please delete this file now.
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
          <ul class="list-disc pl-4 space-y-1">
            <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
          <input type="text" name="full_name" required class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-slate-500 focus:border-slate-500 focus:outline-none">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Admin Email</label>
          <input type="email" name="email" required class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-slate-500 focus:border-slate-500 focus:outline-none">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
          <input type="password" name="password" required minlength="8" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-slate-500 focus:border-slate-500 focus:outline-none">
        </div>
        <button type="submit" class="w-full bg-fulafia-slate hover:bg-slate-900 text-white font-semibold py-2.5 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
          Create Admin
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
