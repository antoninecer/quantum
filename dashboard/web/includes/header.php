<?php
include_once __DIR__ . '/auth.php';
$user = current_user();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/qubit.png">
    <title>Quantum Random Project</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=11">
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <a href="/index.php" class="logo">⚛ Quantum Random</a>
    </div>
    <div class="nav-right">
        <a href="/index.php">Home</a>
        <a href="/dashboard.php">Generator</a>
        <a href="/dnd.php">DnD dice</a>
        <a href="/about.php">About</a>
        <a href="https://quantum.api.ventureout.cz/docs" target="_blank">API Docs</a>

        <?php if ($user): ?>
            <!-- přihlášený uživatel -->
            <a href="/tombola.php">Tombola</a>

            <?php if ($user['role'] === 'admin'): ?>
                <a href="/admin_users.php">Admin</a>
            <?php endif; ?>

            <span class="nav-user">
                <?= htmlspecialchars($user['username']) ?>
                (<a href="/logout.php">Logout</a>)
            </span>
        <?php else: ?>
            <!-- nepřihlášený -->
            <a href="/login.php">Login</a>
        <?php endif; ?>
    </div>
</div>

<div class="content">

<div class="content">

