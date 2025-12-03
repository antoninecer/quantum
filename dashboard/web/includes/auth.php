<?php
// includes/auth.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool {
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_login(): void {
    if (!current_user()) {
        header('Location: dashboard.php');
        exit;
    }
}
