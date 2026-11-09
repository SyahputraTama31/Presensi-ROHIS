<?php
session_start();
include '../lng/koneksi.php';

// Check admin session
$role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($role, ['admin', 'superadmin'])) {
    header('Location: ../lng/lgn.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin_nama = $_SESSION['nama'] ?? 'Admin';
$msg = '';
$msg_type = '';

// Auto-create foto_profil column if not exists
$col_check = $conn->query("SHOW COLUMNS FROM users LIKE 'foto_profil'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN foto_profil LONGTEXT NULL");
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Foto Profil
    if (isset($_POST['action']) && $_POST['action'] === 'update_foto') {
        $foto_data = $_POST['foto_base64'] ?? '';
        if (!empty($foto_data)) {
            $stmt = $conn->prepare("UPDATE users SET foto_profil=? WHERE id=?");
            $stmt->bind_param("si", $foto_data, $admin_id);
            if ($stmt->execute()) {
                $msg = 'Foto profil Administrator berhasil diperbarui!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal memperbarui foto profil.';
                $msg_type = 'error';
            }
            $stmt->close();
        }
    }

    // Hapus Foto Profil
    if (isset($_POST['action']) && $_POST['action'] === 'hapus_foto') {
        $empty = '';
        $stmt = $conn->prepare("UPDATE users SET foto_profil=? WHERE id=?");
        $stmt->bind_param("si", $empty, $admin_id);
        if ($stmt->execute()) {
            $msg = 'Foto profil berhasil dihapus!';
            $msg_type = 'success';
        } else {
            $msg = 'Gagal menghapus foto profil.';
            $msg_type = 'error';
        }
        $stmt->close();
    }

    // Update Akun Admin
    if (isset($_POST['action']) && $_POST['action'] === 'update_akun') {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($nama) || empty($username)) {
            $msg = 'Nama dan username tidak boleh kosong.';
            $msg_type = 'error';
        } else {
            $cek = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $cek->bind_param("si", $username, $admin_id);
            $cek->execute();
            if ($cek->get_result()->num_rows > 0) {
                $msg = 'Username sudah digunakan oleh akun lain.';
                $msg_type = 'error';
            } else {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, password=? WHERE id=?");
                    $stmt->bind_param("sssi", $nama, $username, $hashed, $admin_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET nama=?, username=? WHERE id=?");
                    $stmt->bind_param("ssi", $nama, $username, $admin_id);
                }

                if ($stmt->execute()) {
                    $_SESSION['nama'] = $nama;
                    $_SESSION['username'] = $username;
                    $admin_nama = $nama;
                    $msg = 'Profil akun Administrator berhasil diperbarui!';
                    $msg_type = 'success';
                } else {
                    $msg = 'Gagal memperbarui profil: ' . $conn->error;
                    $msg_type = 'error';
                }
                $stmt->close();
            }
            $cek->close();
        }
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: ../lng/lgn.php');
        exit;
    }
}

