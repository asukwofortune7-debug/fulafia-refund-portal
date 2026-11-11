<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfCheck()) {
    redirect('dashboard.php');
}

$studentId = (int)($_POST['student_id'] ?? 0);
$status    = $_POST['refund_status'] ?? '';
$returnQuery = $_POST['return_query'] ?? '';

$allowedStatuses = ['Pending', 'Approved', 'Rejected'];

if ($studentId > 0 && in_array($status, $allowedStatuses, true)) {
    // Fetch the student first so we can log a meaningful entry and email them.
    $lookup = $pdo->prepare('SELECT matric_number, full_name, email FROM students WHERE id = ?');
    $lookup->execute([$studentId]);
    $student = $lookup->fetch();

    if ($student) {
        $stmt = $pdo->prepare('UPDATE students SET refund_status = ? WHERE id = ?');
        $stmt->execute([$status, $studentId]);

        $adminName = $_SESSION['admin_name'] ?? $_SESSION['admin_email'];

        $action = sprintf(
            'Admin %s changed Matric [%s] status to %s',
            $adminName,
            $student['matric_number'],
            $status
        );
        logActivity($pdo, $_SESSION['admin_id'], $adminName, $action, $student['matric_number']);

        // Notify the student by email. A failed send should never block
        // the status update itself -- the change is already saved above.
        sendStatusUpdateEmail($student['email'], $student['full_name'], $status);

        $_SESSION['flash_success'] = 'Student status updated to "' . $status . '" and notification email sent.';
    }
} else {
    $_SESSION['flash_success'] = null;
}

$redirectTo = 'dashboard.php' . ($returnQuery !== '' ? '?' . $returnQuery : '');
redirect($redirectTo);
