<?php

require_once dirname(__DIR__) . '/src/helpers.php';

$pageTitle = $pageTitle ?? 'Student Records Manager';
$flashMessage = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Student Records Manager</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="index.php">Student Records</a>
            <span class="topbar-label">Administration</span>
        </div>
    </header>
    <main class="container">
        <?php if ($flashMessage !== null): ?>
            <div class="flash flash-<?= e($flashMessage['type'] ?? 'info') ?>" role="status">
                <?= e($flashMessage['msg'] ?? '') ?>
            </div>
        <?php endif; ?>
