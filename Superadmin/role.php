<?php
require_once '../lng/koneksi.php';
require_once '../lng/auth.php';

// Guard: Hanya Superadmin
$role = require_role(['superadmin']);

$admin_id   = $_SESSION['user_id'];
$admin_nama = $_SESSION['nama'] ?? 'Superadmin';
$msg        = '';
$msg_type   = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ubah Role Pengguna
    if (isset($_POST['action']) && $_POST['action'] === 'update_role') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verify_csrf_token($csrf_token)) {
            $msg = 'Permintaan tidak sah: Token keamanan (CSRF) tidak valid.';
            $msg_type = 'error';
        } else {
            $target_id = intval($_POST['target_id'] ?? 0);
            $new_role  = strtolower(trim($_POST['new_role'] ?? ''));
            $whitelist = ['admin', 'user']; // Superadmin tidak boleh diberikan via UI

            if ($target_id <= 0) {
                $msg = 'ID pengguna tidak valid.';
                $msg_type = 'error';
            } elseif ($target_id === intval($admin_id)) {
                $msg = 'Keamanan: Anda tidak dapat mengubah role akun Anda sendiri!';
                $msg_type = 'error';
            } elseif (!in_array($new_role, $whitelist, true)) {
                $msg = 'Role tidak valid! Hanya role Admin dan User yang diizinkan.';
                $msg_type = 'error';
            } else {
                // Cek data user target di database
                $stmt = $conn->prepare("SELECT id, username, nama, Role FROM users WHERE id = ?");
                $stmt->bind_param("i", $target_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $target_user = $res->fetch_assoc();
                $stmt->close();

                if (!$target_user) {
                    $msg = 'Pengguna target tidak ditemukan.';
                    $msg_type = 'error';
                } else {
                    $target_current_role = strtolower(trim($target_user['Role']));
                    if ($target_current_role === 'superadmin') {
                        $msg = 'Keamanan: Akun Superadmin tidak dapat diubah rolenya!';
                        $msg_type = 'error';
                    } else {
                        // Jalankan update role
                        $stmt_up = $conn->prepare("UPDATE users SET Role = ? WHERE id = ?");
                        $stmt_up->bind_param("si", $new_role, $target_id);
                        if ($stmt_up->execute()) {
                            $msg = 'Role untuk ' . $target_user['nama'] . ' (@' . $target_user['username'] . ') berhasil diubah menjadi ' . ucfirst($new_role) . '!';
                            $msg_type = 'success';
                        } else {
                            $msg = 'Gagal memperbarui role pengguna: ' . $conn->error;
                            $msg_type = 'error';
                        }
                        $stmt_up->close();
                    }
                }
            }
        }
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: ../lng/lgn.php');
        exit;
    }
}

// 3 Kartu Ringkasan
$q_super = $conn->query("SELECT COUNT(*) AS total FROM users WHERE LOWER(TRIM(Role)) = 'superadmin'");
$count_superadmin = $q_super ? intval($q_super->fetch_assoc()['total']) : 0;

$q_admin = $conn->query("SELECT COUNT(*) AS total FROM users WHERE LOWER(TRIM(Role)) = 'admin'");
$count_admin = $q_admin ? intval($q_admin->fetch_assoc()['total']) : 0;

$q_user = $conn->query("SELECT COUNT(*) AS total FROM users WHERE LOWER(TRIM(Role)) = 'user'");
$count_user = $q_user ? intval($q_user->fetch_assoc()['total']) : 0;

// Filter GET
$filter_search = trim($_GET['q'] ?? '');
$filter_role   = strtolower(trim($_GET['role'] ?? ''));

$where_clauses = ["1=1"];
$params = [];
$types  = "";

if (!empty($filter_search)) {
    $where_clauses[] = "(nama LIKE ? OR username LIKE ?)";
    $search_param = "%" . $filter_search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types   .= "ss";
}

if (!empty($filter_role)) {
    $where_clauses[] = "LOWER(TRIM(Role)) = ?";
    $params[] = $filter_role;
    $types   .= "s";
}

$sql_users = "SELECT id, username, nama, Role, foto_profil FROM users WHERE " . implode(" AND ", $where_clauses) . " ORDER BY CASE WHEN LOWER(TRIM(Role)) = 'superadmin' THEN 1 WHEN LOWER(TRIM(Role)) = 'admin' THEN 2 ELSE 3 END, nama ASC";

