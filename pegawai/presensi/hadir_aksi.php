<?php

ob_start();
session_start();

if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "pegawai") {
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

// Check for data duplication
$checkQuery = "SELECT * FROM event WHERE id_pegawai = '$id_pegawai' AND tanggal = '$tanggal_masuk'";
$checkResult = mysqli_query($connection, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
    // Data duplication found, handle accordingly (e.g., display an error message)
    $_SESSION['gagal'] = "Anda telah mengisi daftar hadir";
} else {
    // Insert the data into the database
    $insertQuery = "INSERT INTO event(id_pegawai, tanggal, jam, foto) 
                    VALUES ('$id_pegawai', NOW(), NOW(), '$file')";
    $result = mysqli_query($connection, $insertQuery);

    if ($result) {
        $_SESSION['berhasil'] = "Isi daftar hadir berhasil";
    } else {
        $_SESSION['gagal'] = "Isi daftar hadir gagal";
    }
}
?>