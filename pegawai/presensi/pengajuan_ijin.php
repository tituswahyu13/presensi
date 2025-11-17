<?php
session_start();
include_once("../../config.php");

if (!isset($_SESSION["login"])) {
  header("Location: https://internal.pdamkotamagelang.com/absensi/auth/login.php?pesan=belum_login");
  exit();
} elseif (!in_array($_SESSION["role"], ["pegawai", "sumber", "tidar", "kalimas", "sri_ponganten", "satpam"])) {
  header("Location: https://internal.pdamkotamagelang.com/absensi/auth/login.php?pesan=tolak_akses");
  exit();
}

$role_pages = [
  'pegawai' => 'home.php',
  'sumber' => 'sumber.php',
  'tidar' => 'tidar.php',
  'kalimas' => 'kalimas.php',
  'sri_ponganten' => 'sri_ponganten.php',
  'satpam' => 'satpam.php'
];
$from_page = isset($role_pages[$_SESSION['role']]) ? $role_pages[$_SESSION['role']] : 'home.php';

if (isset($_POST['tombol_ijin'])) {
  $id_pegawai = $_SESSION['id'];
//   $latitude_pegawai = $_POST['latitude_pegawai'];
//   $longitude_pegawai = $_POST['longitude_pegawai'];
//   $latitude_kantor = $_POST['latitude_kantor'];
//   $longitude_kantor = $_POST['longitude_kantor'];
//   $radius = $_POST['radius'];
//   $zona_waktu = $_POST['zona_waktu'];
  $tanggal_pengajuan = $_POST['tanggal_pengajuan'];
  $jam_pengajuan = $_POST['jam_pengajuan'];
  $jenis_pengajuan = $_POST['jenis_pengajuan']; // harusnya 'sakit'
  $keterangan_izin = isset($_POST['keterangan_izin']) ? $_POST['keterangan_izin'] : '';

  // Cek apakah sudah ada data dengan id_pegawai dan tanggal_absen yang sama
  $cek_query = "SELECT COUNT(*) as jumlah FROM absensi WHERE id_pegawai = '$id_pegawai' AND tanggal_absen = '$tanggal_pengajuan'";
  $cek_result = mysqli_query($connection, $cek_query);
  $cek_data = mysqli_fetch_assoc($cek_result);

  if ($cek_data['jumlah'] > 0) {
    echo "<script>alert('Anda sudah melakukan pengajuan izin hari ini!'); window.location.href='../home/{$from_page}';</script>";
    exit();
  }

  // Query simpan ke tabel pengajuan_sakit (atau sesuaikan nama tabelnya)
  $query = "INSERT INTO absensi (id_pegawai, tanggal_absen, jam_absen, keterangan, info) 
            VALUES ('$id_pegawai', '$tanggal_pengajuan', '$jam_pengajuan', '$jenis_pengajuan', '$keterangan_izin')";

  if (mysqli_query($connection, $query)) {
    echo "<script>alert('Pengajuan ijin berhasil dikirim!'); window.location.href='../home/{$from_page}';</script>";
  } else {
    $error = mysqli_error($connection);
    echo "<script>alert('Pengajuan ijin gagal! Error: $error'); window.location.href='../home/{$from_page}';</script>";
  }
} else {
  header("Location: ../home/{$from_page}");
  exit();
}
?>
