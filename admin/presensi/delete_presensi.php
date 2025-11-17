<?php
session_start();
include_once('../../config.php');

// Pastikan hanya Admin yang bisa mengakses
if (!isset($_SESSION["login"]) || $_SESSION["role"] != "admin") {
    $_SESSION['gagal'] = "Akses ditolak!";
    header("Location: ../../auth/login.php?pesan=tolak_akses");
    exit();
}

// Ambil parameter untuk redirect dan ID presensi
$presensi_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tanggal_dari = urlencode($_GET['tanggal_dari'] ?? date('Y-m-d'));
$tanggal_sampai = urlencode($_GET['tanggal_sampai'] ?? date('Y-m-d'));
$nama_filter = urlencode($_GET['nama'] ?? '');
$status_filter = urlencode($_GET['status'] ?? 'all');

$redirect_url = "rekap_harian.php?tanggal_dari={$tanggal_dari}&tanggal_sampai={$tanggal_sampai}&nama={$nama_filter}&status={$status_filter}";

if ($presensi_id === 0) {
    $_SESSION['gagal'] = "ID Presensi tidak valid.";
    header("Location: " . $redirect_url);
    exit();
}

// 1. Ambil data dari tabel presensi
$stmt_select = $connection->prepare("SELECT * FROM presensi WHERE id = ?");
$stmt_select->bind_param("i", $presensi_id);
$stmt_select->execute();
$result_select = $stmt_select->get_result();
$data_to_archive = $result_select->fetch_assoc();
$stmt_select->close();

if ($data_to_archive) {
    // 2. Pindahkan data ke tabel presensi_deleted
    // CATATAN: Pastikan urutan kolom dan jumlah tanda tanya (?) sesuai dengan kolom di tabel 'presensi_deleted'
    $query_insert = "
        INSERT INTO presensi_deleted (
            id, id_pegawai, tanggal_masuk, jam_masuk, foto_masuk, latitude_masuk, longitude_masuk, 
            tanggal_keluar, jam_keluar, foto_keluar, latitude_keluar, longitude_keluar,
            keterangan, jam_absen, shift, status_presensi, deleted_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt_insert = $connection->prepare($query_insert);
    
    // Asumsi: Struktur presensi_deleted memiliki kolom ID, dan kolom deleted_by (INT)
    // Jika kolom ID di presensi_deleted dibuat AUTO_INCREMENT, hapus tanda tanya pertama dan $data_to_archive['id']
    $deleted_by_user = $_SESSION["id"] ?? 0; // Ambil ID Admin yang menghapus

    // Jika Anda menggunakan struktur tabel DENGAN kolom ID lama (seperti di query buat tabel):
    $stmt_insert->bind_param("iissssssssssssssi", 
        $data_to_archive['id'], // Mempertahankan ID lama di tabel arsip
        $data_to_archive['id_pegawai'], 
        $data_to_archive['tanggal_masuk'], 
        $data_to_archive['jam_masuk'], 
        $data_to_archive['foto_masuk'], 
        $data_to_archive['latitude_masuk'], 
        $data_to_archive['longitude_masuk'], 
        $data_to_archive['tanggal_keluar'], 
        $data_to_archive['jam_keluar'], 
        $data_to_archive['foto_keluar'], 
        $data_to_archive['latitude_keluar'], 
        $data_to_archive['longitude_keluar'],
        $data_to_archive['keterangan'],
        $data_to_archive['jam_absen'],
        $data_to_archive['shift'],
        $data_to_archive['status_presensi'],
        $deleted_by_user
    );
    
    if ($stmt_insert->execute()) {
        // 3. Hapus data dari tabel presensi (hanya jika pengarsipan berhasil)
        $stmt_delete = $connection->prepare("DELETE FROM presensi WHERE id = ?");
        $stmt_delete->bind_param("i", $presensi_id);
        
        if ($stmt_delete->execute()) {
             $_SESSION['sukses'] = "Data presensi berhasil dihapus dan diarsipkan.";
        } else {
             $_SESSION['gagal'] = "Data berhasil diarsipkan, tapi gagal dihapus dari tabel utama: " . $connection->error;
        }
        $stmt_delete->close();
    } else {
        // Jika INSERT ke arsip gagal, tampilkan error database
        $_SESSION['gagal'] = "Gagal mengarsipkan data. Periksa struktur tabel presensi_deleted. Error: " . $connection->error;
    }
    $stmt_insert->close();
} else {
    $_SESSION['gagal'] = "Data presensi tidak ditemukan.";
}

header("Location: " . $redirect_url);
exit();
?>