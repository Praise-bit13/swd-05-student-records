<?php

declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Add student';
$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => date('Y-m-d'),
];
$errors = [];
$databaseUnavailable = false;
$formAction = 'create.php';
$submitLabel = 'Add student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($student) as $field) {
        $student[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }

    $errors = validateStudentInput($student);

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['_form'] = 'Your form session expired. Please try again.';
    }

    if ($errors === []) {
        try {
            $pdo = db();
        } catch (PDOException $exception) {
            error_log('Unable to open student database: ' . $exception->getMessage());
            http_response_code(503);
            $databaseUnavailable = true;
        }

        if (!$databaseUnavailable) {
            try {
                if (studentEmailExists($pdo, $student['email'])) {
                    $errors['email'] = 'A student with this email address already exists.';
                } else {
                    $insert = $pdo->prepare(
                        'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                         VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
                    );
                    $insert->execute([
                        'first_name' => $student['first_name'],
                        'last_name' => $student['last_name'],
                        'email' => $student['email'],
                        'phone' => $student['phone'] === '' ? null : $student['phone'],
                        'course' => $student['course'],
                        'enrolled_on' => $student['enrolled_on'],
                    ]);

                    flash('success', 'Student added');
                    redirect('index.php');
                }
            } catch (PDOException $exception) {
                error_log('Unable to save student record: ' . $exception->getMessage());
                $errors['_form'] = 'The student could not be saved. Please try again.';
            }
        }
    }
}

require __DIR__ . '/partials/header.php';

if ($databaseUnavailable):
?>
    <section class="card error-card">
        <h1>Records are temporarily unavailable</h1>
        <p>We could not connect to the student records database. Please make sure MySQL is running, then try again.</p>
        <p><a href="create.php">Return to the add student form</a></p>
    </section>
<?php
else:
?>
    <section class="page-heading">
        <div>
            <h1>Add student</h1>
            <p>Enter the student's details below.</p>
        </div>
    </section>

    <section class="card form-card">
        <?php require __DIR__ . '/partials/student-form.php'; ?>
    </section>
<?php
endif;

require __DIR__ . '/partials/footer.php';
