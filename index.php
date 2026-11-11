<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['student_id'])) {
    redirect('dashboard.php');
}

$pageTitle = 'Welcome';
require __DIR__ . '/includes/header.php';
?>

<div class="relative overflow-hidden bg-gradient-to-br from-fulafia-forest via-fulafia-forest2 to-emerald-800">
  <div class="absolute inset-0 opacity-[0.07] bg-[radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] bg-[size:24px_24px]"></div>
  <div class="relative max-w-3xl mx-auto px-4 py-20 sm:py-28 text-center">
    <img src="logo.png" alt="FULAFIA Logo" class="h-24 w-24 object-contain block mx-auto mb-6 rounded-full bg-white p-2 shadow-xl shadow-black/20">
    <h1 class="text-2xl sm:text-4xl font-bold text-white mb-3 tracking-tight">FULAFIA NELFUND Refund Portal</h1>
    <p class="text-emerald-100/90 mb-10 max-w-xl mx-auto leading-relaxed">
      Submit your NELFUND balance evidence and track the status of your tuition refund
      online &mdash; no need to visit the department in person.
    </p>

    <div class="grid sm:grid-cols-2 gap-4 max-w-md mx-auto backdrop-blur-md bg-white/10 border border-white/15 rounded-2xl p-4 shadow-card">
      <a href="login.php" class="bg-white hover:bg-emerald-50 text-fulafia-forest font-semibold py-3 px-6 rounded-xl shadow-md transition transform hover:-translate-y-0.5">
        Student Login
      </a>
      <a href="signup.php" class="bg-emerald-500/20 border-2 border-emerald-300/60 text-white hover:bg-emerald-500/30 font-semibold py-3 px-6 rounded-xl transition transform hover:-translate-y-0.5">
        Create Account
      </a>
    </div>

    <div class="mt-8">
      <a href="admin/login.php" class="text-sm text-emerald-100/70 hover:text-white underline underline-offset-4">Admin Login</a>
    </div>
  </div>
</div>

<div class="max-w-4xl mx-auto px-4 py-14 grid sm:grid-cols-3 gap-6">
  <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-6 text-center">
    <div class="mx-auto mb-3 h-11 w-11 rounded-xl bg-fulafia-mint flex items-center justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-fulafia-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V18a2 2 0 01-2 2z" />
      </svg>
    </div>
    <h3 class="font-semibold text-slate-800 mb-1">Submit Evidence</h3>
    <p class="text-sm text-slate-500">Upload a screenshot of your NELFUND balance in seconds.</p>
  </div>
  <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-6 text-center">
    <div class="mx-auto mb-3 h-11 w-11 rounded-xl bg-amber-50 flex items-center justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>
    <h3 class="font-semibold text-slate-800 mb-1">Track Status</h3>
    <p class="text-sm text-slate-500">Watch your request move from Pending to a final decision.</p>
  </div>
  <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-6 text-center">
    <div class="mx-auto mb-3 h-11 w-11 rounded-xl bg-emerald-50 flex items-center justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-fulafia-emerald" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>
    <h3 class="font-semibold text-slate-800 mb-1">Get Refunded</h3>
    <p class="text-sm text-slate-500">Approved requests move straight to the bursary for payment.</p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
