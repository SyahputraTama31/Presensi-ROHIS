<!-- Scripts -->
<script>
lucide.createIcons();

// Tab switcher
function switchTab(tab) {
    var all    = document.getElementById('tabAll');
    var absent = document.getElementById('tabAbsent');
    var btnAll = document.getElementById('tabBtnAll');
    var btnAbs = document.getElementById('tabBtnAbsent');
    if (!all || !absent) return;

    if (tab === 'all') {
        all.classList.remove('hidden');
        absent.classList.add('hidden');
        btnAll.classList.add('active');
        if (btnAbs) btnAbs.classList.remove('active');
    } else {
        all.classList.add('hidden');
        absent.classList.remove('hidden');
        if (btnAbs) btnAbs.classList.add('active');
        btnAll.classList.remove('active');
    }
}

// Modal Tambah Manual (Searchable Dropdown initialization & helper functions)
document.addEventListener('DOMContentLoaded', function() {
    const searchSiswaInput = document.getElementById('searchSiswaInput');
    const selectSiswaHidden = document.getElementById('selectSiswa');
    const listSiswaDropdown = document.getElementById('listSiswaDropdown');
    const clearSiswaBtn = document.getElementById('clearSiswaBtn');
    
    if (searchSiswaInput && listSiswaDropdown) {
        const dropdownButtons = listSiswaDropdown.querySelectorAll('button[data-id]');

        // Toggle dropdown on focus / click
        searchSiswaInput.addEventListener('focus', function() {
            listSiswaDropdown.classList.remove('hidden');
        });

        // Close dropdown if clicked outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('dropdownSiswaContainer');
            if (container && !container.contains(e.target)) {
                listSiswaDropdown.classList.add('hidden');
            }
        });

        // Input event for searching
        searchSiswaInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            if (query.length > 0) {
                clearSiswaBtn.classList.remove('hidden');
            } else {
                clearSiswaBtn.classList.add('hidden');
            }
            
            let hasMatch = false;
            dropdownButtons.forEach(function(btn) {
                const name = btn.getAttribute('data-name').toLowerCase();
                if (name.includes(query)) {
                    btn.classList.remove('hidden');
                    hasMatch = true;
                } else {
                    btn.classList.add('hidden');
                }
            });
        });

        // Selection event
        dropdownButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                
                selectSiswaHidden.value = id;
                searchSiswaInput.value = name;
                
                clearSiswaBtn.classList.remove('hidden');
                listSiswaDropdown.classList.add('hidden');
            });
        });

        // Clear selection
        clearSiswaBtn.addEventListener('click', function() {
            selectSiswaHidden.value = '';
            searchSiswaInput.value = '';
            clearSiswaBtn.classList.add('hidden');
            dropdownButtons.forEach(function(btn) { btn.classList.remove('hidden'); });
            searchSiswaInput.focus();
        });
    }
});

function openTambahModal() {
    const selectSiswaHidden = document.getElementById('selectSiswa');
    const searchSiswaInput = document.getElementById('searchSiswaInput');
    const clearSiswaBtn = document.getElementById('clearSiswaBtn');
    const listSiswaDropdown = document.getElementById('listSiswaDropdown');

    if (selectSiswaHidden) selectSiswaHidden.value = '';
    if (searchSiswaInput) searchSiswaInput.value = '';
    if (clearSiswaBtn) clearSiswaBtn.classList.add('hidden');
    if (listSiswaDropdown) {
        const dropdownButtons = listSiswaDropdown.querySelectorAll('button[data-id]');
        dropdownButtons.forEach(function(btn) { btn.classList.remove('hidden'); });
    }

    document.getElementById('inputTanggalManual').value = '<?= $filter_tanggal ?: date('Y-m-d') ?>';
    document.getElementById('modalTambahSubtitle').textContent = 'Catat kehadiran / ketidakhadiran siswa';
    
    // Set default status to 'alpha' (checked)
    const radios = document.getElementsByName('status_manual');
    for (let i = 0; i < radios.length; i++) {
        if (radios[i].value === 'alpha') radios[i].checked = true;
    }

    document.getElementById('modalTambah').classList.remove('hidden');
    document.getElementById('modalTambah').classList.add('flex');
    if (window.lucide) window.lucide.createIcons();
}

function openTambahModalFor(userId, nama, tanggal) {
    const selectSiswaHidden = document.getElementById('selectSiswa');
    const searchSiswaInput = document.getElementById('searchSiswaInput');
    const clearSiswaBtn = document.getElementById('clearSiswaBtn');
    const listSiswaDropdown = document.getElementById('listSiswaDropdown');

    if (selectSiswaHidden) selectSiswaHidden.value = userId;
    if (searchSiswaInput) searchSiswaInput.value = nama;
    if (clearSiswaBtn) clearSiswaBtn.classList.remove('hidden');
    if (listSiswaDropdown) {
        const dropdownButtons = listSiswaDropdown.querySelectorAll('button[data-id]');
        dropdownButtons.forEach(function(btn) { btn.classList.remove('hidden'); });
    }

    document.getElementById('inputTanggalManual').value  = tanggal;
    document.getElementById('modalTambahSubtitle').textContent = 'Mencatat status untuk: ' + nama;
    
    const radios = document.getElementsByName('status_manual');
    for (let i = 0; i < radios.length; i++) {
        if (radios[i].value === 'alpha') radios[i].checked = true;
    }

    document.getElementById('modalTambah').classList.remove('hidden');
    document.getElementById('modalTambah').classList.add('flex');
    if (window.lucide) window.lucide.createIcons();
}

