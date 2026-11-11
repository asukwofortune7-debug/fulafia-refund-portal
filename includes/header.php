<?php
/**
 * includes/header.php
 * Expects (optionally) $pageTitle to be set before include.
 *
 * All navigation links here are RELATIVE, not built from BASE_URL.
 * This file is shared by both root-level pages (dashboard.php,
 * index.php, ...) and /admin/ pages (admin/dashboard.php,
 * admin/activity_log.php, ...), so "relative" means something
 * different depending on which one included it -- $inAdmin below
 * detects that and picks the right prefix.
 */
$pageTitle = $pageTitle ?? SITE_NAME;
$inAdmin = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
$root = $inAdmin ? '../' : '';       // from /admin/, go up one level to reach root-level files
$adminPath = $inAdmin ? '' : 'admin/'; // from root, go into admin/; from admin/, already there
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          fulafia: {
            forest:  '#0B3D2E',
            forest2: '#0F5132',
            emerald: '#10B981',
            mint:    '#D1FAE5',
            slate:   '#1E293B',
            gold:    '#B8860B',
            darkgold:'#8B6914'
          }
        },
        boxShadow: {
          card: '0 10px 30px -12px rgba(15, 23, 42, 0.12)'
        }
      }
    }
  }
</script>
<!--
  Logo note: this <img> is a plain, static tag. No onerror/onload
  handlers, no opacity toggles, no JS that can hide it after the
  page mounts -- if you don't see it, the file genuinely isn't at
  the resolved path, not because something is dynamically removing it.
-->
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">

<nav class="bg-fulafia-forest text-white shadow-lg shadow-emerald-950/20 sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
    <a href="<?= !empty($_SESSION['admin_id']) ? $adminPath . 'dashboard.php' : $root . 'index.php' ?>" class="flex items-center gap-3">
      <img src="<?= $root ?>logo.png" alt="FULAFIA Logo" class="h-11 w-11 object-contain block rounded-full bg-white p-1 shadow-sm shrink-0">
      <div class="leading-tight">
        <div class="font-bold text-sm sm:text-base tracking-tight">Federal University of Lafia</div>
        <div class="text-xs text-emerald-300 font-semibold tracking-wide uppercase">NELFUND Refund Portal</div>
      </div>
    </a>
    <div class="flex items-center gap-4 text-sm">
      <?php if (!empty($_SESSION['student_id'])): ?>
        <span class="hidden sm:inline text-emerald-100/80">Hi, <span class="font-medium text-white"><?= e($_SESSION['student_name'] ?? 'Student') ?></span></span>
        <a href="<?= $root ?>logout.php" class="bg-white/10 hover:bg-white/20 border border-white/15 px-3.5 py-1.5 rounded-lg font-medium transition">Logout</a>
      <?php elseif (!empty($_SESSION['admin_id'])): ?>
        <span class="hidden sm:inline text-emerald-100/80">
          <?= e($_SESSION['admin_name'] ?? $_SESSION['admin_email'] ?? '') ?>
          <span class="ml-1.5 text-[11px] font-semibold text-fulafia-forest bg-emerald-300 px-2 py-0.5 rounded-full align-middle"><?= e($_SESSION['admin_role'] ?? 'Admin') ?></span>
        </span>
        <a href="<?= $root ?>logout.php" class="bg-white/10 hover:bg-white/20 border border-white/15 px-3.5 py-1.5 rounded-lg font-medium transition">Logout</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($_SESSION['admin_id'])): ?>
    <?php
      $currentScript = basename($_SERVER['SCRIPT_NAME']);
      $navLinkClass = fn(string $file) => 'px-3.5 py-2.5 text-sm font-medium border-b-2 transition '
          . ($currentScript === $file ? 'border-emerald-400 text-white' : 'border-transparent text-emerald-100/70 hover:text-white hover:border-emerald-400/40');
    ?>
    <div class="border-t border-white/10 bg-black/10">
      <div class="max-w-6xl mx-auto px-4 flex gap-1 overflow-x-auto">
        <a href="<?= $adminPath ?>dashboard.php" class="<?= $navLinkClass('dashboard.php') ?>">Students</a>
        <a href="<?= $adminPath ?>activity_log.php" class="<?= $navLinkClass('activity_log.php') ?>">Activity Log</a>
        <?php if (($_SESSION['admin_role'] ?? '') === 'SuperAdmin'): ?>
          <a href="<?= $adminPath ?>manage_admins.php" class="<?= $navLinkClass('manage_admins.php') ?>">Manage Admins</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</nav>

<main class="flex-1 w-full">
