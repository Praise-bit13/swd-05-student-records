<?php

$student = $student ?? [];
$errors = $errors ?? [];
$formAction = $formAction ?? '';
$submitLabel = $submitLabel ?? 'Save student';
$fields = [
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'email' => 'Email address',
    'phone' => 'Phone (optional)',
    'course' => 'Course',
    'enrolled_on' => 'Enrollment date',
];
?>
<form class="student-form" method="post" action="<?= e($formAction) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

    <?php if (isset($errors['_form'])): ?>
        <div class="form-error-summary" role="alert"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <?php foreach (['first_name', 'last_name', 'email', 'phone'] as $field): ?>
        <div class="form-field">
            <label for="<?= e($field) ?>"><?= e($fields[$field]) ?></label>
            <input
                id="<?= e($field) ?>"
                name="<?= e($field) ?>"
                type="<?= $field === 'email' ? 'email' : 'text' ?>"
                value="<?= e($student[$field] ?? '') ?>"
                <?= in_array($field, ['first_name', 'last_name', 'email'], true) ? 'required' : '' ?>
                <?= $field === 'phone' ? 'inputmode="numeric" autocomplete="tel"' : '' ?>
                <?= isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="' . e($field) . '-error' : '' ?>
            >
            <?php if (isset($errors[$field])): ?>
                <span class="field-error" id="<?= e($field) ?>-error"><?= e($errors[$field]) ?></span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="form-field">
        <label for="course"><?= e($fields['course']) ?></label>
        <select
            id="course"
            name="course"
            required
            <?= isset($errors['course']) ? 'aria-invalid="true" aria-describedby="course-error"' : '' ?>
        >
            <option value="">Choose a course</option>
            <?php foreach (STUDENT_COURSES as $course): ?>
                <option value="<?= e($course) ?>" <?= ($student['course'] ?? '') === $course ? 'selected' : '' ?>>
                    <?= e($course) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['course'])): ?>
            <span class="field-error" id="course-error"><?= e($errors['course']) ?></span>
        <?php endif; ?>
    </div>

    <div class="form-field">
        <label for="enrolled_on"><?= e($fields['enrolled_on']) ?></label>
        <input
            id="enrolled_on"
            name="enrolled_on"
            type="date"
            value="<?= e($student['enrolled_on'] ?? '') ?>"
            required
            <?= isset($errors['enrolled_on']) ? 'aria-invalid="true" aria-describedby="enrolled_on-error"' : '' ?>
        >
        <?php if (isset($errors['enrolled_on'])): ?>
            <span class="field-error" id="enrolled_on-error"><?= e($errors['enrolled_on']) ?></span>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button class="button" type="submit"><?= e($submitLabel) ?></button>
        <a class="cancel-link" href="index.php">Cancel</a>
    </div>
</form>
