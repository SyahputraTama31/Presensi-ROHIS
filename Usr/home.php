<?php
session_start();
include '../lng/koneksi.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: ../lng/lgn.php");
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../lng/lgn.php");
    exit();
}

$bulan_ini = date('Y-m');
$hari_ini = date('Y-m-d');

// Total absensi bulan ini
$total = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM absensi WHERE user_id = $user_id AND tanggal LIKE '$bulan_ini%'");
if ($result && $row = $result->fetch_assoc()) {
    $total = $row['total'];
}

// Status hari ini
$status_hari_ini = 'Belum Presensi';
$today_waktu = null;
$today_result = $conn->query("SELECT status, waktu FROM absensi WHERE user_id = $user_id AND tanggal = '$hari_ini'");
if ($today_result && $today_row = $today_result->fetch_assoc()) {
    $status_hari_ini = ucfirst(strtolower($today_row['status']));
    $today_waktu = $today_row['waktu'];
}

// Riwayat 10 terakhir
$riwayat = [];
$riwayat_result = $conn->query("SELECT tanggal, waktu, status, pesan FROM absensi WHERE user_id = $user_id ORDER BY tanggal DESC, waktu DESC LIMIT 10");
if ($riwayat_result) {
    while ($row = $riwayat_result->fetch_assoc()) {
        $riwayat[] = $row;
    }
}

// Foto profil user
$foto_profil = '';
$fp = $conn->query("SELECT foto_profil FROM users WHERE id = $user_id");
if ($fp && $r = $fp->fetch_assoc()) {
    $foto_profil = $r['foto_profil'] ?? '';
}

