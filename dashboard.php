<?php
require_once __DIR__ . '/config.php';
requireStudentLogin();

$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

if (!$student) {
    // Session refers to a deleted account
    session_destroy();
    redirect('login.php');
}

$errors = [];
$uploadSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['balance_image'])) {
    if (!csrfCheck()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $file = $_FILES['balance_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed. Please try again.';
        } elseif ($file['size'] > MAX_UPLOAD_BYTES) {
            $errors[] = 'File is too large. Maximum size is 5MB.';
        } else {
            // --- Strict, multi-layer image validation ---------------------
            // Layer 1: the extension the browser reported must be one we
            // allow. This alone is trivially spoofable (a script can be
            // renamed "shell.php.jpg"), so it's a first filter, not proof.
            $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Layer 2: getimagesize() actually parses the file's binary
            // header. A renamed .php or .js file has no valid image header
            // and will fail this even if its extension and even its
            // declared MIME type claim otherwise.
            $imageInfo = @getimagesize($file['tmp_name']);
            $mime = $imageInfo['mime'] ?? null;

            // Layer 3: cross-check with fileinfo's own MIME detection
            // (reads magic bytes independently of getimagesize) so a
            // crafted polyglot file has to fool two separate parsers.
            $finfoMime = null;
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $finfoMime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
            }

            $extensionOk = in_array($originalExt, ALLOWED_IMAGE_EXTENSIONS, true);
            $getimagesizeOk = $imageInfo && in_array($mime, ALLOWED_IMAGE_TYPES, true);
            $finfoOk = $finfoMime === null || in_array($finfoMime, ALLOWED_IMAGE_TYPES, true);

            if (!$extensionOk || !$getimagesizeOk || !$finfoOk) {
                $errors[] = 'Only genuine PNG and JPG/JPEG images are allowed.';
            } else {
                // We never trust the uploader's filename for storage --
                // we always generate our own from verified data, so path
                // traversal / double-extension tricks in $file['name']
                // can't reach the filesystem.
                $ext = $mime === 'image/png' ? 'png' : 'jpg';
                $filename = 'balance_' . $student['matric_number'] . '_' . time() . '.' . $ext;
                $filename = preg_replace('/[^A-Za-z0-9._-]/', '', $filename);
                $destination = UPLOAD_DIR . $filename;

                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }

                // Remove any previous upload for this student
                if (!empty($student['nelfund_balance_img']) && file_exists(UPLOAD_DIR . $student['nelfund_balance_img'])) {
                    @unlink(UPLOAD_DIR . $student['nelfund_balance_img']);
                }

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $update = $pdo->prepare(
                        'UPDATE students SET nelfund_balance_img = ?, refund_status = "Pending" WHERE id = ?'
                    );
                    $update->execute([$filename, $student['id']]);
                    $uploadSuccess = true;
                    $student['nelfund_balance_img'] = $filename;
                    $student['refund_status'] = 'Pending';
                } else {
                    $errors[] = 'Could not save the uploaded file. Please try again.';
                }
            }
        }
    }
}

$statusMap = [
    'Pending'  => ['label' => 'Pending Review',      'color' => 'bg-amber-50 text-amber-700 border-amber-300'],
    'Approved' => ['label' => 'Approved / Refunded', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-300'],
    'Rejected' => ['label' => 'Rejected',             'color' => 'bg-red-50 text-red-700 border-red-300'],
];
$statusInfo = $statusMap[$student['refund_status']] ?? $statusMap['Pending'];

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-10">
  <h1 class="text-2xl font-bold text-fulafia-forest mb-1 tracking-tight">Welcome, <?= e($student['full_name']) ?></h1>
  <p class="text-slate-500 mb-8">Matric No: <span class="font-medium text-slate-700"><?= e($student['matric_number']) ?></span></p>

  <!-- Status Card -->
  <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-6 mb-6">
    <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Your Refund Status</h2>
    <div class="inline-flex items-center gap-2 border <?= $statusInfo['color'] ?> px-4 py-2 rounded-full font-semibold text-sm">
      <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
      <?= e($statusInfo['label']) ?>
    </div>

    <?php if ($student['refund_status'] === 'Rejected'): ?>
      <p class="text-sm text-slate-500 mt-3">
        Your submission was rejected. Please re-upload a clear screenshot of your NELFUND balance below.
      </p>
    <?php elseif ($student['refund_status'] === 'Pending'): ?>
      <p class="text-sm text-slate-500 mt-3">
        Your submission is being reviewed by the department. Check back later for updates.
      </p>
    <?php else: ?>
      <p class="text-sm text-slate-500 mt-3">
        Your refund has been approved. Contact the Bursary/Department for disbursement details.
      </p>
    <?php endif; ?>
  </div>

  <!-- Current Upload Preview -->
  <?php if (!empty($student['nelfund_balance_img'])): ?>
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-6 mb-6">
      <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Your Submitted Evidence</h2>
      <a href="uploads/<?= e($student['nelfund_balance_img']) ?>" target="_blank" class="inline-block group">
        <img src="uploads/<?= e($student['nelfund_balance_img']) ?>" alt="NELFUND Balance Screenshot"
             class="max-h-64 rounded-xl border border-slate-200 shadow-sm transition group-hover:opacity-90">
      </a>
    </div>
  <?php endif; ?>

  <!-- Upload Form -->
  <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 p-6">
    <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">
      <?= !empty($student['nelfund_balance_img']) ? 'Replace' : 'Upload' ?> NELFUND Balance Screenshot
    </h2>

    <?php if ($uploadSuccess): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3 mb-4">
        Screenshot uploaded successfully. Your status has been set to Pending Review.
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

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

      <label for="balance_image"
             class="relative flex flex-col items-center justify-center gap-2 border-2 border-dashed border-slate-300 hover:border-fulafia-emerald bg-slate-50 hover:bg-emerald-50/50 rounded-2xl px-6 py-10 text-center cursor-pointer transition group">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-400 group-hover:text-fulafia-emerald transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 8.25L12 3.75m0 0L7.5 8.25M12 3.75v13.5" />
        </svg>
        <span class="text-sm font-medium text-slate-600 group-hover:text-fulafia-forest">
          <span id="fileLabel">Tap to choose a screenshot, or drag one here</span>
        </span>
        <span class="text-xs text-slate-400">PNG or JPG only &middot; Maximum size 5MB</span>
        <input id="balance_image" type="file" name="balance_image" accept=".png,.jpg,.jpeg,image/png,image/jpeg" required
               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
               onchange="document.getElementById('fileLabel').textContent = this.files.length ? this.files[0].name : 'Tap to choose a screenshot, or drag one here';">
      </label>

      <button type="submit"
              class="w-full bg-fulafia-forest hover:bg-fulafia-forest2 text-white font-semibold px-6 py-2.5 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
        Submit Screenshot
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
