<?php
// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Update status absensi yang sudah ada
    if (isset($_POST['action']) && $_POST['action'] === 'update_absensi') {
        $absen_id   = intval($_POST['absen_id']);
        $new_status = $_POST['new_status'];
        $new_pesan  = trim($_POST['new_pesan'] ?? '');
        $allowed    = ['hadir','izin','sakit','alpha'];
        if (in_array($new_status, $allowed)) {
            $stmt = $conn->prepare("UPDATE absensi SET status=?, pesan=? WHERE id=?");
            $stmt->bind_param("ssi", $new_status, $new_pesan, $absen_id);
            if ($stmt->execute()) {
                $msg = 'Status Presensi berhasil diubah!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal mengubah status.';
                $msg_type = 'error';
            }
            $stmt->close();
        }
    }

    // 2. Tambah absensi manual untuk siswa yang tidak hadir
    if (isset($_POST['action']) && $_POST['action'] === 'tambah_absensi') {
        $user_id    = intval($_POST['user_id']);
        $tanggal    = $conn->real_escape_string($_POST['tanggal_manual']);
        $new_status = $_POST['status_manual'];
        $new_pesan  = trim($_POST['pesan_manual'] ?? '');
        $allowed    = ['hadir','izin','sakit','alpha'];

        if ($user_id > 0 && $tanggal && in_array($new_status, $allowed)) {
            // Cek apakah sudah ada data untuk user+tanggal tersebut
            $cek = $conn->prepare("SELECT id FROM absensi WHERE user_id=? AND tanggal=?");
            $cek->bind_param("is", $user_id, $tanggal);
            $cek->execute();
            $cek->store_result();

            if ($cek->num_rows > 0) {
                $msg      = 'Siswa ini sudah memiliki data Presensi pada tanggal tersebut.';
                $msg_type = 'warning';
            } else {
                $waktu = date('H:i:s');
                $ins   = $conn->prepare("INSERT INTO absensi (user_id, tanggal, waktu, status, pesan) VALUES (?, ?, ?, ?, ?)");
                $ins->bind_param("issss", $user_id, $tanggal, $waktu, $new_status, $new_pesan);
                if ($ins->execute()) {
                    $msg = 'Data Presensi berhasil ditambahkan!';
                    $msg_type = 'success';
                } else {
                    $msg = 'Gagal menambahkan data.';
                    $msg_type = 'error';
                }
                $ins->close();
            }
            $cek->close();
        } else {
            $msg = 'Data tidak lengkap. Harap isi semua field.';
            $msg_type = 'error';
        }
    }

    // 3. Simpan semua siswa yang belum absen sebagai Alpha sekaligus (Bulk Alpha)
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_alpha') {
        $tanggal    = $conn->real_escape_string($_POST['tanggal_bulk'] ?? '');
        $ids_raw    = $_POST['absent_user_ids'] ?? '';
        $ids        = array_filter(array_map('intval', explode(',', $ids_raw)));
        $waktu      = date('H:i:s');
        $berhasil   = 0;

        if ($tanggal && count($ids) > 0) {
            foreach ($ids as $uid) {
                // Hanya insert jika belum ada
                $cek = $conn->prepare("SELECT id FROM absensi WHERE user_id=? AND tanggal=?");
                $cek->bind_param("is", $uid, $tanggal);
                $cek->execute();
                $cek->store_result();
                if ($cek->num_rows === 0) {
                    $statusAlpha = 'alpha';
                    $pesanAlpha  = 'Tidak hadir - dicatat otomatis oleh sistem';
                    $ins = $conn->prepare("INSERT INTO absensi (user_id, tanggal, waktu, status, pesan) VALUES (?, ?, ?, ?, ?)");
                    $ins->bind_param("issss", $uid, $tanggal, $waktu, $statusAlpha, $pesanAlpha);
                    if ($ins->execute()) $berhasil++;
                    $ins->close();
                }
                $cek->close();
            }
            $msg      = "$berhasil siswa berhasil dicatat sebagai Alpha!";
            $msg_type = 'success';
        } else {
            $msg = 'Tidak ada siswa yang perlu dicatat atau tanggal tidak valid.';
            $msg_type = 'error';
        }
    }

    // 4. Hapus absensi
    if (isset($_POST['action']) && $_POST['action'] === 'delete_absensi') {
        $absen_id = intval($_POST['absen_id']);
        if ($absen_id > 0) {
            $stmt = $conn->prepare("DELETE FROM absensi WHERE id=?");
            $stmt->bind_param("i", $absen_id);
            if ($stmt->execute()) {
                $msg = 'Data absensi berhasil dihapus!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal menghapus data.';
                $msg_type = 'error';
            }
            $stmt->close();
        }
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: ../lng/lgn.php');
        exit;
    }
}
