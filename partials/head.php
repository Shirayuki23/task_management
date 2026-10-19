<?php
$page_title = $page_title ?? 'Task Management';
$css_extra = $css_extra ?? '';
$asset_ver = date('YmdHis', filemtime(__DIR__ . '/../assets/css/style.css'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | Task Management</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= $asset_ver ?>">
    <link rel="stylesheet" href="assets/css/icons.css?v=<?= $asset_ver ?>">
    <?php if ($css_extra !== ''): ?>
        <link rel="stylesheet" href="assets/css/<?= e($css_extra) ?>.css?v=<?= $asset_ver ?>">
    <?php endif; ?>
</head>
