<?php
$page_title = $page_title ?? 'Task Management';
$css_extra = $css_extra ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | Task Management</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/icons.css">
    <?php if ($css_extra !== ''): ?>
        <link rel="stylesheet" href="assets/css/<?= e($css_extra) ?>.css">
    <?php endif; ?>
</head>
