<?php

declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Seed student records';
$seedRequested = ($_GET['confirm'] ?? null) === 'yes';
$inserted = 0;
$skipped = 0;
$databaseUnavailable = false;

$firstNames = [
    'Adaeze', 'Adebayo', 'Aisha', 'Amaka', 'Babatunde',
    'Chiamaka', 'Chinedu', 'Damilola', 'Emeka', 'Fatima',
];
$lastNames = [
    'Adeyemi', 'Afolabi', 'Bello', 'Eze', 'Ibrahim',
    'Nwosu', 'Okafor', 'Okonkwo', 'Olawale', 'Yusuf',
];
$courses = STUDENT_COURSES;
$seedStudents = [];

for ($index = 0; $index < 50; $index++) {
    $group = intdiv($index, count($firstNames));
    $firstIndex = $index % count($firstNames);
    $lastIndex = ($firstIndex + ($group * 2)) % count($lastNames);
    $seedNumber = $index + 1;
    $seedStudents[] = [
        'first_name' => $firstNames[$firstIndex],
        'last_name' => $lastNames[$lastIndex],
        'email' => sprintf('seed.%03d@student-records.test', $seedNumber),
        'phone' => sprintf('080%08d', 10000000 + $seedNumber),
        'course' => $courses[$index % count($courses)],
        'enrolled_on' => date('Y-m-d', strtotime('-' . (30 + ($index * 23)) . ' days')),
    ];
}

if ($seedRequested) {
    try {
        $pdo = db();
        $pdo->beginTransaction();

        $existing = $pdo->prepare('SELECT id FROM students WHERE email = :email LIMIT 1');
        $insert = $pdo->prepare(
            'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
             VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
        );

        foreach ($seedStudents as $student) {
            $existing->execute(['email' => $student['email']]);
            if ($existing->fetch() !== false) {
                $skipped++;
                continue;
            }

            $insert->execute($student);
            $inserted++;
        }

        $pdo->commit();
    } catch (PDOException $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Unable to seed student records: ' . $exception->getMessage());
        http_response_code(503);
        $databaseUnavailable = true;
    }
}

require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Seed student records</h1>
        <p>Insert 50 Nigerian-named sample students to test search and pagination.</p>
    </div>
</section>

<?php if ($databaseUnavailable): ?>
    <section class="card error-card">
        <h1>Records are temporarily unavailable</h1>
        <p>We could not connect to the student records database. Please make sure MySQL is running, then try again.</p>
    </section>
<?php elseif (!$seedRequested): ?>
    <section class="card seed-card">
        <p>No students have been added. Confirm below to insert 50 sample records. Existing seed email addresses will be skipped if you run this again.</p>
        <a class="button" href="seed.php?confirm=yes">Insert 50 sample students</a>
        <a class="cancel-link" href="index.php">Cancel</a>
    </section>
<?php else: ?>
    <section class="card seed-card">
        <h2>Seed complete</h2>
        <p>Inserted <?= $inserted ?> students; skipped <?= $skipped ?> existing seed records.</p>
        <a class="button" href="index.php">View students</a>
    </section>
<?php endif; ?>
<?php
require __DIR__ . '/partials/footer.php';
