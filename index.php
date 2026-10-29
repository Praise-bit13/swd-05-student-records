<?php

declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Students';
$students = [];
$databaseUnavailable = false;

try {
    $statement = db()->query(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students
         ORDER BY last_name, first_name'
    );
    $students = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log('Unable to load student records: ' . $exception->getMessage());
    http_response_code(503);
    $databaseUnavailable = true;
}

require __DIR__ . '/partials/header.php';

if ($databaseUnavailable):
?>
    <section class="card error-card">
        <h1>Records are temporarily unavailable</h1>
        <p>We could not connect to the student records database. Please make sure MySQL is running, then refresh this page.</p>
    </section>
<?php
else:
?>
    <section class="page-heading">
        <div>
            <h1>Students</h1>
            <p>View and manage student records.</p>
        </div>
        <a class="button" href="add.php">Add student</a>
    </section>

    <section class="card" aria-label="Student records">
        <?php if ($students === []): ?>
            <div class="empty-state">
                <strong>No students yet</strong>
                Add a student to start building your records.
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Course</th>
                            <th scope="col">Enrolled</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="student-name">
                                    <?= e($student['first_name'] . ' ' . $student['last_name']) ?>
                                </td>
                                <td><?= e($student['email']) ?></td>
                                <td><?= e($student['course']) ?></td>
                                <td><?= e((new DateTimeImmutable($student['enrolled_on']))->format('d M Y')) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="edit.php?id=<?= e($student['id']) ?>">Edit</a>
                                        <form method="post" action="delete.php" onsubmit="return confirm('Delete this student record?');">
                                            <input type="hidden" name="id" value="<?= e($student['id']) ?>">
                                            <button class="button button-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php
endif;

require __DIR__ . '/partials/footer.php';
