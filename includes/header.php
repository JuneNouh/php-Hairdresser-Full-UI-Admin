<?php
/**
 * Header Include - Shared across all pages
 */
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', SITE_NAME);
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hairdresser Pro - Book your perfect hair appointment online. Professional haircuts, coloring, and styling services.">
    <title><?= h(PAGE_TITLE) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&display=swap" rel="stylesheet">

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="/css/style.css">

    <!-- Dark mode init (prevent flash) -->
    <script>
        (function(){
            var theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') document.documentElement.classList.add('light-mode-preload');
        })();
    </script>
    <style>
        html.light-mode-preload body { background-color: #f5f0e8; }
    </style>
</head>
<body<?= $isAdmin ? ' class="admin-page"' : '' ?>>
    <?php if (!$isAdmin): ?>
    <?php include __DIR__ . '/nav.php'; ?>
    <?php endif; ?>
    <?php if (!$isAdmin): ?>
    <main class="main-content" id="main-content">
        <div class="container">
            <?= display_flash() ?>
    <?php endif; ?>
