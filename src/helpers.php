<?php

declare(strict_types=1);

const STUDENT_COURSES = [
    'Web Development',
    'Software Engineering',
    'Data Analysis',
    'UI/UX Design',
];

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function parseStudentId(mixed $value): ?int
{
    if (!is_string($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $id === false ? null : $id;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg): void
{
    ensureSession();
    $_SESSION['flash'] = [
        'type' => $type,
        'msg' => $msg,
    ];
}

function getFlash(): ?array
{
    ensureSession();
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($message) ? $message : null;
}

function ensureSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function csrfToken(): string
{
    ensureSession();

    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function isValidCsrfToken(mixed $token): bool
{
    ensureSession();

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function validateStudentInput(array $student): array
{
    $errors = [];

    foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
        $value = $student[$field] ?? '';
        $characterCount = preg_match_all('/./us', $value, $matches);

        if ($value === '') {
            $errors[$field] = $label . ' is required.';
        } elseif ($characterCount === false || $characterCount < 2 || $characterCount > 60) {
            $errors[$field] = $label . ' must be between 2 and 60 characters.';
        } elseif (preg_match('/\A[\p{L} -]+\z/u', $value) !== 1) {
            $errors[$field] = $label . ' may contain only letters, spaces, and hyphens.';
        }
    }

    if (filter_var($student['email'] ?? '', FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    }

    $phone = $student['phone'] ?? '';
    if ($phone !== '' && preg_match('/\A0[789][01]\d{8}\z/', $phone) !== 1) {
        $errors['phone'] = 'Enter a valid phone number or leave this field blank.';
    }

    if (!in_array($student['course'] ?? '', STUDENT_COURSES, true)) {
        $errors['course'] = 'Select a valid course.';
    }

    $enrolledOnValue = $student['enrolled_on'] ?? '';
    $enrolledOn = DateTimeImmutable::createFromFormat('!Y-m-d', $enrolledOnValue);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        $enrolledOn === false
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $enrolledOn->format('Y-m-d') !== $enrolledOnValue
    ) {
        $errors['enrolled_on'] = 'Enter a valid enrollment date.';
    } elseif ($enrolledOnValue > date('Y-m-d')) {
        $errors['enrolled_on'] = 'Enrollment date cannot be in the future.';
    }

    return $errors;
}

function studentEmailExists(PDO $pdo, string $email, ?int $exceptId = null): bool
{
    $query = $exceptId === null
        ? 'SELECT id FROM students WHERE email = :email LIMIT 1'
        : 'SELECT id FROM students WHERE email = :email AND id <> :except_id LIMIT 1';
    $statement = $pdo->prepare($query);
    $parameters = ['email' => $email];

    if ($exceptId !== null) {
        $parameters['except_id'] = $exceptId;
    }

    $statement->execute($parameters);

    return $statement->fetch() !== false;
}
