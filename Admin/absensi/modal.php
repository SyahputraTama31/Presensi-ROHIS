<!-- Modal: Tambah Presensi Manual -->
<div id="modalTambah" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-rohis-50 text-rohis-700 border border-rohis-200 flex items-center justify-center">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Catat Presensi Siswa</h3>
                    <p id="modalTambahSubtitle" class="text-xs text-slate-400">Pencatatan manual oleh Administrator</p>
                </div>
            </div>
            <button type="button" onclick="closeTambahModal()" class="p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Form Body -->
        <div class="p-6 overflow-y-auto flex-1">
            <form method="POST" id="formTambah" class="space-y-4">
                <input type="hidden" name="action" value="tambah_absensi">

                <!-- Pilih Siswa (Searchable Dropdown) -->
                <div class="relative" id="dropdownSiswaContainer">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Nama Siswa</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="searchSiswaInput" placeholder="Ketik nama siswa untuk mencari..." autocomplete="off" required
                            class="w-full pl-9 pr-9 py-2 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all">
                        <button type="button" id="clearSiswaBtn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 hidden cursor-pointer">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                    <input type="hidden" name="user_id" id="selectSiswa" required>

                    <!-- Dropdown List -->
                    <div id="listSiswaDropdown" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-52 overflow-y-auto hidden divide-y divide-slate-100">
                        <?php if (empty($unabsented_users)): ?>
                            <div class="px-3.5 py-2.5 text-xs text-slate-400 italic">Semua siswa sudah tercatat pada tanggal ini.</div>
                        <?php else: ?>
                            <?php foreach ($unabsented_users as $u): ?>
                                <button type="button" 
                                    class="w-full text-left px-3.5 py-2 hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors flex items-center justify-between group cursor-pointer"
                                    data-id="<?= $u['id'] ?>" 
                                    data-name="<?= htmlspecialchars($u['nama']) ?>">
                                    <span><?= htmlspecialchars($u['nama']) ?></span>
                                    <span class="text-[11px] text-rohis-700 font-semibold opacity-0 group-hover:opacity-100 transition-opacity">Pilih &rarr;</span>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tanggal -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Tanggal Presensi</label>
                    <input type="date" id="inputTanggalManual" name="tanggal_manual"
                        value="<?= $filter_tanggal ?: date('Y-m-d') ?>" required
                        class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all">
                </div>

                <!-- Status Kehadiran Radio Pills -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Status Kehadiran</label>
                    <div class="grid grid-cols-2 gap-2.5" id="statusOptions">
                        <!-- HADIR -->
                        <label class="cursor-pointer">
                            <input type="radio" name="status_manual" value="hadir" class="peer sr-only">
                            <div class="flex items-center gap-2.5 p-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-all peer-checked:border-emerald-600 peer-checked:bg-emerald-50/60 peer-checked:text-emerald-800 select-none">
                                <div class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold leading-tight">Hadir</p>
                                    <p class="text-[10px] text-slate-400">Tepat waktu</p>
                                </div>
                            </div>
                        </label>

                        <!-- SAKIT -->
                        <label class="cursor-pointer">
                            <input type="radio" name="status_manual" value="sakit" class="peer sr-only">
                            <div class="flex items-center gap-2.5 p-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-all peer-checked:border-sky-600 peer-checked:bg-sky-50/60 peer-checked:text-sky-800 select-none">
                                <div class="w-6 h-6 rounded-md bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                                    <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold leading-tight">Sakit</p>
                                    <p class="text-[10px] text-slate-400">Surat/keterangan</p>
                                </div>
                            </div>
                        </label>

                        <!-- IZIN -->
                        <label class="cursor-pointer">
                            <input type="radio" name="status_manual" value="izin" class="peer sr-only">
                            <div class="flex items-center gap-2.5 p-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-all peer-checked:border-amber-600 peer-checked:bg-amber-50/60 peer-checked:text-amber-800 select-none">
                                <div class="w-6 h-6 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold leading-tight">Izin</p>
                                    <p class="text-[10px] text-slate-400">Keperluan dinas/ortu</p>
                                </div>
                            </div>
                        </label>

                        <!-- ALPHA -->
                        <label class="cursor-pointer">
                            <input type="radio" name="status_manual" value="alpha" class="peer sr-only" checked>
                            <div class="flex items-center gap-2.5 p-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-all peer-checked:border-rose-600 peer-checked:bg-rose-50/60 peer-checked:text-rose-800 select-none">
                                <div class="w-6 h-6 rounded-md bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold leading-tight">Alpha</p>
                                    <p class="text-[10px] text-slate-400">Tanpa kabar</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Keterangan -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                        Keterangan <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                    </label>
                    <textarea name="pesan_manual" rows="2" placeholder="Contoh: Izin urusan keluarga, sakit flu..."
                        class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-rohis-500/20 focus:border-rohis-600 transition-all resize-none"></textarea>
                </div>

                <!-- Footer Actions -->
                <div class="flex gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeTambahModal()"
                        class="flex-1 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 border border-slate-200 rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 py-2 text-xs font-semibold text-white bg-rohis-800 hover:bg-rohis-900 rounded-lg transition-colors shadow-xs flex items-center justify-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Simpan Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Konfirmasi Bulk Alpha -->
