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


// Get current day in Indonesian format
$hari_ini = date('N'); // 1 = Monday, 2 = Tuesday, etc.
$nama_hari = '';
switch($hari_ini) {
    case 1: $nama_hari = 'senin'; break;
    case 2: $nama_hari = 'selasa'; break;
    case 3: $nama_hari = 'rabu'; break;
    case 4: $nama_hari = 'kamis'; break;
    case 5: $nama_hari = 'jumat'; break;
    case 6: $nama_hari = 'sabtu'; break;
    case 7: $nama_hari = 'minggu'; break;
}

// Get office hours from jam_kerja table based on current day
$jam_masuk_kantor = '';
$jam_pulang_kantor = '';

if ($nama_hari != 'minggu') { // Only get office hours if not Sunday
    $jamKerjaQuery = "SELECT jam_masuk_$nama_hari as jam_masuk_kantor, jam_pulang_$nama_hari as jam_pulang_kantor FROM jam_kerja LIMIT 1";
    $jamKerjaResult = mysqli_query($connection, $jamKerjaQuery);
    
    if ($jamKerjaResult && mysqli_num_rows($jamKerjaResult) > 0) {
        $jamKerjaData = mysqli_fetch_assoc($jamKerjaResult);
        $jam_masuk_kantor = $jamKerjaData['jam_masuk_kantor'];
        $jam_pulang_kantor = $jamKerjaData['jam_pulang_kantor'];
    }
}

// Check for data duplication
$checkQuery = "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_masuk = '$tanggal_masuk'";
$checkResult = mysqli_query($connection, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
    // Data duplication found, handle accordingly (e.g., display an error message)
    $_SESSION['gagal'] = "Presensi masuk gagal (data duplikasi)";
} else {
    // Insert the data into the database including office hours
    $insertQuery = "INSERT INTO presensi(id_pegawai, tanggal_masuk, jam_masuk, foto_masuk, jam_masuk_kantor, jam_pulang_kantor) 
                    VALUES ('$id_pegawai', NOW(), NOW(), '$file', '$jam_masuk_kantor', '$jam_pulang_kantor')";
    $result = mysqli_query($connection, $insertQuery);

    if ($result) {
        $_SESSION['berhasil'] = "Presensi masuk berhasil";
    } else {
        $_SESSION['gagal'] = "Presensi masuk gagal";
    }
}
?>