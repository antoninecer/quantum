<?php
include __DIR__ . '/includes/auth.php';
session_destroy();
header('Location: dashboard.php');
exit;