<div id="modalBulkAlpha" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm border border-slate-200 overflow-hidden">
        <div class="p-5 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900 mb-1">Tandai Semua sebagai Alpha?</h3>
            <p class="text-xs text-slate-500 mb-3">
                Sebanyak <span id="bulkJumlah" class="font-bold text-rose-600"></span> siswa yang belum hadir akan otomatis dicatat sebagai <span class="font-bold text-rose-600">Alpha</span>.
            </p>
            <div class="px-3.5 py-2.5 bg-slate-50 rounded-lg border border-slate-200 text-left mb-3 max-h-36 overflow-y-auto">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Daftar Siswa:</p>
                <ul id="bulkListSiswa" class="text-xs text-slate-700 space-y-1"></ul>
            </div>
            <p class="text-[11px] text-slate-400">Keterangan: "Tidak hadir - dicatat otomatis oleh sistem".</p>
        </div>
        <div class="flex border-t border-slate-200 divide-x divide-slate-200">
            <button type="button" onclick="closeBulkAlphaModal()" class="flex-1 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors cursor-pointer">
                Batal
            </button>
            <button type="button" onclick="submitBulkAlpha()" class="flex-1 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                Ya, Tandai Alpha
            </button>
        </div>
    </div>
</div>

<form id="formBulkAlpha" method="POST" class="hidden">
    <input type="hidden" name="action" value="bulk_alpha">
    <input type="hidden" name="tanggal_bulk" value="<?= htmlspecialchars($filter_tanggal) ?>">
    <input type="hidden" name="absent_user_ids" id="bulkUserIds"
        value="<?= implode(',', array_column($absent_users, 'id')) ?>">
</form>

<!-- Modal: Konfirmasi Hapus Presensi -->
<div id="confirmHapusModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm border border-slate-200 overflow-hidden">
        <div class="p-5 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="trash-2" class="w-6 h-6"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900 mb-1">Hapus Data Presensi?</h3>
            <p class="text-xs text-slate-500 mb-3">Catatan presensi berikut akan dihapus secara permanen:</p>
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-left text-xs space-y-1">
                <p class="text-slate-700"><span class="text-slate-400 font-medium">Nama:</span> <span id="hapusNama" class="font-bold"></span></p>
                <p class="text-slate-700"><span class="text-slate-400 font-medium">Tanggal:</span> <span id="hapusTanggal" class="font-mono"></span></p>
            </div>
            <p class="text-[11px] text-rose-500 font-medium mt-3">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="flex border-t border-slate-200 divide-x divide-slate-200">
            <button type="button" onclick="batalHapus()" class="flex-1 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors cursor-pointer">Batal</button>
            <button type="button" onclick="lanjutHapus()" class="flex-1 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- Modal: Lightbox Bukti Foto -->
<div id="photoModal" class="fixed inset-0 z-50 hidden flex flex-col bg-slate-900/90 backdrop-blur-xs">
    <div class="flex items-center justify-between px-5 py-3 shrink-0 bg-slate-900 border-b border-slate-800 text-white">
        <div class="flex items-center gap-2">
            <i data-lucide="image" class="w-4 h-4 text-rohis-400"></i>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-200">Bukti Foto Kehadiran</h3>
        </div>
        <button type="button" onclick="closePhotoModal()" class="px-3 py-1 text-xs font-semibold rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors cursor-pointer">
            Tutup
        </button>
    </div>
    <div id="noPhotoMsg" class="hidden flex-1 flex-col items-center justify-center text-slate-400 text-xs">
        <i data-lucide="camera-off" class="w-8 h-8 text-slate-500 mb-2"></i>
        <span>Bukti foto tidak dilampirkan atau tidak tersedia.</span>
    </div>
    <div id="zoomWrapper" class="hidden flex-1 select-none overflow-hidden flex items-center justify-center p-4" style="cursor:zoom-in;">
        <img id="modalImage" src="" alt="Foto Absen"
            class="max-w-full max-h-full object-contain rounded-lg border border-slate-700 shadow-2xl transition-transform duration-150">
    </div>
    <div id="zoomIndicator" class="hidden shrink-0 py-2.5 flex items-center justify-center gap-3 bg-slate-900 border-t border-slate-800">
        <button type="button" onclick="zoomOut()" class="w-7 h-7 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-200 flex items-center justify-center text-sm font-bold border border-slate-700 cursor-pointer">-</button>
        <span id="zoomLabel" class="text-xs font-mono text-slate-300 w-12 text-center">100%</span>
        <button type="button" onclick="zoomIn()" class="w-7 h-7 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-200 flex items-center justify-center text-sm font-bold border border-slate-700 cursor-pointer">+</button>
    </div>
</div>
