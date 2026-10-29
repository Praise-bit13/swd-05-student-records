<?php

declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$idValue = $_GET['id'] ?? '';
if (!is_string($idValue) || preg_match('/\A[1-9]\d*\z/', $idValue) !== 1) {
    http_response_code(404);
    $student = null;
} else {
    $studentId = (int) $idValue;
    $student = null;
}

$pageTitle = 'Edit student';
$errors = [];
$databaseUnavailable = false;
$formAction = isset($studentId) ? 'edit.php?id=' . $studentId : '';
$submitLabel = 'Save changes';

if (isset($studentId)) {
    try {
        $pdo = db();
        $select = $pdo->prepare(
            'SELECT id, first_name, last_name, email, phone, course, enrolled_on
             FROM students
             WHERE id = :id'
        );
        $select->execute(['id' => $studentId]);
        $student = $select->fetch();
    } catch (PDOException $exception) {
        error_log('Unable to load student for editing: ' . $exception->getMessage());
        http_response_code(503);
        $databaseUnavailable = true;
    }
}

if (!$databaseUnavailable && isset($studentId) && $student === false) {
    http_response_code(404);
}

if (!$databaseUnavailable && isset($studentId) && is_array($student) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['first_name', 'last_name', 'email', 'phone', 'course', 'enrolled_on'] as $field) {
        $student[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }

    $errors = validateStudentInput($student);
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['_form'] = 'Your form session expired. Please try again.';
    }

    if ($errors === []) {
        try {
            if (studentEmailExists($pdo, $student['email'], $studentId)) {
                $errors['email'] = 'A student with this email address already exists.';
            } else {
                $update = $pdo->prepare(
                    'UPDATE students
                     SET first_name = :first_name,
                         last_name = :last_name,
                         email = :email,
                         phone = :phone,
                         course = :course,
                         enrolled_on = :enrolled_on
                     WHERE id = :id'
                );
                $update->execute([
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'email' => $student['email'],
                    'phone' => $student['phone'] === '' ? null : $student['phone'],
                    'course' => $student['course'],
                    'enrolled_on' => $student['enrolled_on'],
                    'id' => $studentId,
                ]);

                flash('success', 'Student updated');
                redirect('index.php');
            }
        } catch (PDOException $exception) {
            error_log('Unable to update student record: ' . $exception->getMessage());
            $errors['_form'] = 'The student could not be updated. Please try again.';
        }
    }
}

require __DIR__ . '/partials/header.php';

if ($databaseUnavailable):
?>
    <section class="card error-card">
        <h1>Records are temporarily unavailable</h1>
        <p>We could not connect to the student records database. Please make sure MySQL is running, then try again.</p>
        <p><a href="index.php">Return to students</a></p>
    </section>
<?php
elseif (!isset($studentId) || !is_array($student)):
?>
    <section class="card error-card">
        <h1>Student not found</h1>
        <p>This student record could not be found.</p>
        <p><a href="index.php">Return to students</a></p>
    </section>
<?php
else:
?>
    <section class="page-heading">
        <div>
            <h1>Edit student</h1>
            <p>Update this student's details.</p>
        </div>
    </section>

    <section class="card form-card">
        <?php require __DIR__ . '/partials/student-form.php'; ?>
    </section>
<?php
endif;

require __DIR__ . '/partials/footer.php';