function closeTambahModal() {
    document.getElementById('modalTambah').classList.add('hidden');
    document.getElementById('modalTambah').classList.remove('flex');
}

// Modal Bulk Alpha
var _absentUsers = <?= json_encode(array_values($absent_users)) ?>;

function openBulkAlphaModal() {
    document.getElementById('bulkJumlah').textContent = _absentUsers.length;
    var list = document.getElementById('bulkListSiswa');
    list.innerHTML = '';
    _absentUsers.forEach(function(u, i) {
        var li = document.createElement('li');
        li.innerHTML = '<span class="inline-block w-5 text-center text-orange-400 font-bold">' + (i+1) + '.</span> ' + u.nama;
        list.appendChild(li);
    });
    document.getElementById('modalBulkAlpha').classList.remove('hidden');
    document.getElementById('modalBulkAlpha').classList.add('flex');
}

function closeBulkAlphaModal() {
    document.getElementById('modalBulkAlpha').classList.add('hidden');
    document.getElementById('modalBulkAlpha').classList.remove('flex');
}

function submitBulkAlpha() {
    closeBulkAlphaModal();
    document.getElementById('formBulkAlpha').submit();
}

// Konfirmasi Hapus
var _hapusForm = null;
function confirmHapus(e, nama, tanggal) {
    e.preventDefault();
    _hapusForm = e.target;
    document.getElementById('hapusNama').textContent    = nama;
    document.getElementById('hapusTanggal').textContent = tanggal;
    document.getElementById('confirmHapusModal').classList.remove('hidden');
    return false;
}
function batalHapus() { document.getElementById('confirmHapusModal').classList.add('hidden'); _hapusForm = null; }
function lanjutHapus() { document.getElementById('confirmHapusModal').classList.add('hidden'); if (_hapusForm) _hapusForm.submit(); }

// Foto Lightbox
let _zoomScale = 1;
const ZOOM_MIN = 1, ZOOM_MAX = 4, ZOOM_STEP = 0.35;

function _applyZoom(scale, smooth) {
    if (smooth === undefined) smooth = true;
    _zoomScale = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, scale));
    var img  = document.getElementById('modalImage');
    var wrap = document.getElementById('zoomWrapper');
    img.style.transition = smooth ? 'transform 0.15s ease' : 'none';
    img.style.transform  = 'scale(' + _zoomScale + ')';
    var cur = _zoomScale >= ZOOM_MAX ? 'zoom-out' : 'zoom-in';
    img.style.cursor  = cur;
    wrap.style.cursor = cur;
    document.getElementById('zoomLabel').textContent = Math.round(_zoomScale * 100) + '%';
}
function zoomIn()  { _applyZoom(_zoomScale + ZOOM_STEP); }
function zoomOut() { _applyZoom(_zoomScale - ZOOM_STEP); }

function openPhotoModal(id) {
    var base64Data = document.getElementById(id).value;
    var imgEl      = document.getElementById('modalImage');
    var noPhotoEl  = document.getElementById('noPhotoMsg');
    var wrapper    = document.getElementById('zoomWrapper');
    var indicator  = document.getElementById('zoomIndicator');
    var modal      = document.getElementById('photoModal');
    _applyZoom(1, false);
    if (base64Data && base64Data.trim() !== '') {
        imgEl.src = base64Data;
        wrapper.classList.remove('hidden'); wrapper.classList.add('flex');
        noPhotoEl.classList.add('hidden'); noPhotoEl.classList.remove('flex');
        indicator.classList.remove('hidden'); indicator.classList.add('flex');
    } else {
        wrapper.classList.add('hidden'); wrapper.classList.remove('flex');
        indicator.classList.add('hidden'); indicator.classList.remove('flex');
        noPhotoEl.classList.remove('hidden'); noPhotoEl.classList.add('flex');
    }
    modal.style.opacity = '0';
    modal.classList.remove('hidden'); modal.classList.add('flex');
    setTimeout(function() { modal.style.transition = 'opacity 0.2s ease'; modal.style.opacity = '1'; }, 10);
}
function closePhotoModal() {
    var modal = document.getElementById('photoModal');
    modal.style.opacity = '0';
    setTimeout(function() {
        modal.classList.add('hidden'); modal.classList.remove('flex');
        modal.style.transition = '';
        _applyZoom(1, false);
    }, 200);
}

document.getElementById('zoomWrapper').addEventListener('wheel', function(e) {
    e.preventDefault();
    _applyZoom(_zoomScale + (e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP));
}, { passive: false });
document.getElementById('zoomWrapper').addEventListener('dblclick', function() {
    _applyZoom(_zoomScale > 1 ? 1 : 2);
});

var _lastPinchDist = null;
document.getElementById('zoomWrapper').addEventListener('touchstart', function(e) {
    if (e.touches.length === 2) {
        _lastPinchDist = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
    }
}, { passive: true });
document.getElementById('zoomWrapper').addEventListener('touchmove', function(e) {
    if (e.touches.length === 2 && _lastPinchDist !== null) {
        e.preventDefault();
        var dist = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
        _applyZoom(_zoomScale * (dist / _lastPinchDist), false);
        _lastPinchDist = dist;
    }
}, { passive: false });
document.getElementById('zoomWrapper').addEventListener('touchend', function() { _lastPinchDist = null; });

// Logout SweetAlert
document.querySelectorAll('.logout-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Konfirmasi Keluar',
            text: 'Apakah Anda yakin ingin keluar dari sistem?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Keluar!',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