$active_menu = 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - Presensi ROHIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../src/output.css?v=<?= filemtime('../src/output.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="shortcut icon" href="../img/lg.jpg" type="image/x-icon">
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col selection:bg-rohis-700 selection:text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    <!-- Sidebar Partial (includes mobile topbar & offcanvas drawer) -->
    <?php include 'partials/side.php'; ?>

    <!-- Main Content Area with Desktop Left Margin -->
    <div class="lg:pl-64 flex-1 flex flex-col min-h-screen">
        
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-6xl w-full mx-auto">
            
            <!-- Welcome Header -->
            <header class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200/80">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-rohis-50 border border-rohis-200 text-rohis-700 text-xs font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-rohis-600"></span>
                            Portal Siswa
                        </span>
                        <span class="text-xs text-slate-400 font-medium"><?= date('l, d F Y') ?></span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        Halo, <?= htmlspecialchars(explode(' ', trim($_SESSION['nama'] ?? 'User'))[0]) ?>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">Selamat datang di sistem pencatatan kehadiran kegiatan Rohani Islam.</p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="absen.php" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-rohis-800 hover:bg-rohis-900 active:bg-rohis-950 text-white rounded-xl text-sm font-semibold shadow-xs transition-colors">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        <span>Presensi Sekarang</span>
                    </a>
                </div>
            </header>

            <!-- Metrics Summary Cards -->
            <section class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mb-8">
                
                <!-- Stat 1: Total Bulan Ini -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Kehadiran</span>
                        <div class="w-9 h-9 rounded-lg bg-rohis-50 text-rohis-700 border border-rohis-200 flex items-center justify-center">
                            <i data-lucide="calendar" class="w-4.5 h-4.5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight"><?= $total ?></span>
                            <span class="text-sm font-medium text-slate-500">hari presensi</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Tercatat sepanjang bulan <?= date('F Y') ?></p>
                    </div>
                </div>

                <!-- Stat 2: Status Hari Ini -->
                <?php
                    $status_key = strtolower($status_hari_ini);
                    if ($status_key === 'hadir') {
                        $badge_class = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        $status_icon = 'check-circle';
                        $status_desc = $today_waktu ? 'Waktu: ' . date('H:i', strtotime($today_waktu)) . ' WIB' : 'Tercatat hadir tepat waktu';
                    } elseif ($status_key === 'izin') {
                        $badge_class = 'bg-amber-50 text-amber-700 border-amber-200';
                        $status_icon = 'file-text';
                        $status_desc = 'Izin dengan konfirmasi pembina';
                    } elseif ($status_key === 'sakit') {
                        $badge_class = 'bg-sky-50 text-sky-700 border-sky-200';
                        $status_icon = 'activity';
                        $status_desc = 'Sakit dengan keterangan tercatat';
                    } elseif ($status_key === 'alpha') {
                        $badge_class = 'bg-rose-50 text-rose-700 border-rose-200';
                        $status_icon = 'x-circle';
                        $status_desc = 'Tidak hadir tanpa keterangan';
                    } else {
                        $badge_class = 'bg-slate-100 text-slate-600 border-slate-200';
                        $status_icon = 'clock';
                        $status_desc = 'Belum melakukan check-in hari ini';
                    }
                ?>
                <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status Hari Ini</span>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border <?= $badge_class ?>">
                            <i data-lucide="<?= $status_icon ?>" class="w-3.5 h-3.5"></i>
                            <span><?= $status_hari_ini ?></span>
                        </div>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight"><?= $status_hari_ini ?></span>
                        <p class="text-xs text-slate-500 mt-1"><?= $status_desc ?></p>
                    </div>
                </div>

            </section>

            <!-- Attendance History Section -->
            <section class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 tracking-tight">Riwayat Presensi Terbaru</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Maksimal 10 data presensi terakhir Anda</p>
                    </div>
                    <span class="text-xs font-medium text-slate-400">Total riwayat: <?= count($riwayat) ?></span>
                </div>

                <?php if (empty($riwayat)): ?>
                    <!-- Empty State -->
                    <div class="p-12 text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="clipboard-list" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-800">Belum Ada Riwayat Presensi</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                            Catatan kehadiran Anda akan otomatis tampil di sini setelah Anda melakukan presensi via kamera.
                        </p>
                        <a href="absen.php" class="inline-flex items-center gap-2 mt-4 px-3.5 py-2 bg-rohis-800 hover:bg-rohis-900 text-white rounded-lg text-xs font-semibold transition-colors">
                            <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                            Mulai Presensi Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                                <tr>
                                    <th class="px-5 py-3.5">Tanggal</th>
                                    <th class="px-5 py-3.5">Waktu Check-In</th>
                                    <th class="px-5 py-3.5">Status</th>
                                    <th class="px-5 py-3.5">Keterangan / Motivasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($riwayat as $r): 
                                    $st = strtolower($r['status'] ?? '');
                                    if ($st === 'hadir') {
                                        $pill = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                        $label = 'Hadir';
                                    } elseif ($st === 'izin') {
                                        $pill = 'bg-amber-50 text-amber-700 border-amber-200';
                                        $label = 'Izin';
                                    } elseif ($st === 'sakit') {
                                        $pill = 'bg-sky-50 text-sky-700 border-sky-200';
                                        $label = 'Sakit';
                                    } else {
                                        $pill = 'bg-rose-50 text-rose-700 border-rose-200';
                                        $label = ucfirst($st);
                                    }
                                ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-3.5 font-medium text-slate-900 whitespace-nowrap">
                                        <?= date('d M Y', strtotime($r['tanggal'])) ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 font-mono text-xs whitespace-nowrap">
                                        <?= $r['waktu'] ? date('H:i', strtotime($r['waktu'])) . ' WIB' : '-' ?>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold border <?= $pill ?>">
                                            <?= $label ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-slate-600 max-w-xs truncate">
                                        <?= !empty($r['pesan']) ? htmlspecialchars($r['pesan']) : '<span class="text-slate-300 italic">-</span>' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

        </main>

        <!-- Clean Institutional Footer -->
        <footer class="mt-auto border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Portal Presensi ROHIS. Seluruh data tervalidasi secara digital.
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        lucide.createIcons();

        document.querySelectorAll('.logout-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Keluar',
                    text: 'Apakah Anda yakin ingin mengakhiri sesi dan keluar?',
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
