<?php
// index.php - Router
session_start();

$role = strtolower(trim($_SESSION['role'] ?? ''));

if (!empty($_SESSION['user_id'])) {
    if ($role === 'superadmin') {
        header('Location: Superadmin/Home.php');
        exit;
    } elseif ($role === 'admin') {
        header('Location: Admin/Home.php');
        exit;
    } elseif ($role === 'user') {
        header('Location: Usr/home.php');
        exit;
    }
}

header('Location: lng/lgn.php');
exit;

