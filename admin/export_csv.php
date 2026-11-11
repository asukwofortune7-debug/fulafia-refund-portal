<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$stmt = $pdo->prepare(
    'SELECT full_name, matric_number, account_number
     FROM students
     WHERE refund_status = "Approved"
     ORDER BY full_name ASC'
);
$stmt->execute();
$students = $stmt->fetchAll();

logActivity(
    $pdo,
    $_SESSION['admin_id'],
    $_SESSION['admin_name'] ?? $_SESSION['admin_email'],
    sprintf('Exported Approved Refunds CSV (%d records)', count($students))
);

$filename = 'approved_refunds_' . date('Y-m-d_His') . '.csv';

// ---- Stream the CSV directly to the browser as a download ----------------
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// Excel-friendly: UTF-8 BOM so accented characters render correctly
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['Full Name', 'Matriculation Number', 'Bank Account Number']);

foreach ($students as $s) {
    fputcsv($out, [$s['full_name'], $s['matric_number'], $s['account_number']]);
}

fclose($out);
exit;
