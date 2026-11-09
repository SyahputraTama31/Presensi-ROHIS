<?php
// Fetch data filter
$filter_tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$filter_nama    = $_GET['nama'] ?? '';
$show_all       = true; 

// Ambil semua users role 'anggota'/'user'
$all_users = [];
$uq = $conn->query("SELECT id, nama FROM users WHERE role NOT IN ('admin','superadmin') ORDER BY nama ASC");
if ($uq) { 
    while ($r = $uq->fetch_assoc()) {
        $all_users[] = $r; 
    }
}

// Ambil absensi yang sudah ada
$absensi_list   = [];
$absensi_by_uid = []; 

if ($show_all) {
    $where = "a.tanggal = '" . $conn->real_escape_string($filter_tanggal) . "'";
    if ($filter_nama) {
        $where .= " AND u.nama LIKE '%" . $conn->real_escape_string($filter_nama) . "%'";
    }
    $q = $conn->query("SELECT a.*, u.nama as user_nama FROM absensi a JOIN users u ON a.user_id = u.id WHERE $where ORDER BY u.nama ASC");
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $absensi_list[]               = $r;
            $absensi_by_uid[$r['user_id']] = $r;
        }
    }
} else {
    // Tanpa filter tanggal: tampilkan 50 data terbaru
    $where = "1=1";
    if ($filter_nama) {
        $where .= " AND u.nama LIKE '%" . $conn->real_escape_string($filter_nama) . "%'";
    }
    $q = $conn->query("SELECT a.*, u.nama as user_nama FROM absensi a JOIN users u ON a.user_id = u.id WHERE $where ORDER BY a.tanggal DESC, a.waktu DESC LIMIT 50");
    if ($q) { 
        while ($r = $q->fetch_assoc()) {
            $absensi_list[] = $r; 
        }
    }
}

// Siswa yang belum absen pada tanggal yang dipilih
$absent_users = [];
if ($show_all) {
    foreach ($all_users as $u) {
        if ($filter_nama && stripos($u['nama'], $filter_nama) === false) {
            continue;
        }
        if (!isset($absensi_by_uid[$u['id']])) {
            $absent_users[] = $u;
        }
    }
}


$unabsented_users = [];
if ($show_all) {
    $already_absent_ids = [];
    $aq = $conn->query("SELECT user_id FROM absensi WHERE tanggal = '" . $conn->real_escape_string($filter_tanggal) . "'");
    if ($aq) {
        while ($ar = $aq->fetch_assoc()) {
            $already_absent_ids[$ar['user_id']] = true;
        }
    }
    foreach ($all_users as $u) {
        if (!isset($already_absent_ids[$u['id']])) {
            $unabsented_users[] = $u;
        }
    }
} else {
    $unabsented_users = $all_users;
}

// Foto profil admin
$foto_profil = '';
$fp = $conn->query("SELECT foto_profil FROM users WHERE id = $admin_id");
if ($fp && $r = $fp->fetch_assoc()) {
    $foto_profil = $r['foto_profil'] ?? '';
}