$stmt_users = $conn->prepare($sql_users);
if (!empty($params)) {
    $stmt_users->bind_param($types, ...$params);
}
$stmt_users->execute();
$res_users = $stmt_users->get_result();
$users_list = [];
if ($res_users) {
    while ($row = $res_users->fetch_assoc()) {
        $users_list[] = $row;
    }
}
$stmt_users->close();

// Foto profil user saat ini
$stmt_myfp = $conn->prepare("SELECT foto_profil FROM users WHERE id = ?");
$stmt_myfp->bind_param("i", $admin_id);
$stmt_myfp->execute();
$res_myfp = $stmt_myfp->get_result();
$foto_profil = '';
if ($res_myfp && $row_fp = $res_myfp->fetch_assoc()) {
    $foto_profil = $row_fp['foto_profil'] ?? '';
}
$stmt_myfp->close();

$active_menu = 'role';
$csrf_token  = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Role & Hak Akses - Superadmin ROHIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../src/output.css?v=<?= filemtime('../src/output.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="shortcut icon" href="../img/lg.jpg" type="image/x-icon">
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col selection:bg-rohis-700 selection:text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    <!-- Superadmin Sidebar Partial -->
    <?php include 'partials/side.php'; ?>

    <!-- Main Content Area with Desktop Left Margin -->
    <div class="lg:pl-64 flex-1 flex flex-col min-h-screen">
        
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-6xl w-full mx-auto">
            
            <!-- Page Header -->
            <header class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200/80">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-rohis-50 border border-rohis-200 text-rohis-700 text-xs font-semibold">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            Otorisasi & Keamanan
                        </span>
                        <span class="text-xs text-slate-400 font-medium"><?= date('l, d F Y') ?></span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Manajemen Role & Hak Akses</h1>
                    <p class="text-sm text-slate-500 mt-1">Kelola peran pengguna antara Administrator dan Siswa dalam ekosistem presensi ROHIS.</p>
                </div>
            </header>

            <!-- Feedback Notification -->
            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-xl border flex items-start gap-3 text-sm shadow-xs <?= $msg_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
                    <i data-lucide="<?= $msg_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5 <?= $msg_type === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>"></i>
                    <p class="text-xs font-medium leading-relaxed"><?= htmlspecialchars($msg) ?></p>
                </div>
            <?php endif; ?>

            <!-- 3 Ringkasan Akun KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                
                <!-- Superadmin Card -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Superadmin</span>
                        <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4.5 h-4.5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-slate-900 tracking-tight"><?= $count_superadmin ?></span>
                            <span class="text-xs font-medium text-slate-500">akun</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Akses penuh ke seluruh modul sistem</p>
                    </div>
                </div>

                <!-- Admin Card -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Administrator</span>
                        <div class="w-9 h-9 rounded-lg bg-rohis-50 text-rohis-700 border border-rohis-200 flex items-center justify-center">
                            <i data-lucide="shield" class="w-4.5 h-4.5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-slate-900 tracking-tight"><?= $count_admin ?></span>
                            <span class="text-xs font-medium text-slate-500">akun</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Kelola presensi & lokasi toleransi</p>
                    </div>
                </div>

                <!-- User Card -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">User (Siswa)</span>
                        <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center">
                            <i data-lucide="users" class="w-4.5 h-4.5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-slate-900 tracking-tight"><?= $count_user ?></span>
                            <span class="text-xs font-medium text-slate-500">akun</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Peserta presensi mandiri kegiatan</p>
                    </div>
                </div>

            </div>

            <!-- Filter Search Card -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xs p-5 sm:p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                    
                    <div class="sm:col-span-6">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Pencarian Akun</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </span>
                            <input type="text" name="q" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Cari nama atau username..."
                                class="w-full pl-9 pr-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all">
                        </div>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Filter Role</label>
                        <select name="role" class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all cursor-pointer">
                            <option value="">Semua Role</option>
                            <option value="superadmin" <?= $filter_role === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                            <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="user" <?= $filter_role === 'user' ? 'selected' : '' ?>>User (Siswa)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-3 flex gap-2">
                        <button type="submit" class="flex-1 py-2 px-3.5 bg-rohis-800 hover:bg-rohis-900 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                            Tampilkan
                        </button>
                        <a href="role.php" class="py-2 px-3 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-xs font-semibold transition-colors text-center">
                            Reset
                        </a>
                    </div>

                </form>
            </div>

            <!-- Main User List Table Card -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Daftar Pengguna Sistem</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Menampilkan <?= count($users_list) ?> akun terdaftar</p>
                    </div>
                    <span class="text-[11px] text-slate-400 italic">*Akun Superadmin dikunci dan dilindungi</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="px-5 py-3.5">Pengguna</th>
                                <th class="px-5 py-3.5">Username</th>
                                <th class="px-5 py-3.5">Role Saat Ini</th>
                                <th class="px-5 py-3.5 text-center">Tindakan Otorisasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($users_list)): ?>
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-slate-400">
                                    <i data-lucide="users" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                    <p class="font-semibold text-slate-700">Tidak ada akun ditemukan.</p>
                                    <p class="text-[11px] mt-0.5">Sesuaikan kata kunci pencarian atau filter role.</p>
                                </td>
                            </tr>
                            <?php else: foreach ($users_list as $u):
                                $user_role_lower = strtolower(trim($u['Role']));
                                $is_current_user = intval($u['id']) === intval($admin_id);
                                $is_superadmin   = ($user_role_lower === 'superadmin');

                                if ($user_role_lower === 'superadmin') {
                                    $badge_class = 'bg-slate-100 text-slate-800 border-slate-300 font-bold';
                                    $badge_icon  = 'shield-check';
                                    $badge_label = 'Superadmin';
                                } elseif ($user_role_lower === 'admin') {
                                    $badge_class = 'bg-rohis-50 text-rohis-700 border-rohis-200';
                                    $badge_icon  = 'shield';
                                    $badge_label = 'Admin';
                                } else {
                                    $badge_class = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    $badge_icon  = 'user';
                                    $badge_label = 'User (Siswa)';
                                }
                            ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($u['foto_profil'])): ?>
                                            <img src="<?= htmlspecialchars($u['foto_profil']) ?>" alt="Avatar" class="w-8 h-8 rounded-full border border-slate-200 object-cover shrink-0">
                                        <?php else: ?>
                                            <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs shrink-0">
                                                <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-semibold text-slate-900 flex items-center gap-1.5">
                                                <?= htmlspecialchars($u['nama']) ?>
                                                <?php if ($is_current_user): ?>
                                                    <span class="px-1.5 py-0.2 rounded-md text-[10px] font-bold bg-rohis-50 text-rohis-700 border border-rohis-200">Anda</span>
                                                <?php endif; ?>
                                            </p>
                                            <span class="text-[10px] text-slate-400 font-mono">ID: #<?= $u['id'] ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="font-mono text-xs font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                                        @<?= htmlspecialchars($u['username']) ?>
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border <?= $badge_class ?>">
                                        <i data-lucide="<?= $badge_icon ?>" class="w-3.5 h-3.5"></i>
                                        <span><?= $badge_label ?></span>
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    <?php if ($is_current_user): ?>
                                        <span class="text-xs text-slate-400 font-medium italic">Akun Anda</span>
                                    <?php elseif ($is_superadmin): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 text-slate-500 text-xs font-semibold rounded-md border border-slate-200" title="Akun Superadmin dikunci dari perubahan">
                                            <i data-lucide="lock" class="w-3 h-3"></i>
                                            Terkunci
                                        </span>
                                    <?php else: ?>
                                        <button type="button" 
                                            onclick="openRoleModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nama'])) ?>', '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= $user_role_lower ?>', '<?= !empty($u['foto_profil']) ? htmlspecialchars(addslashes($u['foto_profil'])) : '' ?>')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rohis-50 hover:bg-rohis-100 text-rohis-700 border border-rohis-200 rounded-lg text-xs font-semibold transition-colors cursor-pointer">
                                            <i data-lucide="user-cog" class="w-3.5 h-3.5"></i>
                                            <span>Ubah Role</span>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Portal Presensi ROHIS. Manajemen hak akses & integritas sistem.
        </footer>

    </div>

    <!-- Modal: Ubah Role Pengguna -->
    <div id="modalRole" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md border border-slate-200 overflow-hidden flex flex-col">
            
            <!-- Header Modal -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-rohis-50 text-rohis-700 border border-rohis-200 flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Ubah Role Pengguna</h3>
                        <p class="text-xs text-slate-400">Pilih peran Admin atau Siswa</p>
                    </div>
                </div>
                <button type="button" onclick="closeRoleModal()" class="p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Body Modal -->
            <div class="p-6">
                <!-- Target User Box -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center gap-3 mb-5">
                    <div id="modalTargetAvatarBox" class="w-10 h-10 rounded-full bg-rohis-100 border border-rohis-200 text-rohis-700 font-bold flex items-center justify-center text-xs shrink-0 overflow-hidden">
                        <span id="modalTargetInitials">?</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 id="modalTargetNama" class="text-xs font-bold text-slate-900 truncate">Nama User</h4>
                        <p class="text-xs text-slate-500 font-mono">@<span id="modalTargetUsername">username</span></p>
                    </div>
                </div>

                <!-- Form -->
                <form method="POST" id="formUbahRole" class="space-y-4">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="target_id" id="modalTargetId" value="0">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Pilih Role Baru</label>
                        <div class="space-y-2.5">
                            
                            <!-- Option ADMIN -->
                            <label class="cursor-pointer block">
                                <input type="radio" name="new_role" id="roleAdminRadio" value="admin" class="peer sr-only">
                                <div class="p-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition-all peer-checked:border-rohis-700 peer-checked:bg-rohis-50/60 flex items-start gap-3 select-none">
                                    <div class="w-8 h-8 rounded-lg bg-rohis-100 text-rohis-700 flex items-center justify-center shrink-0">
                                        <i data-lucide="shield" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs font-bold text-slate-900">Admin</p>
                                            <span class="text-[10px] font-semibold px-2 py-0.2 rounded-md bg-rohis-100 text-rohis-700">Administrator</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Kelola titik lokasi presensi & rekap kehadiran siswa.</p>
                                    </div>
                                </div>
                            </label>

                            <!-- Option USER -->
                            <label class="cursor-pointer block">
                                <input type="radio" name="new_role" id="roleUserRadio" value="user" class="peer sr-only">
                                <div class="p-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition-all peer-checked:border-emerald-600 peer-checked:bg-emerald-50/60 flex items-start gap-3 select-none">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs font-bold text-slate-900">User</p>
                                            <span class="text-[10px] font-semibold px-2 py-0.2 rounded-md bg-emerald-100 text-emerald-700">Siswa / Anggota</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Hanya dapat melakukan presensi mandiri sebagai peserta.</p>
                                    </div>
                                </div>
                            </label>

                        </div>
                    </div>

                    <!-- Security Alert -->
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-[11px] flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5 text-amber-600"></i>
                        <span>Role Superadmin tidak dapat dialihkan melalui antarmuka ini demi alasan keamanan sistem.</span>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" onclick="closeRoleModal()" class="flex-1 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 border border-slate-200 rounded-lg transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="flex-1 py-2 text-xs font-semibold text-white bg-rohis-800 hover:bg-rohis-900 rounded-lg transition-colors shadow-xs flex items-center justify-center gap-1.5 cursor-pointer">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        lucide.createIcons();

        function openRoleModal(userId, nama, username, currentRole, fotoUrl) {
            document.getElementById('modalTargetId').value = userId;
            document.getElementById('modalTargetNama').textContent = nama;
            document.getElementById('modalTargetUsername').textContent = username;

            const avatarBox = document.getElementById('modalTargetAvatarBox');
            if (fotoUrl && fotoUrl.trim() !== '') {
                avatarBox.innerHTML = `<img src="${fotoUrl}" class="w-full h-full object-cover">`;
            } else {
                const initial = nama ? nama.charAt(0).toUpperCase() : '?';
                avatarBox.innerHTML = `<span id="modalTargetInitials">${initial}</span>`;
            }

            if (currentRole === 'admin') {
                document.getElementById('roleAdminRadio').checked = true;
            } else {
                document.getElementById('roleUserRadio').checked = true;
            }

            const modal = document.getElementById('modalRole');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (window.lucide) window.lucide.createIcons();
        }

        function closeRoleModal() {
            const modal = document.getElementById('modalRole');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('formUbahRole').addEventListener('submit', function(e) {
            e.preventDefault();
            const nama = document.getElementById('modalTargetNama').textContent;
            const selected = document.querySelector('input[name="new_role"]:checked');
            if (!selected) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Role',
                    text: 'Silakan pilih role baru untuk pengguna ini.',
                    confirmButtonColor: '#194fa6'
                });
                return;
            }

            const roleName = selected.value === 'admin' ? 'Admin' : 'User (Siswa)';

            Swal.fire({
                title: 'Konfirmasi Ubah Role',
                html: `Apakah Anda yakin ingin mengubah peran <b>${nama}</b> menjadi <b>${roleName}</b>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#194fa6',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Ubah Role',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });

        document.querySelectorAll('.logout-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Keluar',
                    text: 'Apakah Anda yakin ingin mengakhiri sesi Superadmin?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#be123c',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Keluar',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>
