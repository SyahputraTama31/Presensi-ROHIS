<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Sistem Presensi ROHIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../src/output.css?v=<?= filemtime('../src/output.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="shortcut icon" href="../img/lg.jpg" type="image/x-icon">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased flex flex-col justify-between selection:bg-rohis-700 selection:text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    
    <!-- Top subtle bar -->
    <header class="w-full border-b border-slate-200 bg-white/80 backdrop-blur-sm py-3.5 px-6">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rohis-800 text-white flex items-center justify-center font-bold text-sm tracking-wider">
                    R
                </div>
                <div>
                    <span class="text-sm font-bold text-slate-900 tracking-tight">PRESENSI ROHIS</span>
                    <span class="hidden sm:inline-block text-xs text-slate-400 ml-2 pl-2 border-l border-slate-200">Sistem Presensi Digital</span>
                </div>
            </div>
            <a href="register.php" class="text-xs font-semibold text-rohis-700 hover:text-rohis-900 transition-colors">
                Daftar Akun Baru &rarr;
            </a>
        </div>
    </header>

    <!-- Main Content Form -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md">
            
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8">
                
                <!-- Title & Context -->
                <div class="mb-6 text-center sm:text-left">
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-rohis-50 border border-rohis-200 text-rohis-700 text-xs font-medium mb-3">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        Portal Resmi ROHIS
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Selamat Datang</h1>
                    <p class="text-sm text-slate-500 mt-1">Silakan masukkan username dan password akun Anda.</p>
                </div>

                <!-- Session Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl mb-5 flex items-start gap-2.5 text-rose-800 text-sm">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0 mt-0.5"></i>
                        <span class="text-xs font-medium leading-relaxed"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl mb-5 flex items-start gap-2.5 text-emerald-800 text-sm">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
                        <span class="text-xs font-medium leading-relaxed"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="pros.php" method="post" class="space-y-4">
                    
                    <!-- Username -->
                    <div>
                        <label for="login-username" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                            Username
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </span>
                            <input
                                type="text"
                                id="login-username"
                                name="username"
                                placeholder="Masukkan username"
                                required
                                autocomplete="username"
                                class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all font-medium"
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="login-password" class="block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Password
                            </label>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </span>
                            <input
                                type="password"
                                name="Password"
                                id="login-password"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                                class="w-full pl-10 pr-11 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all font-medium"
                            >
                            <button
                                type="button"
                                onclick="togglePassword('login-password')"
                                aria-label="Tampilkan atau sembunyikan password"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 rounded-md focus:outline-none"
                            >
                                <i data-lucide="eye" id="eye-icon" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full py-2.5 px-4 bg-rohis-800 hover:bg-rohis-900 active:bg-rohis-950 text-white font-semibold text-sm rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>Masuk ke Akun</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>

                </form>

                <!-- Footer Link inside Card -->
                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-500">
                        Belum terdaftar sebagai anggota?
                        <a href="register.php" class="text-rohis-700 font-semibold hover:text-rohis-900 transition-colors ml-1">
                            Daftar sekarang
                        </a>
                    </p>
                </div>

            </div>

            <!-- Context Info -->
            <div class="mt-6 text-center text-xs text-slate-400">
                <p>Mengalami kendala akun? Hubungi Pengurus ROHIS atau Admin sekolah.</p>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full border-t border-slate-200/60 py-4 px-6 text-center text-xs text-slate-400">
        &copy; <?= date('Y') ?> Rohani Islam (ROHIS). Sistem Presensi & Keanggotaan Terintegrasi.
    </footer>

    <script>
        lucide.createIcons();

        function togglePassword(id) {
            const passwordField = document.getElementById(id);
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordField.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>
