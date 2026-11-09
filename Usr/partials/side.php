<?php
// Usr/partials/side.php
$active_menu = $active_menu ?? '';
$sidebar_nama = $_SESSION['nama'] ?? 'User';
$sidebar_foto = $foto_profil ?? '';
?>
<!-- Mobile Top Header (Visible on < lg) -->
<div class="lg:hidden w-full bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between sticky top-0 z-30 shadow-xs">
    <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-rohis-800 text-white flex items-center justify-center font-bold text-sm">
            R
        </div>
        <div>
            <span class="text-sm font-bold text-slate-900 tracking-tight">ROHIS</span>
            <span class="text-[10px] text-slate-400 block -mt-0.5 font-medium">Portal Siswa</span>
        </div>
    </div>
    <button type="button" onclick="toggleSidebar()" aria-label="Buka Menu" class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer">
        <i data-lucide="menu" class="w-5 h-5"></i>
    </button>
</div>

<!-- Backdrop Overlay for Mobile -->
<div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/40 z-40 hidden lg:hidden transition-opacity"></div>

<!-- Sidebar Container -->
<aside id="sidebarMenu" class="fixed top-0 bottom-0 left-0 w-64 bg-white border-r border-slate-200 flex flex-col z-50 transform -translate-x-full transition-transform duration-200 ease-in-out lg:translate-x-0 shadow-sm lg:shadow-none">
    
    <!-- Header / Brand -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-rohis-800 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                R
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-900 tracking-tight">PRESENSI ROHIS</h1>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Portal Siswa</p>
            </div>
        </div>
        <button type="button" onclick="toggleSidebar()" class="lg:hidden p-1.5 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- User Profile Summary -->
    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60">
        <div class="flex items-center gap-3">
            <?php if (!empty($sidebar_foto)): ?>
                <img src="<?= htmlspecialchars($sidebar_foto) ?>" alt="Avatar" class="w-10 h-10 rounded-full border border-slate-200 object-cover shadow-xs shrink-0">
            <?php else: ?>
                <div class="w-10 h-10 rounded-full bg-rohis-100 border border-rohis-200 text-rohis-700 flex items-center justify-center font-bold text-sm shrink-0">
                    <?= strtoupper(substr($sidebar_nama, 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-900 truncate" title="<?= htmlspecialchars($sidebar_nama) ?>">
                    <?= htmlspecialchars($sidebar_nama) ?>
                </p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span class="text-xs text-slate-500 font-medium">Siswa Aktif</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <p class="px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Navigasi Utama</p>
        
        <a href="home.php" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= $active_menu === 'home' ? 'bg-rohis-50 text-rohis-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
            <i data-lucide="layout-dashboard" class="w-4.5 h-4.5 <?= $active_menu === 'home' ? 'text-rohis-700' : 'text-slate-400' ?>"></i>
            <span>Dashboard</span>
        </a>

        <a href="absen.php" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= $active_menu === 'absen' ? 'bg-rohis-50 text-rohis-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
            <i data-lucide="camera" class="w-4.5 h-4.5 <?= $active_menu === 'absen' ? 'text-rohis-700' : 'text-slate-400' ?>"></i>
            <span>Kamera Presensi</span>
        </a>

        <a href="akun.php" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?= $active_menu === 'akun' ? 'bg-rohis-50 text-rohis-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
            <i data-lucide="user-cog" class="w-4.5 h-4.5 <?= $active_menu === 'akun' ? 'text-rohis-700' : 'text-slate-400' ?>"></i>
            <span>Kelola Akun</span>
        </a>
    </nav>

    <!-- Logout Area -->
    <div class="p-3 border-t border-slate-100">
        <form method="POST" action="home.php" class="logout-form m-0">
            <input type="hidden" name="logout" value="1">
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2.5 text-rose-600 hover:bg-rose-50 rounded-lg text-sm font-semibold transition-colors cursor-pointer">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Keluar Sistem</span>
            </button>
        </form>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebarMenu');
        const overlay = document.getElementById('sidebarOverlay');
        if (!sidebar) return;
        const isClosed = sidebar.classList.contains('-translate-x-full');
        if (isClosed) {
            sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
        }
    }
</script>