// Fetch Admin Data
$admin_data = $conn->query("SELECT nama, username, foto_profil FROM users WHERE id = $admin_id")->fetch_assoc();
$foto_profil = $admin_data['foto_profil'] ?? '';
$active_menu = 'akun';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Akun - Admin Presensi ROHIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../src/output.css?v=<?= filemtime('../src/output.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="shortcut icon" href="../img/lg.jpg" type="image/x-icon">
    <style>
        #cameraVideo { transform: scaleX(-1); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col selection:bg-rohis-700 selection:text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    <!-- Sidebar Component -->
    <?php include 'partials/side.php'; ?>

    <!-- Main Content Area with Desktop Left Margin -->
    <div class="lg:pl-64 flex-1 flex flex-col min-h-screen">
        
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-4xl w-full mx-auto">
            
            <!-- Page Header -->
            <header class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200/80">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-rohis-50 border border-rohis-200 text-rohis-700 text-xs font-semibold">
                            <i data-lucide="user-cog" class="w-3.5 h-3.5"></i>
                            Akun Administrator
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Kelola Akun Admin</h1>
                    <p class="text-sm text-slate-500 mt-1">Perbarui profil administrator, foto avatar, dan kata sandi masuk.</p>
                </div>
            </header>

            <!-- Feedback Notification -->
            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-xl border flex items-start gap-3 text-sm shadow-xs <?= $msg_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
                    <i data-lucide="<?= $msg_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5 <?= $msg_type === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>"></i>
                    <p class="text-xs font-medium leading-relaxed"><?= htmlspecialchars($msg) ?></p>
                </div>
            <?php endif; ?>

            <!-- Profile & Settings Card -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                
                <!-- Card Header with Subtle Background -->
                <div class="px-6 py-5 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="relative group cursor-pointer" onclick="showPhotoOptions()" title="Klik untuk mengubah foto profil">
                            <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-white shadow-xs bg-rohis-100 flex items-center justify-center">
                                <?php if (!empty($foto_profil)): ?>
                                    <img id="avatarPreview" src="<?= htmlspecialchars($foto_profil) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-rohis-700 font-bold text-2xl">
                                        <?= strtoupper(substr($admin_data['nama'] ?? 'A', 0, 1)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="absolute inset-0 bg-slate-950/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <i data-lucide="camera" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-base font-bold text-slate-900"><?= htmlspecialchars($admin_data['nama'] ?? 'Admin') ?></h2>
                            <p class="text-xs text-slate-500 font-mono">@<?= htmlspecialchars($admin_data['username'] ?? '') ?></p>
                            <button type="button" onclick="showPhotoOptions()" class="mt-1 text-xs font-semibold text-rohis-700 hover:text-rohis-900 inline-flex items-center gap-1 cursor-pointer">
                                <i data-lucide="edit-3" class="w-3 h-3"></i>
                                Ganti Foto Profil
                            </button>
                        </div>
                    </div>

                    <div class="text-xs text-slate-400">
                        Hak Akses: <span class="font-semibold text-slate-700 capitalize"><?= htmlspecialchars($role) ?></span>
                    </div>
                </div>

                <!-- Form Body -->
                <div class="p-6 sm:p-8">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_akun">

                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                                Nama Lengkap
                            </label>
                            <div class="relative max-w-lg">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="user" class="w-4 h-4"></i>
                                </span>
                                <input
                                    type="text"
                                    name="nama"
                                    value="<?= htmlspecialchars($admin_data['nama'] ?? '') ?>"
                                    required
                                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all font-medium"
                                >
                            </div>
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                                Username
                            </label>
                            <div class="relative max-w-lg">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="at-sign" class="w-4 h-4"></i>
                                </span>
                                <input
                                    type="text"
                                    name="username"
                                    value="<?= htmlspecialchars($admin_data['username'] ?? '') ?>"
                                    required
                                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all font-medium"
                                >
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                                Password Baru <span class="text-slate-400 font-normal lowercase">(kosongkan jika tidak ingin mengubah)</span>
                            </label>
                            <div class="relative max-w-lg">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="lock" class="w-4 h-4"></i>
                                </span>
                                <input
                                    type="password"
                                    name="password"
                                    placeholder="••••••••"
                                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all font-medium"
                                >
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-rohis-800 hover:bg-rohis-900 active:bg-rohis-950 text-white rounded-xl text-sm font-semibold transition-all shadow-xs cursor-pointer"
                            >
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Simpan Perubahan Akun</span>
                            </button>
                        </div>

                    </form>
                </div>

            </div>

        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Portal Presensi ROHIS. Seluruh data tervalidasi secara digital.
        </footer>

    </div>

    <!-- Modal: Ubah Foto Profil Options -->
    <div id="photoOptionsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full border border-slate-200 overflow-hidden" id="photoOptionsContent">
            <div class="flex justify-between items-center px-5 py-4 border-b border-slate-100">
                <div class="flex items-center gap-2 text-slate-800">
                    <i data-lucide="camera" class="w-4.5 h-4.5 text-rohis-700"></i>
                    <h3 class="text-sm font-bold">Ubah Foto Profil Admin</h3>
                </div>
                <button type="button" onclick="closePhotoOptions()" class="p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="p-4 space-y-2.5">
                <button type="button" onclick="pickFromGallery()" class="w-full flex items-center gap-3.5 p-3 rounded-xl border border-slate-200 hover:border-rohis-300 hover:bg-rohis-50/50 transition-all text-left cursor-pointer">
                    <div class="w-10 h-10 rounded-lg bg-rohis-50 text-rohis-700 border border-rohis-200 flex items-center justify-center shrink-0">
                        <i data-lucide="image" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-slate-800">Pilih dari Galeri</p>
                        <p class="text-xs text-slate-500">Unggah file foto dari perangkat Anda</p>
                    </div>
                </button>

                <button type="button" onclick="openCamera()" class="w-full flex items-center gap-3.5 p-3 rounded-xl border border-slate-200 hover:border-rohis-300 hover:bg-rohis-50/50 transition-all text-left cursor-pointer">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0">
                        <i data-lucide="camera" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-slate-800">Ambil Selfie</p>
                        <p class="text-xs text-slate-500">Gunakan kamera untuk foto langsung</p>
                    </div>
                </button>

                <?php if (!empty($foto_profil)): ?>
                    <button type="button" onclick="deletePhoto()" class="w-full flex items-center gap-3.5 p-3 rounded-xl border border-rose-200 hover:bg-rose-50/50 transition-all text-left cursor-pointer">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm text-rose-600">Hapus Foto Profil</p>
                            <p class="text-xs text-slate-500">Gunakan inisial default</p>
                        </div>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal: Kamera Selfie -->
    <div id="cameraModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full border border-slate-200 overflow-hidden">
            <div class="flex justify-between items-center px-5 py-4 border-b border-slate-100">
                <div class="flex items-center gap-2 text-slate-800">
                    <i data-lucide="camera" class="w-4 h-4 text-emerald-600"></i>
                    <h3 class="text-sm font-bold">Ambil Foto Wajah</h3>
                </div>
                <button type="button" onclick="closeCamera()" class="p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="p-4">
                <div class="rounded-xl overflow-hidden bg-slate-950 mb-4 aspect-square relative border border-slate-200">
                    <video id="cameraVideo" autoplay playsinline class="w-full h-full object-cover"></video>
                    <canvas id="cameraCanvas" class="hidden"></canvas>
                </div>
                <button type="button" onclick="capturePhoto()" class="w-full py-2.5 px-4 bg-rohis-800 hover:bg-rohis-900 text-white rounded-xl font-semibold text-sm flex items-center justify-center gap-2 shadow-xs cursor-pointer">
                    <i data-lucide="camera" class="w-4 h-4"></i>
                    <span>Ambil Foto</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden Forms & Inputs -->
    <input type="file" id="fileInput" accept="image/*" class="hidden" onchange="handleFileSelect(event)">
    <form id="photoForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="update_foto">
        <input type="hidden" name="foto_base64" id="fotoBase64">
    </form>
    <form id="deletePhotoForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="hapus_foto">
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        lucide.createIcons();

        let cameraStream = null;

        function deletePhoto() {
            closePhotoOptions();
            Swal.fire({
                title: 'Hapus Foto Profil',
                text: 'Apakah Anda yakin ingin menghapus foto profil dan kembali ke avatar inisial?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#be123c',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deletePhotoForm').submit();
                }
            });
        }

        function showPhotoOptions() {
            const modal = document.getElementById('photoOptionsModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closePhotoOptions() {
            const modal = document.getElementById('photoOptionsModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }

        function pickFromGallery() {
            closePhotoOptions();
            setTimeout(() => document.getElementById('fileInput').click(), 200);
        }

        function handleFileSelect(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(ev) {
                submitPhoto(ev.target.result);
            };
            reader.readAsDataURL(file);
        }

        function openCamera() {
            closePhotoOptions();
            setTimeout(async () => {
                const modal = document.getElementById('cameraModal');
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 640 } },
                        audio: false
                    });
                    document.getElementById('cameraVideo').srcObject = cameraStream;
                } catch (err) {
                    alert('Tidak dapat mengakses kamera. Pastikan izin kamera sudah diberikan di browser.');
                    closeCamera();
                }
            }, 200);
        }

        function capturePhoto() {
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            const size = Math.min(video.videoWidth, video.videoHeight);
            canvas.width = 400;
            canvas.height = 400;
            const ctx = canvas.getContext('2d');
            ctx.translate(400, 0);
            ctx.scale(-1, 1);
            const sx = (video.videoWidth - size) / 2;
            const sy = (video.videoHeight - size) / 2;
            ctx.drawImage(video, sx, sy, size, size, 0, 0, 400, 400);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
            closeCamera();
            submitPhoto(dataUrl);
        }

        function closeCamera() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(t => t.stop());
                cameraStream = null;
            }
            const modal = document.getElementById('cameraModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }

        function submitPhoto(base64) {
            document.getElementById('fotoBase64').value = base64;
            document.getElementById('photoForm').submit();
        }

        document.querySelectorAll('.logout-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Keluar',
                    text: 'Apakah Anda yakin ingin mengakhiri sesi Administrator?',
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
