<?php

declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Students';
$students = [];
$totalStudents = 0;
$databaseUnavailable = false;
$perPage = 10;
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$course = is_string($_GET['course'] ?? null) ? $_GET['course'] : '';
if (!in_array($course, STUDENT_COURSES, true)) {
    $course = '';
}

$requestedPage = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$currentPage = $requestedPage === false ? 1 : $requestedPage;
$offset = 0;
$where = [];
$parameters = [];

if ($search !== '') {
    $where[] = '(first_name LIKE :first_name_search OR last_name LIKE :last_name_search OR email LIKE :email_search)';
    $searchPattern = '%' . $search . '%';
    $parameters['first_name_search'] = $searchPattern;
    $parameters['last_name_search'] = $searchPattern;
    $parameters['email_search'] = $searchPattern;
}

if ($course !== '') {
    $where[] = 'course = :course';
    $parameters['course'] = $course;
}

$whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

try {
    $pdo = db();
    $countStatement = $pdo->prepare('SELECT COUNT(*) FROM students' . $whereSql);
    $countStatement->execute($parameters);
    $totalStudents = (int) $countStatement->fetchColumn();

    $totalPages = max(1, (int) ceil($totalStudents / $perPage));
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $perPage;

    $statement = $pdo->prepare(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students' . $whereSql . '
         ORDER BY last_name, first_name
         LIMIT :limit OFFSET :offset'
    );

    foreach ($parameters as $name => $value) {
        $statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
    }
    $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $students = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log('Unable to load student records: ' . $exception->getMessage());
    http_response_code(503);
    $databaseUnavailable = true;
    $totalPages = 1;
    $currentPage = 1;
}

$firstStudent = $totalStudents === 0 ? 0 : $offset + 1;
$lastStudent = min($offset + $perPage, $totalStudents);
$paginationUrl = static function (int $page) use ($search, $course): string {
    $query = [];
    if ($search !== '') {
        $query['q'] = $search;
    }
    if ($course !== '') {
        $query['course'] = $course;
    }
    $query['page'] = $page;

    return 'index.php?' . http_build_query($query);
};

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
        <a class="button" href="create.php">Add student</a>
    </section>

    <section class="card student-list-card" aria-label="Student records">
        <form class="student-filters" method="get" action="index.php">
            <div class="filter-search">
                <label for="q">Search students</label>
                <input id="q" name="q" type="search" value="<?= e($search) ?>" placeholder="Name or email">
            </div>
            <div class="filter-course">
                <label for="course">Course</label>
                <select id="course" name="course">
                    <option value="">All courses</option>
                    <?php foreach (STUDENT_COURSES as $courseOption): ?>
                        <option value="<?= e($courseOption) ?>" <?= $course === $courseOption ? 'selected' : '' ?>>
                            <?= e($courseOption) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="button" type="submit">Search</button>
            <?php if ($search !== '' || $course !== ''): ?>
                <a class="clear-filters" href="index.php">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($students === []): ?>
            <div class="empty-state">
                <strong><?= $totalStudents === 0 && $search === '' && $course === '' ? 'No students yet' : 'No matching students' ?></strong>
                <?= $totalStudents === 0 && $search === '' && $course === '' ? 'Add a student to start building your records.' : 'Try changing or clearing your filters.' ?>
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
                                    <a class="student-name" href="show.php?id=<?= e($student['id']) ?>">
                                        <?= e($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </a>
                                </td>
                                <td><?= e($student['email']) ?></td>
                                <td><?= e($student['course']) ?></td>
                                <td><?= e((new DateTimeImmutable($student['enrolled_on']))->format('d M Y')) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="edit.php?id=<?= e($student['id']) ?>">Edit</a>
                                        <form method="post" action="delete.php" onsubmit="return confirm(<?= e(json_encode('Delete ' . $student['first_name'] . ' ' . $student['last_name'] . '?', JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>);">
                                            <input type="hidden" name="id" value="<?= e($student['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
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

        <div class="list-footer">
            <p class="result-count">
                <?php if ($totalStudents === 0): ?>
                    Showing 0 students
                <?php else: ?>
                    Showing <?= $firstStudent ?>–<?= $lastStudent ?> of <?= $totalStudents ?> students
                <?php endif; ?>
            </p>
            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Student list pages">
                    <?php if ($currentPage > 1): ?>
                        <a class="page-link" href="<?= e($paginationUrl($currentPage - 1)) ?>">Previous</a>
                    <?php else: ?>
                        <span class="page-link is-disabled" aria-disabled="true">Previous</span>
                    <?php endif; ?>

                    <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                        <?php if ($page === $currentPage): ?>
                            <span class="page-link is-current" aria-current="page"><?= $page ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= e($paginationUrl($page)) ?>"><?= $page ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="page-link" href="<?= e($paginationUrl($currentPage + 1)) ?>">Next</a>
                    <?php else: ?>
                        <span class="page-link is-disabled" aria-disabled="true">Next</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </section>
<?php
endif;

require __DIR__ . '/partials/footer.php';
