<?php $theme = getUserTheme(); ?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Most — <?= $pageTitle ?? 'Доска' ?></title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/themes.css">
</head>