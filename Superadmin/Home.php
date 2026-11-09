<?php
require_once '../lng/koneksi.php';
require_once '../lng/auth.php';

// Guard: Hanya Superadmin
$role = require_role(['superadmin']);

$admin_id = $_SESSION['user_id'];
$admin_nama = $_SESSION['nama'] ?? 'Superadmin';
$msg = '';
$msg_type = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Update Lokasi
    if (isset($_POST['action']) && $_POST['action'] === 'update_lokasi') {
        $lat = floatval($_POST['lat_admin'] ?? 0);
        $lng = floatval($_POST['lng_admin'] ?? 0);
        $radius = floatval($_POST['radius_km'] ?? 0.5);
        $jam_mulai = $_POST['jam_mulai'] ?? '07:00:00';
        $jam_selesai = $_POST['jam_selesai'] ?? '09:00:00';
        $stmt = $conn->prepare("UPDATE pengaturan_absen SET lat_admin=?, lng_admin=?, radius_km=?, jam_mulai=?, jam_selesai=? WHERE id=1");
        $stmt->bind_param("dddss", $lat, $lng, $radius, $jam_mulai, $jam_selesai);
        if ($stmt->execute()) { 
            $msg = 'Pengaturan lokasi dan jadwal absensi berhasil disimpan!'; 
            $msg_type = 'success'; 
        } else { 
            $msg = 'Gagal memperbarui pengaturan: ' . $conn->error; 
            $msg_type = 'error'; 
        }
        $stmt->close();
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: ../lng/lgn.php');
        exit;
    }
}

// Fetch data
$pengaturan = $conn->query("SELECT * FROM pengaturan_absen WHERE id=1")->fetch_assoc();
$foto_profil = '';
$stmt_fp = $conn->prepare("SELECT foto_profil FROM users WHERE id = ?");
$stmt_fp->bind_param("i", $admin_id);
$stmt_fp->execute();
$res_fp = $stmt_fp->get_result();
if ($res_fp && $r = $res_fp->fetch_assoc()) {
    $foto_profil = $r['foto_profil'] ?? '';
}
$stmt_fp->close();

