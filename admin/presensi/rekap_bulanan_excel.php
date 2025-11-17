<?php
ob_start();
session_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}

$judul = "Rekap Presensi Bulanan";
include_once('../../config.php');
require_once('../../assets/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$tahun_bulan = $_POST["filter_tahun"] . '-' . $_POST["filter_bulan"];
$result = mysqli_query($connection, "SELECT presensi.*, pegawai.nama, pegawai.nik, pegawai.lokasi_presensi 
    FROM presensi JOIN pegawai ON presensi.id_pegawai = pegawai.id 
    WHERE DATE_FORMAT(tanggal_masuk, '%Y-%m') = '$tahun_bulan'
    ORDER BY tanggal_masuk DESC");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Rekap Presensi Bulanan');
$sheet->setCellValue('A2', 'Bulan  :');
$sheet->setCellValue('A3', 'Tahun  :');
$sheet->setCellValue('C2', $_POST['filter_bulan']);
$sheet->setCellValue('C3', $_POST['filter_tahun']);
$sheet->setCellValue('A5', 'No.');
$sheet->setCellValue('B5', 'Nama');
$sheet->setCellValue('C5', 'NIK');
$sheet->setCellValue('D5', 'Lokasi Presensi');
$sheet->setCellValue('E5', 'Tgl Masuk');
$sheet->setCellValue('F5', 'Jam Masuk');
$sheet->setCellValue('G5', 'Tgl Keluar');
$sheet->setCellValue('H5', 'Jam Keluar');
$sheet->setCellValue('I5', 'Total Jam Kerja');
$sheet->setCellValue('J5', 'Total Jam Terlambat');

$sheet->mergeCells('A1:F1');
$sheet->mergeCells('A2:B2');
$sheet->mergeCells('A3:B3');

$no = 1;
$row = 6;

while ($data = mysqli_fetch_array($result)) {

    // menghitung total jam kerja
    $jam_tanggal_masuk = date('Y-m-d H:i:s', strtotime($data['tanggal_masuk'] . '' . $data['jam_masuk']));
    $jam_tanggal_keluar = date('Y-m-d H:i:s', strtotime($data['tanggal_keluar'] . '' . $data['jam_keluar']));

    $timestamp_masuk = strtotime($jam_tanggal_masuk);
    $timestamp_keluar = strtotime($jam_tanggal_keluar);

    $selisih = $timestamp_keluar - $timestamp_masuk;

    $total_jam_kerja = floor($selisih / 3600);
    $selisih -= $total_jam_kerja * 3600;
    $selisih_menit_kerja = floor($selisih / 60);

    // menghitung total terlambat

    $lokasi_presensi = $data['lokasi_presensi'];
    $lokasi = mysqli_query($connection, "SELECT * FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_presensi'");

    while ($lokasi_result = mysqli_fetch_array($lokasi)) :
        $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk']));
    endwhile;

    $jam_masuk = date('H:i:s', strtotime($data['jam_masuk']));
    $timestamp_jam_masuk_real = strtotime($jam_masuk);
    $timestamp_jam_masuk_kantor = strtotime($jam_masuk_kantor);

    $terlambat = $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor;
    $total_jam_terlambat = floor($terlambat / 3600);
    $terlambat -= $total_jam_terlambat * 3600;
    $selisih_menit_terlambat = floor($terlambat / 60);

    $sheet->setCellValue('A' . $row, $no);
    $sheet->setCellValue('B' . $row, $data['nama']);
    $sheet->setCellValue('C' . $row, $data['nik']);
    $sheet->setCellValue('D' . $row, $data['lokasi_presensi']);
    $sheet->setCellValue('E' . $row, $data['tanggal_masuk']);
    $sheet->setCellValue('F' . $row, $data['jam_masuk']);
    $sheet->setCellValue('G' . $row, $data['tanggal_keluar']);
    $sheet->setCellValue('H' . $row, $data['jam_keluar']);
    $sheet->setCellValue('I' . $row, $total_jam_kerja . ' jam ' . $selisih_menit_kerja . ' menit');
    $sheet->setCellValue('J' . $row, $total_jam_terlambat . ' jam ' . $selisih_menit_terlambat . ' menit');

    $no++;
    $row++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Laporan Presensi Bulanan.xlsx"');
header('Cache-Control: max-age=0');

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
