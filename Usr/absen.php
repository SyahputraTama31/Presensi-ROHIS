<?php
session_start();
include '../lng/koneksi.php';

$user_id = $_SESSION['user_id'] ?? 0;
$message = '';
$message_type = '';

if (!$user_id) {
    header("Location: ../lng/lgn.php");
    exit();
}

// Ambil info pengaturan absen untuk display
$pengaturan = $conn->query("SELECT * FROM pengaturan_absen WHERE id = 1")->fetch_assoc();
$jam_mulai = $pengaturan['jam_mulai'] ?? '07:00:00';
$jam_selesai = $pengaturan['jam_selesai'] ?? '09:00:00';
$radius_km = $pengaturan['radius_km'] ?? 0.5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = date('Y-m-d');
    $jam = date('H:i:s');
    $lat = floatval($_POST['lat'] ?? 0);
    $lng = floatval($_POST['lng'] ?? 0);
    $foto_base64 = $_POST['foto_base64'] ?? '';
    $pesan = trim($_POST['pesan'] ?? '');
    
    // Check double absen hari ini
    $check_stmt = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?");
    $check_stmt->bind_param("is", $user_id, $tanggal);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $message = 'Anda sudah melakukan presensi pada hari ini.';
        $message_type = 'error';
    } else {
        // Cek jam absensi
        $jam_sekarang = date('H:i:s');
        
        $is_valid_time = false;
        if ($jam_mulai <= $jam_selesai) {
            $is_valid_time = ($jam_sekarang >= $jam_mulai && $jam_sekarang <= $jam_selesai);
        } else {
            // Jika jam buka melewati tengah malam (misal 21:00 - 12:00)
            $is_valid_time = ($jam_sekarang >= $jam_mulai || $jam_sekarang <= $jam_selesai);
        }
        
        if (!$is_valid_time) {
            $message = 'Presensi di luar jam operasional (Dibuka pukul ' . date('H:i', strtotime($jam_mulai)) . ' - ' . date('H:i', strtotime($jam_selesai)) . ' WIB).';
            $message_type = 'error';
        } else {
            // Calculate distance
            $lat_admin = floatval($pengaturan['lat_admin'] ?? 0);
            $lng_admin = floatval($pengaturan['lng_admin'] ?? 0);
            
            $earth_radius = 6371;
            $dlat = deg2rad($lat - $lat_admin);
            $dlng = deg2rad($lng - $lng_admin);
            $a = sin($dlat/2) * sin($dlat/2) + cos(deg2rad($lat_admin)) * cos(deg2rad($lat)) * sin($dlng/2) * sin($dlng/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $distance = $earth_radius * $c;
            
            if ($distance > $radius_km) {
                $message = 'Anda berada di luar jangkauan lokasi presensi (' . round($distance * 1000) . ' m dari titik pusat, toleransi maksimal ' . round($radius_km * 1000) . ' m).';
                $message_type = 'error';
            } else {
                // Insert absensi
                $stmt = $conn->prepare("INSERT INTO absensi (user_id, tanggal, waktu, lat, lng, foto_base64, pesan, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'hadir')");
                $stmt->bind_param("issddss", $user_id, $tanggal, $jam, $lat, $lng, $foto_base64, $pesan);
                if ($stmt->execute()) {
                    $message = 'Presensi kehadiran Anda berhasil disimpan!';
                    $message_type = 'success';
                } else {
                    $message = 'Terjadi kesalahan sistem: ' . $conn->error;
                    $message_type = 'error';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kamera Presensi - ROHIS Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../src/output.css?v=<?= filemtime('../src/output.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="shortcut icon" href="../img/lg.jpg" type="image/x-icon">
    <style>
        #videoPreview { transform: scaleX(-1); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col selection:bg-rohis-700 selection:text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    <!-- Top Navigation Header -->
    <header class="w-full bg-white border-b border-slate-200 py-3.5 px-4 sm:px-6 sticky top-0 z-30 shadow-xs">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="home.php" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors" title="Kembali ke Dashboard">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-sm font-bold text-slate-900 tracking-tight">Presensi Kehadiran</h1>
                    <p class="text-[11px] text-slate-400 font-medium">Verifikasi Kamera & Koordinat GPS</p>
                </div>
            </div>
            <a href="home.php" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
                Dashboard
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-3xl w-full mx-auto">
        
        <!-- Contextual Operational Rules Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 text-slate-600">
                    <div class="w-7 h-7 rounded-md bg-rohis-50 text-rohis-700 border border-rohis-200 flex items-center justify-center shrink-0">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="font-semibold text-slate-700">Jam Operasional:</span>
                        <span class="ml-1 font-mono text-slate-900 font-medium"><?= date('H:i', strtotime($jam_mulai)) ?> - <?= date('H:i', strtotime($jam_selesai)) ?> WIB</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-slate-600">
                    <div class="w-7 h-7 rounded-md bg-rohis-50 text-rohis-700 border border-rohis-200 flex items-center justify-center shrink-0">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="font-semibold text-slate-700">Radius Lokasi:</span>
                        <span class="ml-1 text-slate-900 font-medium">Maksimal <?= round($radius_km * 1000) ?> meter</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl border flex items-start gap-3 text-sm shadow-xs <?= $message_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
                <i data-lucide="<?= $message_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5 <?= $message_type === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>"></i>
                <div>
                    <p class="font-bold text-xs uppercase tracking-wider"><?= $message_type === 'success' ? 'Berhasil' : 'Perhatian' ?></p>
                    <p class="text-xs mt-0.5 leading-relaxed"><?= htmlspecialchars($message) ?></p>
                    <?php if ($message_type === 'success'): ?>
                        <a href="home.php" class="inline-flex items-center gap-1.5 mt-2 text-xs font-bold text-emerald-700 hover:text-emerald-900 underline">
                            Kembali ke Dashboard &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Attendance Scanner Card -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-5 sm:p-7">
                
                <form method="POST" id="absenForm" class="space-y-5">
                    <input type="hidden" name="lat" id="lat">
                    <input type="hidden" name="lng" id="lng">
                    <input type="hidden" name="foto_base64" id="foto_base64">

                    <!-- Camera / Viewfinder Box -->
                    <div class="relative w-full rounded-xl overflow-hidden bg-slate-900 border border-slate-200 aspect-[4/3] sm:aspect-[16/10] flex items-center justify-center">
                        
                        <!-- State 1: Placeholder before activation -->
                        <div id="cameraPlaceholder" class="w-full h-full bg-slate-100 flex flex-col items-center justify-center p-6 text-center text-slate-500">
                            <div class="w-14 h-14 rounded-full bg-white border border-slate-200 shadow-xs flex items-center justify-center mb-3 text-slate-400">
                                <i data-lucide="camera" class="w-7 h-7"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800">Kamera & Lokasi Belum Aktif</h3>
                            <p class="text-xs text-slate-500 max-w-xs mt-1">
                                Klik tombol di bawah untuk mengaktifkan izin kamera wajah dan posisi koordinat GPS.
                            </p>
                        </div>

                        <!-- State 2: Video Stream -->
                        <div id="videoContainer" class="hidden w-full h-full relative">
                            <video id="videoPreview" class="w-full h-full object-cover" autoplay playsinline></video>
                            
                            <!-- Clean Viewfinder Corners overlay -->
                            <div class="absolute inset-8 pointer-events-none flex flex-col justify-between">
                                <div class="flex justify-between">
                                    <div class="w-6 h-6 border-t-2 border-l-2 border-white/80 rounded-tl-sm"></div>
                                    <div class="w-6 h-6 border-t-2 border-r-2 border-white/80 rounded-tr-sm"></div>
                                </div>
                                <div class="flex justify-between">
                                    <div class="w-6 h-6 border-b-2 border-l-2 border-white/80 rounded-bl-sm"></div>
                                    <div class="w-6 h-6 border-b-2 border-r-2 border-white/80 rounded-br-sm"></div>
                                </div>
                            </div>

                            <!-- Live Tag -->
                            <div class="absolute top-3 left-3 bg-slate-900/70 backdrop-blur-xs text-white text-[11px] font-mono px-2.5 py-1 rounded-md flex items-center gap-1.5 border border-white/10">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Kamera Aktif
                            </div>
                        </div>

                        <!-- State 3: Captured Photo Preview -->
                        <div id="resultContainer" class="hidden w-full h-full relative">
                            <img id="fotoPreview" class="w-full h-full object-cover" alt="Preview Foto">
                            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-slate-950/80 via-slate-950/40 to-transparent p-4 flex items-center justify-between text-white text-xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="map-pin" class="w-4 h-4 text-emerald-400"></i>
                                    <span id="coords" class="font-mono text-[11px]">Koordinat tercatat</span>
                                </div>
                                <span class="text-[10px] uppercase font-semibold tracking-wider text-emerald-300">Foto Siap</span>
                            </div>
                        </div>

                    </div>

                    <!-- Geolocation Status Pill (Visible once location retrieved) -->
                    <div id="geoPill" class="hidden flex items-center gap-2 p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span class="font-medium">Posisi GPS Terdeteksi:</span>
                        <span id="geoText" class="font-mono font-semibold text-slate-800"></span>
                    </div>

                    <!-- Optional Note / Message Container -->
                    <div id="pesanContainer" class="hidden pt-3 border-t border-slate-100">
                        <label for="pesan" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                            Catatan / Motivasi Hari Ini <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                        </label>
                        <textarea
                            name="pesan"
                            id="pesan"
                            rows="2"
                            placeholder="Tuliskan catatan kehadiran atau motivasi singkat..."
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all resize-none"
                        ></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-2">
                        
                        <!-- Step 1: Start Button -->
                        <button
                            type="button"
                            id="startBtn"
                            class="w-full py-3 px-4 bg-rohis-800 hover:bg-rohis-900 active:bg-rohis-950 text-white font-semibold text-sm rounded-xl transition-all shadow-xs flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i data-lucide="power" class="w-4.5 h-4.5"></i>
                            <span>Aktifkan Kamera & Deteksi Lokasi</span>
                        </button>
                        
                        <!-- Step 2: Capture Button -->
                        <button
                            type="button"
                            id="captureBtn"
                            class="hidden w-full py-3 px-4 bg-rohis-700 hover:bg-rohis-800 active:bg-rohis-900 text-white font-semibold text-sm rounded-xl transition-all shadow-xs flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i data-lucide="camera" class="w-4.5 h-4.5"></i>
                            <span>Ambil Foto Kehadiran</span>
                        </button>

                        <!-- Step 3: Retake & Submit Group -->
                        <div id="submitGroup" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                type="button"
                                id="retakeBtn"
                                class="w-full py-2.5 px-4 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <i data-lucide="rotate-ccw" class="w-4 h-4 text-slate-500"></i>
                                <span>Foto Ulang</span>
                            </button>
                            <button
                                type="submit"
                                id="submitBtn"
                                class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold text-sm rounded-xl transition-all shadow-xs flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <i data-lucide="send" class="w-4 h-4"></i>
                                <span>Kirim Presensi Sekarang</span>
                            </button>
                        </div>

                    </div>

                </form>

            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-slate-200/80 py-4 px-6 text-center text-xs text-slate-400">
        &copy; <?= date('Y') ?> Portal Presensi ROHIS. Verifikasi waktu dan koordinat tervalidasi otomatis.
    </footer>

    <!-- Scripts -->
    <script>
        lucide.createIcons();

        let stream = null;
        let fotoData = null;
        const video = document.getElementById('videoPreview');
        const startBtn = document.getElementById('startBtn');
        const captureBtn = document.getElementById('captureBtn');
        const retakeBtn = document.getElementById('retakeBtn');
        const submitGroup = document.getElementById('submitGroup');
        const cameraPlaceholder = document.getElementById('cameraPlaceholder');
        const videoContainer = document.getElementById('videoContainer');
        const resultContainer = document.getElementById('resultContainer');
        const pesanContainer = document.getElementById('pesanContainer');
        const geoPill = document.getElementById('geoPill');
        const geoText = document.getElementById('geoText');
        
        startBtn.onclick = async function() {
            try {
                startBtn.innerHTML = '<i data-lucide="loader" class="w-4.5 h-4.5 animate-spin"></i><span>Meminta Akses Lokasi & Kamera...</span>';
                startBtn.disabled = true;
                lucide.createIcons();
                
                // Get Location
                const pos = await new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }));
                const latVal = pos.coords.latitude;
                const lngVal = pos.coords.longitude;
                document.getElementById('lat').value = latVal;
                document.getElementById('lng').value = lngVal;
                
                const coordStr = `${latVal.toFixed(6)}, ${lngVal.toFixed(6)}`;
                document.getElementById('coords').textContent = coordStr;
                geoText.textContent = coordStr;
                geoPill.classList.remove('hidden');
                
                // Request User Camera
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: "user" } 
                });
                video.srcObject = stream;
                
                cameraPlaceholder.classList.add('hidden');
                videoContainer.classList.remove('hidden');
                
                startBtn.classList.add('hidden');
                captureBtn.classList.remove('hidden');
                
            } catch (err) {
                alert('Gagal mengakses perangkat: Pastikan izin lokasi (GPS) dan kamera diizinkan di browser Anda.\n\nDetail: ' + err.message);
                startBtn.innerHTML = '<i data-lucide="power" class="w-4.5 h-4.5"></i><span>Aktifkan Kamera & Deteksi Lokasi</span>';
                startBtn.disabled = false;
                lucide.createIcons();
            }
        };

        captureBtn.onclick = function() {
            const canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 480;
            
            const ctx = canvas.getContext('2d');
            // Mirror image horizontally to match preview
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            fotoData = canvas.toDataURL('image/jpeg', 0.85);
            
            document.getElementById('foto_base64').value = fotoData;
            document.getElementById('fotoPreview').src = fotoData;
            
            videoContainer.classList.add('hidden');
            resultContainer.classList.remove('hidden');
            pesanContainer.classList.remove('hidden');
            
            captureBtn.classList.add('hidden');
            submitGroup.classList.remove('hidden');
            
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        };

        retakeBtn.onclick = function() {
            resultContainer.classList.add('hidden');
            pesanContainer.classList.add('hidden');
            submitGroup.classList.add('hidden');
            captureBtn.classList.remove('hidden');
            
            startBtn.onclick();
        };
        
        window.onunload = () => {
            if (stream) stream.getTracks().forEach(track => track.stop());
        };
    </script>
</body>
</html>