$active_menu = 'lokasi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Lokasi - Superadmin Presensi ROHIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../src/output.css?v=<?= filemtime('../src/output.css') ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="shortcut icon" href="../img/lg.jpg" type="image/x-icon">
    <style>
        #map { height: 380px; z-index: 1; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col selection:bg-rohis-700 selection:text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    <!-- Superadmin Sidebar Partial -->
    <?php include 'partials/side.php'; ?>

    <!-- Main Content Area with Desktop Left Margin -->
    <div class="lg:pl-64 flex-1 flex flex-col min-h-screen">
        
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-5xl w-full mx-auto">
            
            <!-- Page Header -->
            <header class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200/80">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-rohis-50 border border-rohis-200 text-rohis-700 text-xs font-semibold">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            Superadmin
                        </span>
                        <span class="text-xs text-slate-400 font-medium"><?= date('l, d F Y') ?></span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Pengaturan Lokasi Presensi</h1>
                    <p class="text-sm text-slate-500 mt-1">Konfigurasi pusat koordinat GPS, radius presensi, dan jadwal buka absensi siswa.</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="getCurrentLocation()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl text-xs font-semibold text-slate-700 shadow-xs transition-colors cursor-pointer">
                        <i data-lucide="crosshair" class="w-4 h-4 text-rohis-700"></i>
                        <span>Deteksi Lokasi Saya</span>
                    </button>
                </div>
            </header>

            <!-- Feedback Notification -->
            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-xl border flex items-start gap-3 text-sm shadow-xs <?= $msg_type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
                    <i data-lucide="<?= $msg_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5 <?= $msg_type === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>"></i>
                    <p class="text-xs font-medium leading-relaxed"><?= htmlspecialchars($msg) ?></p>
                </div>
            <?php endif; ?>

            <!-- Form & Map Card -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                
                <!-- Map Container -->
                <div class="p-5 sm:p-6 pb-0">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="map" class="w-4 h-4 text-rohis-700"></i>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Peta Interaktif Presensi</h2>
                        </div>
                        <span class="text-xs text-slate-400">Klik pada peta atau geser penanda</span>
                    </div>

                    <div id="map" class="w-full rounded-xl border border-slate-200 shadow-inner"></div>
                </div>

                <!-- Form Section -->
                <div class="p-5 sm:p-6 sm:pt-6">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_lokasi">

                        <!-- Section: Koordinat & Radius -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-4 h-4 text-slate-500"></i>
                                Titik Koordinat & Radius Presensi
                            </h3>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                                        Latitude
                                    </label>
                                    <input
                                        type="text"
                                        name="lat_admin"
                                        id="lat_admin"
                                        value="<?= $pengaturan['lat_admin'] ?? 0 ?>"
                                        readonly
                                        required
                                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-700 cursor-not-allowed"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                                        Longitude
                                    </label>
                                    <input
                                        type="text"
                                        name="lng_admin"
                                        id="lng_admin"
                                        value="<?= $pengaturan['lng_admin'] ?? 0 ?>"
                                        readonly
                                        required
                                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-700 cursor-not-allowed"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                                        Radius Toleransi (KM)
                                    </label>
                                    <div class="relative">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max="50"
                                            name="radius_km"
                                            id="radius_km"
                                            value="<?= $pengaturan['radius_km'] ?? 0.5 ?>"
                                            required
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all"
                                        >
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">km</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Waktu Operasional -->
                        <div class="pt-5 border-t border-slate-100">
                            <h3 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                                <i data-lucide="clock" class="w-4 h-4 text-slate-500"></i>
                                Jam Buka & Tutup Presensi Harian
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                                        Jam Mulai Presensi (Buka)
                                    </label>
                                    <input
                                        type="text"
                                        pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]"
                                        placeholder="Contoh: 07:00"
                                        title="Format 24 jam (misal 07:00)"
                                        name="jam_mulai"
                                        value="<?= isset($pengaturan['jam_mulai']) ? date('H:i', strtotime($pengaturan['jam_mulai'])) : '07:00' ?>"
                                        required
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all font-mono"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                                        Jam Selesai Presensi (Tutup)
                                    </label>
                                    <input
                                        type="text"
                                        pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]"
                                        placeholder="Contoh: 09:00"
                                        title="Format 24 jam (misal 09:00)"
                                        name="jam_selesai"
                                        value="<?= isset($pengaturan['jam_selesai']) ? date('H:i', strtotime($pengaturan['jam_selesai'])) : '09:00' ?>"
                                        required
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all font-mono"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-5 border-t border-slate-100 flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-rohis-800 hover:bg-rohis-900 active:bg-rohis-950 text-white rounded-xl text-sm font-semibold transition-all shadow-xs cursor-pointer"
                            >
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Simpan Pengaturan</span>
                            </button>
                        </div>

                    </form>
                </div>

            </div>

        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Portal Presensi ROHIS. Seluruh konfigurasi berlaku secara real-time.
        </footer>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        lucide.createIcons();

        // Leaflet Map Logic
        const initLat = <?= $pengaturan['lat_admin'] ?? 0 ?>;
        const initLng = <?= $pengaturan['lng_admin'] ?? 0 ?>;
        const startLat = (initLat === 0 && initLng === 0) ? -6.200000 : initLat;
        const startLng = (initLat === 0 && initLng === 0) ? 106.816666 : initLng;

        const map = L.map('map').setView([startLat, startLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let marker = null;
        let circle = null;

        function setMarker(lat, lng) {
            if (marker) map.removeLayer(marker);
            if (circle) map.removeLayer(circle);
            
            const pinIcon = L.divIcon({
                html: '<div style="width: 22px; height: 22px; background-color: #194fa6; border: 3px solid white; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
                className: '',
                iconSize: [22, 22],
                iconAnchor: [11, 11]
            });

            marker = L.marker([lat, lng], { draggable: true, icon: pinIcon }).addTo(map);
            const radiusM = parseFloat(document.getElementById('radius_km').value || 0.5) * 1000;
            circle = L.circle([lat, lng], { 
                radius: radiusM, 
                color: '#194fa6', 
                weight: 1.5,
                fillColor: '#5ea6f0', 
                fillOpacity: 0.15 
            }).addTo(map);
            
            document.getElementById('lat_admin').value = lat.toFixed(6);
            document.getElementById('lng_admin').value = lng.toFixed(6);
            
            marker.on('dragend', function(e) {
                const p = e.target.getLatLng();
                setMarker(p.lat, p.lng);
            });
        }

        if (initLat !== 0 || initLng !== 0) {
            setMarker(initLat, initLng);
        }

        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
        });

        document.getElementById('radius_km').addEventListener('input', function() {
            if (marker) {
                const ll = marker.getLatLng();
                setMarker(ll.lat, ll.lng);
            }
        });

        function getCurrentLocation() {
            if (!navigator.geolocation) {
                alert('Geolokasi tidak didukung oleh browser Anda.');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    map.setView([lat, lng], 16);
                    setMarker(lat, lng);
                },
                function(err) {
                    alert('Gagal mendeteksi lokasi: ' + err.message);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }

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
