<?php

declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Student details';
$studentId = parseStudentId($_GET['id'] ?? null);
$student = null;
$databaseUnavailable = false;

if ($studentId === null) {
    http_response_code(404);
} else {
    try {
        $statement = db()->prepare(
            'SELECT id, first_name, last_name, email, phone, course, enrolled_on, created_at, updated_at
             FROM students
             WHERE id = :id'
        );
        $statement->execute(['id' => $studentId]);
        $student = $statement->fetch();

        if ($student === false) {
            $student = null;
            http_response_code(404);
        }
    } catch (PDOException $exception) {
        error_log('Unable to load student details: ' . $exception->getMessage());
        http_response_code(503);
        $databaseUnavailable = true;
    }
}

require __DIR__ . '/partials/header.php';

if ($databaseUnavailable):
?>
    <section class="card error-card">
        <h1>Records are temporarily unavailable</h1>
        <p>We could not connect to the student records database. Please make sure MySQL is running, then refresh this page.</p>
        <p><a href="index.php">Return to students</a></p>
    </section>
<?php
elseif ($student === null):
?>
    <section class="card error-card">
        <h1>Student not found</h1>
        <p>This student record could not be found.</p>
        <p><a href="index.php">Return to students</a></p>
    </section>
<?php
else:
    $enrolledOn = (new DateTimeImmutable($student['enrolled_on']))->format('d M Y');
?>
    <section class="page-heading">
        <div>
            <h1><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
            <p>Student record details.</p>
        </div>
        <a class="button" href="edit.php?id=<?= e($student['id']) ?>">Edit student</a>
    </section>

    <section class="card detail-card" aria-label="Student details">
        <dl class="student-details">
            <div>
                <dt>Email</dt>
                <dd><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></dd>
            </div>
            <div>
                <dt>Phone</dt>
                <dd><?= e($student['phone'] ?? '') ?: 'Not provided' ?></dd>
            </div>
            <div>
                <dt>Course</dt>
                <dd><?= e($student['course']) ?></dd>
            </div>
            <div>
                <dt>Enrolled</dt>
                <dd><?= e($enrolledOn) ?></dd>
            </div>
            <div>
                <dt>Created</dt>
                <dd><?= e($student['created_at']) ?></dd>
            </div>
            <div>
                <dt>Last updated</dt>
                <dd><?= e($student['updated_at']) ?></dd>
            </div>
        </dl>
        <a class="back-link" href="index.php">Back to students</a>
    </section>
<?php
endif;

require __DIR__ . '/partials/footer.php';
