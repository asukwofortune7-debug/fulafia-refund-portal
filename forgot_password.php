<?php
require_once __DIR__ . '/config.php';

$errors = [];
$success = false;

/**
 * Sends the password reset email. Uses PHP's built-in mail().
 * On cPanel this typically works without extra configuration since
 * the mail server runs on the same host. If your host needs SMTP
 * authentication, replace the body of this function with a PHPMailer
 * call instead.
 */
function sendResetEmail(string $toEmail, string $toName, string $resetLink): bool
{
    $subject = 'Password Reset - ' . SITE_NAME;
    $message = "Hello {$toName},\r\n\r\n"
        . "We received a request to reset your password for the FULAFIA NELFUND Refund Portal.\r\n\r\n"
        . "Click the link below to set a new password. This link expires in 1 hour:\r\n"
        . "{$resetLink}\r\n\r\n"
        . "If you did not request this, you can safely ignore this email.\r\n\r\n"
        . "- " . SITE_NAME;

    $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">\r\n"
        . "Reply-To: " . MAIL_FROM_ADDRESS . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($toEmail, $subject, $message, $headers);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $stmt = $pdo->prepare('SELECT id, full_name, email FROM students WHERE email = ?');
            $stmt->execute([$email]);
            $student = $stmt->fetch();

            // Always show the same success message whether or not the
            // email exists, so we don't leak which emails are registered.
            if ($student) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $update = $pdo->prepare(
                    'UPDATE students SET reset_token = ?, reset_token_expires = ? WHERE id = ?'
                );
                $update->execute([$token, $expires, $student['id']]);

                $resetLink = BASE_URL . '/reset_password.php?token=' . $token;
                sendResetEmail($student['email'], $student['full_name'], $resetLink);
            }

            $success = true;
        }
    }
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-64px)] flex items-center justify-center bg-gradient-to-br from-fulafia-forest via-fulafia-forest2 to-emerald-800 px-4 py-16">
  <div class="w-full max-w-md backdrop-blur-md bg-white/85 rounded-2xl shadow-xl shadow-black/20 border border-white/40 p-8">
    <img src="logo.png" alt="FULAFIA Logo" class="h-14 w-14 object-contain block mx-auto mb-4 rounded-full bg-white p-1 shadow-sm">
    <h1 class="text-xl font-bold text-fulafia-forest mb-1 text-center">Forgot Password</h1>
    <p class="text-sm text-slate-500 mb-6 text-center">Enter your registered email and we'll send you a reset link.</p>

    <?php if ($success): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3 mb-4">
        If that email is registered, a password reset link has been sent. Please check your inbox
        (and spam folder).
      </div>
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

    <?php if (!$success): ?>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
        <input type="email" name="email" required
               class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 transition focus:ring-2 focus:ring-fulafia-emerald focus:border-fulafia-emerald focus:outline-none">
      </div>
      <button type="submit"
              class="w-full bg-fulafia-forest hover:bg-fulafia-forest2 text-white font-semibold py-2.5 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
        Send Reset Link
      </button>
    </form>
    <?php endif; ?>

    <p class="text-sm text-slate-500 mt-5 text-center">
      <a href="login.php" class="text-fulafia-forest font-medium hover:underline">&larr; Back to Login</a>
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
