<?php
// lng/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memverifikasi autentikasi dan otorisasi role user berdasarkan data terbaru di DB.
 *
 * @param array $roles Daftar role yang diizinkan (case-insensitive), misal ['superadmin']
 * @param mysqli|null $conn Objek koneksi mysqli opsional
 * @return string Role terbaru user
 */
function require_role(array $roles, $conn = null) {
    if (!$conn) {
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            $conn = $GLOBALS['conn'];
        } else {
            require_once __DIR__ . '/koneksi.php';
        }
    }

    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        header('Location: ../lng/lgn.php');
        exit;
    }

    // Ambil data user & role terbaru langsung dari database
    $stmt = $conn->prepare("SELECT id, username, nama, Role FROM users WHERE id = ?");
    if (!$stmt) {
        header('Location: ../lng/lgn.php');
        exit;
    }

    $uid = intval($user_id);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        session_unset();
        session_destroy();
        header('Location: ../lng/lgn.php');
        exit;
    }

    // Perbarui session dengan data teranyar
    $current_role = strtolower(trim($user['Role']));
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama']     = $user['nama'];
    $_SESSION['role']     = $user['Role'];

    $allowed = array_map(function($r) {
        return strtolower(trim($r));
    }, $roles);

    if (!in_array($current_role, $allowed, true)) {
        // Alihkan jika role tidak memiliki izin untuk halaman ini
        if ($current_role === 'superadmin') {
            header('Location: ../Superadmin/Home.php');
        } elseif ($current_role === 'admin') {
            header('Location: ../Admin/Home.php');
        } elseif ($current_role === 'user') {
            header('Location: ../Usr/home.php');
        } else {
            header('Location: ../lng/lgn.php');
        }
        exit;
    }

    return $current_role;
}

/**
 * Menghasilkan token CSRF dan menyimpannya di session jika belum ada
 *
 * @return string
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Memvalidasi token CSRF menggunakan hash_equals
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

