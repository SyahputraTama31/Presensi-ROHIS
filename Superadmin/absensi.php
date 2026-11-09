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
include 'absensi/aksi.php';

// Fetch data & filter
include 'absensi/data.php';

$active_menu = 'absensi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Presensi - Superadmin ROHIS</title>
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
                            <i data-lucide="clipboard-list" class="w-3.5 h-3.5"></i>
                            Manajemen Presensi (Superadmin)
                        </span>
                        <span class="text-xs text-slate-400 font-medium"><?= date('l, d F Y') ?></span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Data Presensi Siswa</h1>
                    <p class="text-sm text-slate-500 mt-1">Pantau, perbarui status, dan catat presensi kegiatan siswa ROHIS.</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="openTambahModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rohis-800 hover:bg-rohis-900 active:bg-rohis-950 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Catat Presensi Manual</span>
                    </button>
                </div>
            </header>

            <!-- Feedback Notification -->
            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-xl border flex items-start gap-3 text-sm shadow-xs <?= $msg_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($msg_type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-rose-50 border-rose-200 text-rose-800') ?>">
                    <i data-lucide="<?= $msg_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5 <?= $msg_type === 'success' ? 'text-emerald-600' : ($msg_type === 'warning' ? 'text-amber-600' : 'text-rose-600') ?>"></i>
                    <p class="text-xs font-medium leading-relaxed"><?= htmlspecialchars($msg) ?></p>
                </div>
            <?php endif; ?>

            <!-- Filter Card -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xs p-5 sm:p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                    
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Tanggal Presensi</label>
                        <div class="relative">
                            <input type="date" name="tanggal" value="<?= htmlspecialchars($filter_tanggal) ?>"
                                class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all font-mono">
                        </div>
                    </div>

                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Cari Nama Siswa</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </span>
                            <input type="text" name="nama" value="<?= htmlspecialchars($filter_nama) ?>" placeholder="Ketik nama siswa..."
                                class="w-full pl-9 pr-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all">
                        </div>
                    </div>

                    <div class="sm:col-span-3 flex gap-2">
                        <button type="submit" class="flex-1 py-2 px-3.5 bg-rohis-800 hover:bg-rohis-900 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                            Terapkan
                        </button>
                        <a href="absensi.php" class="py-2 px-3 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-xs font-semibold transition-colors text-center">
                            Reset
                        </a>
                    </div>

                </form>
            </div>

            <!-- Summary KPI & Bulk Action Cards -->
            <?php if ($show_all): ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <!-- Stat: Sudah Absen -->
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Sudah Presensi</span>
                        <p class="text-2xl font-bold text-emerald-700 mt-0.5"><?= count($absensi_list) ?> <span class="text-xs font-medium text-slate-400">siswa</span></p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                    </div>
                </div>

                <!-- Stat: Belum Absen -->
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Belum Presensi</span>
                        <p class="text-2xl font-bold text-amber-700 mt-0.5"><?= count($absent_users) ?> <span class="text-xs font-medium text-slate-400">siswa</span></p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                </div>

                <!-- Quick Action Bulk Alpha -->
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <?php if (count($absent_users) > 0): ?>
                        <div>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Aksi Massal</span>
                            <p class="text-xs text-slate-600 mt-0.5">Tandai siswa belum hadir</p>
                        </div>
                        <button type="button" onclick="openBulkAlphaModal()"
                            class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-semibold transition-colors cursor-pointer">
                            Tandai <?= count($absent_users) ?> Alpha
                        </button>
                    <?php else: ?>
                        <div>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status Rekap</span>
                            <p class="text-xs font-semibold text-emerald-700 mt-0.5">Seluruh siswa sudah tercatat</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 border border-slate-200 flex items-center justify-center">
                            <i data-lucide="check" class="w-5 h-5"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Data Table Card -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                
                <!-- Tab Switching Bar -->
                <?php if ($show_all && count($absent_users) > 0): ?>
                <div class="px-5 pt-4 pb-0 border-b border-slate-200 flex items-center gap-2">
                    <button type="button" id="tabBtnAll" onclick="switchTab('all')"
                        class="tab-btn active px-3.5 py-2 text-xs font-bold text-slate-700 border-b-2 border-rohis-800 transition-all cursor-pointer">
                        Semua Catatan (<?= count($absensi_list) + count($absent_users) ?>)
                    </button>
                    <button type="button" id="tabBtnAbsent" onclick="switchTab('absent')"
                        class="tab-btn px-3.5 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 border-b-2 border-transparent transition-all flex items-center gap-1.5 cursor-pointer">
                        <span>Belum Presensi</span>
                        <span class="px-1.5 py-0.2 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold"><?= count($absent_users) ?></span>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Table Content Wrapper -->
                <div class="overflow-x-auto">
                    
                    <!-- TAB 1: Semua Data -->
                    <div id="tabAll">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                                <tr>
                                    <th class="px-5 py-3.5">Nama Siswa</th>
                                    <th class="px-5 py-3.5">Waktu Check-In</th>
                                    <th class="px-5 py-3.5">Keterangan</th>
                                    <th class="px-5 py-3.5">Bukti Foto</th>
                                    <th class="px-5 py-3.5">Status & Perubahan Cepat</th>
                                    <th class="px-5 py-3.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                
                                <?php if (empty($absensi_list) && empty($absent_users)): ?>
                                    <tr>
                                        <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                            <i data-lucide="inbox" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                                            <p class="font-semibold text-slate-700">Tidak ada data presensi yang sesuai.</p>
                                            <p class="text-[11px] mt-0.5">Silakan pilih tanggal lain atau sesuaikan kata kunci pencarian.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <!-- List of Recorded Attendance -->
                                <?php foreach ($absensi_list as $a): 
                                    $st = strtolower($a['status'] ?? '');
                                    if ($st === 'hadir') {
                                        $pill_class = 'bg-emerald-50 text-emerald-800 border-emerald-300';
                                    } elseif ($st === 'izin') {
                                        $pill_class = 'bg-amber-50 text-amber-800 border-amber-300';
                                    } elseif ($st === 'sakit') {
                                        $pill_class = 'bg-sky-50 text-sky-800 border-sky-300';
                                    } else {
                                        $pill_class = 'bg-rose-50 text-rose-800 border-rose-300';
                                    }
                                ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-5 py-3.5 font-semibold text-slate-900 whitespace-nowrap">
                                        <?= htmlspecialchars($a['user_nama']) ?>
                                        <span class="block text-[10px] text-slate-400 font-mono font-normal">ID: #<?= $a['id'] ?></span>
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-slate-600 whitespace-nowrap">
                                        <div class="font-bold text-slate-800"><?= $a['waktu'] ? date('H:i', strtotime($a['waktu'])) . ' WIB' : '-' ?></div>
                                        <div class="text-[11px] text-slate-400 font-normal"><?= date('d M Y', strtotime($a['tanggal'])) ?></div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 max-w-xs truncate">
                                        <?= !empty($a['pesan']) ? htmlspecialchars($a['pesan']) : '<span class="text-slate-300 italic">-</span>' ?>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <?php if (!empty($a['foto_base64'])): ?>
                                            <button type="button" onclick="openPhotoModal('foto_<?= $a['id'] ?>')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rohis-50 hover:bg-rohis-100 text-rohis-700 border border-rohis-200 text-xs font-semibold transition-colors cursor-pointer">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                <span>Lihat Foto</span>
                                            </button>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-400 text-[11px] font-medium border border-slate-200">
                                                Tanpa Foto
                                            </span>
                                        <?php endif; ?>
                                        <textarea id="foto_<?= $a['id'] ?>" class="hidden"><?= htmlspecialchars($a['foto_base64'] ?? '') ?></textarea>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <form method="POST" class="flex items-center gap-2 m-0">
                                            <input type="hidden" name="action" value="update_absensi">
                                            <input type="hidden" name="absen_id" value="<?= $a['id'] ?>">
                                            <?php if ($filter_tanggal): ?>
                                                <input type="hidden" name="_redirect_tanggal" value="<?= htmlspecialchars($filter_tanggal) ?>">
                                            <?php endif; ?>
                                            
                                            <select name="new_status" class="px-2 py-1 rounded-lg text-xs font-semibold border <?= $pill_class ?> focus:outline-none focus:ring-1 focus:ring-rohis-500 cursor-pointer">
                                                <option value="hadir" <?= $a['status'] === 'hadir' ? 'selected' : '' ?>>Hadir</option>
                                                <option value="izin"  <?= $a['status'] === 'izin'  ? 'selected' : '' ?>>Izin</option>
                                                <option value="sakit" <?= $a['status'] === 'sakit' ? 'selected' : '' ?>>Sakit</option>
                                                <option value="alpha" <?= $a['status'] === 'alpha' ? 'selected' : '' ?>>Alpha</option>
                                            </select>

                                            <input type="text" name="new_pesan" value="<?= htmlspecialchars($a['pesan'] ?? '') ?>" placeholder="Keterangan..."
                                                class="w-32 px-2.5 py-1 rounded-lg text-xs border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-rohis-500 text-slate-700">

                                            <button type="submit" class="px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer">
                                                Simpan
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <form method="POST" class="m-0"
                                            onsubmit="return confirmHapus(event, '<?= htmlspecialchars(addslashes($a['user_nama'])) ?>', '<?= date('d M Y', strtotime($a['tanggal'])) ?>')">
                                            <input type="hidden" name="action" value="delete_absensi">
                                            <input type="hidden" name="absen_id" value="<?= $a['id'] ?>">
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Hapus Data">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <!-- List of Students Not Yet Checked In (At bottom of all tab) -->
                                <?php if ($show_all && count($absent_users) > 0): ?>
                                    <?php foreach ($absent_users as $u): ?>
                                    <tr class="bg-amber-50/30 hover:bg-amber-50/60 transition-colors">
                                        <td class="px-5 py-3.5 font-semibold text-slate-900 whitespace-nowrap">
                                            <?= htmlspecialchars($u['nama']) ?>
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded-md bg-amber-100 text-amber-800 text-[10px] font-semibold ml-1.5">
                                                Belum Presensi
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-slate-400 font-mono whitespace-nowrap">
                                            - <span class="text-[11px] text-slate-400 block"><?= date('d M Y', strtotime($filter_tanggal)) ?></span>
                                        </td>
                                        <td class="px-5 py-3.5 text-slate-300 italic">-</td>
                                        <td class="px-5 py-3.5 text-slate-400 text-[11px]">-</td>
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            <button type="button" onclick="openTambahModalFor(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nama'])) ?>', '<?= $filter_tanggal ?>')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-rohis-50 hover:bg-rohis-100 text-rohis-700 border border-rohis-200 rounded-lg text-xs font-semibold transition-colors cursor-pointer">
                                                <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                                <span>Catat Status</span>
                                            </button>
                                        </td>
                                        <td class="px-5 py-3.5 text-center text-slate-300">-</td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>

                    <!-- TAB 2: Hanya Siswa Belum Absen -->
                    <?php if ($show_all && count($absent_users) > 0): ?>
                    <div id="tabAbsent" class="hidden">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                                <tr>
                                    <th class="px-5 py-3.5">Nama Siswa</th>
                                    <th class="px-5 py-3.5">Tanggal</th>
                                    <th class="px-5 py-3.5">Status</th>
                                    <th class="px-5 py-3.5">Aksi Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($absent_users as $u): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-5 py-3.5 font-semibold text-slate-900">
                                        <?= htmlspecialchars($u['nama']) ?>
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-slate-600">
                                        <?= date('d M Y', strtotime($filter_tanggal)) ?>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold">
                                            Belum Presensi
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <button type="button" onclick="openTambahModalFor(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nama'])) ?>', '<?= $filter_tanggal ?>')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 bg-rohis-800 hover:bg-rohis-900 text-white rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            <span>Catat Presensi</span>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Portal Presensi ROHIS. Rekapitulasi kehadiran terpusat.
        </footer>

    </div>

    <!-- Modals & Scripts -->
    <?php include 'absensi/modal.php'; ?>
    <?php include 'absensi/js.php'; ?>
</body>
</html>
