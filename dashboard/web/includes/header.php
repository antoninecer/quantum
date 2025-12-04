<?php
include_once __DIR__ . '/auth.php';

$user    = current_user();
$current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

function nav_is_active(string $file, string $current): bool {
    return $current === $file;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantum Random Project</title>

    <link rel="stylesheet" href="/assets/css/style.css?v=11">
</head>
<body>

<div class="navbar">
    <div class="nav-inner">
        <a href="/index.php" class="logo">⚛ Quantum Random</a>

        <nav class="nav-links">
            <a href="/index.php"
               class="nav-link<?= nav_is_active('index.php', $current) ? ' active' : '' ?>">
                Home
            </a>
            <a href="/dashboard.php"
               class="nav-link<?= nav_is_active('dashboard.php', $current) ? ' active' : '' ?>">
                Generator
            </a>
            <a href="/dnd.php"
               class="nav-link<?= nav_is_active('dnd.php', $current) ? ' active' : '' ?>">
                DnD dice
            </a>
            <a href="/cards32.php"
               class="nav-link<?= nav_is_active('cards32.php', $current) ? ' active' : '' ?>">
                DnD 32Cards
            </a>
            <a href="/about.php"
               class="nav-link<?= nav_is_active('about.php', $current) ? ' active' : '' ?>">
                About
            </a>
            <a href="https://quantum.api.ventureout.cz/docs"
               target="_blank"
               class="nav-link">
                API Docs
            </a>

            <?php if ($user): ?>
                <a href="/tombola.php"
                   class="nav-link<?= nav_is_active('tombola.php', $current) ? ' active' : '' ?>">
                    Tombola
                </a>
                <?php if (is_admin()): ?>
                    <a href="/admin_users.php"
                       class="nav-link<?= nav_is_active('admin_users.php', $current) ? ' active' : '' ?>">
                        Admin
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>

        <div class="nav-user">
            <?php if ($user): ?>
                <span class="nav-user-name">
                    <?= htmlspecialchars($user['username']) ?>
                </span>
                <span class="nav-user-sep">·</span>
                <a href="/logout.php" class="nav-user-link">Logout</a>
            <?php else: ?>
                <a href="/login.php" class="nav-user-link">Login</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="content">
