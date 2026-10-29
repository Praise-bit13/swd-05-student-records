<?php

declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Delete student';
$errorTitle = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    $errorTitle = 'Method not allowed';
    $errorMessage = 'Student records can only be deleted by submitting the delete form.';
} elseif (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    $errorTitle = 'Request could not be verified';
    $errorMessage = 'The form token is missing or expired. Return to the student list and try again.';
} else {
    $studentId = parseStudentId($_POST['id'] ?? null);

    if ($studentId === null) {
        http_response_code(400);
        $errorTitle = 'Invalid student';
        $errorMessage = 'A valid student record must be selected.';
    } else {
        try {
            $delete = db()->prepare('DELETE FROM students WHERE id = :id');
            $delete->execute(['id' => $studentId]);

            if ($delete->rowCount() === 0) {
                flash('error', 'Student not found');
            } else {
                flash('success', 'Student deleted');
            }

            redirect('index.php');
        } catch (PDOException $exception) {
            error_log('Unable to delete student record: ' . $exception->getMessage());
            http_response_code(503);
            $errorTitle = 'Records are temporarily unavailable';
            $errorMessage = 'We could not delete the student record. Please make sure MySQL is running, then try again.';
        }
    }
}

require __DIR__ . '/partials/header.php';
?>
<section class="card error-card">
    <h1><?= e($errorTitle) ?></h1>
    <p><?= e($errorMessage) ?></p>
    <p><a href="index.php">Return to students</a></p>
</section>
<?php
require __DIR__ . '/partials/footer.php';
