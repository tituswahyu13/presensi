<?php

ob_start();
session_start();

if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "sumber") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}
include_once("../../config.php");

$file_foto = $_POST['photo'];
$id_pegawai = $_POST['id'];
$tanggal_masuk = $_POST['tanggal_masuk'];
$jam_masuk = $_POST['jam_masuk'];

$foto = $file_foto;
$foto = str_replace('data:image/jpeg;base64', '', $foto);
$foto = str_replace(' ', '+', $foto);
$data = base64_decode($foto);

$jam_masuk_formatted = str_replace(':', '_', $jam_masuk);

$nama_file = 'foto/' . 'masuk_' . $id_pegawai . '_' . $tanggal_masuk . '_' . $jam_masuk_formatted . '.png';
$file = 'masuk_' . $id_pegawai . '_' . $tanggal_masuk . '_' . $jam_masuk_formatted . '.png';
file_put_contents($nama_file, $data);

// $result = mysqli_query($connection, "INSERT INTO presensi(id_pegawai, tanggal_masuk, jam_masuk, foto_masuk) 
// VALUES ('$id_pegawai', '$tanggal_masuk', '$jam_masuk', '$file')");

// $result = mysqli_query($connection, "INSERT INTO presensi(id_pegawai, tanggal_masuk, jam_masuk, foto_masuk) 
// VALUES ('$id_pegawai', NOW(), NOW(), '$file')");


// if($result) {
//     $_SESSION['berhasil'] = "Presensi masuk berhasil";
// } else {
//     $_SESSION['gagal'] = "Presensi masuk gagal";
// }

// Ambil jam masuk dan pulang kantor dari tabel shift
$lokasi_presensi = $_SESSION['lokasi_presensi'];
$stmt_shift = mysqli_prepare($connection, "SELECT masuk_b, pulang_b FROM shift WHERE id = 1");
mysqli_stmt_bind_param($stmt_shift, "s", $_SESSION['lokasi_presensi']);
mysqli_stmt_execute($stmt_shift);
$result_shift = mysqli_stmt_get_result($stmt_shift);
$shift_data = mysqli_fetch_assoc($result_shift);

if (!$shift_data || empty($shift_data['masuk_b']) || empty($shift_data['pulang_b'])) {
    $_SESSION['gagal'] = "Jadwal shift tidak lengkap atau tidak ditemukan untuk lokasi Anda.";
    header("Location: ../home/sumber.php"); // Redirect back to the home page
    exit();
}

// Check for data duplication
$stmt_check = mysqli_prepare($connection, "SELECT id FROM presensi WHERE id_pegawai = ? AND tanggal_masuk = ?");
mysqli_stmt_bind_param($stmt_check, "is", $id_pegawai, $tanggal_masuk);
mysqli_stmt_execute($stmt_check);
$checkResult = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($checkResult) > 0) {
    // Data duplication found, handle accordingly (e.g., display an error message)
    $_SESSION['gagal'] = "Presensi masuk gagal (data duplikasi)";
} else {
    // Insert the data into the database
    $stmt_insert = mysqli_prepare($connection, "INSERT INTO presensi(id_pegawai, tanggal_masuk, jam_masuk, foto_masuk, jam_masuk_kantor, jam_pulang_kantor, shift) VALUES (?, NOW(), NOW(), ?, ?, ?, 'B')");
    mysqli_stmt_bind_param($stmt_insert, "isss", $id_pegawai, $file, $shift_data['masuk_b'], $shift_data['pulang_b']);
    $result = mysqli_stmt_execute($stmt_insert);

    if ($result) {
        $_SESSION['berhasil'] = "Presensi masuk berhasil";
    } else {
        $_SESSION['gagal'] = "Presensi masuk gagal: " . mysqli_stmt_error($stmt_insert);
    }
}
