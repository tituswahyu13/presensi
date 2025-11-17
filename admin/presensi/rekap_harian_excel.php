<?php
ob_start();
session_start();
if (!isset($_SESSION["login"])) {
  header("Location: ../../auth/login.php?pesan=belum_login");
  exit();
} elseif ($_SESSION["role"] != "admin") {
  header("Location: ../../auth/login.php?pesan=tolak_akses");
  exit();
}

$judul = "Rekap Presensi Harian";
include_once('../../config.php');
require_once('../../assets/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Get filter parameters from the URL
$tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : date('Y-m-d');
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : date('Y-m-d');
$nama_filter = isset($_GET['nama']) ? "%" . $_GET['nama'] . "%" : null;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build the base query
$query = "SELECT
    pegawai.id as pegawai_id,
    pegawai.nama,
    pegawai.lokasi_presensi,
    pegawai.bagian,
    pegawai.jabatan,
    presensi.*,
    users.role 
FROM
    pegawai
LEFT JOIN presensi ON pegawai.id = presensi.id_pegawai 
    AND presensi.tanggal_masuk BETWEEN ? AND ?
LEFT JOIN users ON users.id_pegawai = pegawai.id 
WHERE
    users.role != 'admin'
    AND users.status = 'aktif'";

// Add name filter if it exists
if ($nama_filter) {
  $query .= " AND pegawai.nama LIKE ?";
}

// Add sorting
$query .= " ORDER BY
    CASE pegawai.jabatan
        WHEN 'Direktur Utama' THEN 1
        WHEN 'Direktur' THEN 2
        WHEN 'Ketua' THEN 3
        WHEN 'Manajer' THEN 4
        WHEN 'Pengawas' THEN 5
        WHEN 'Asisten Manajer' THEN 6
        WHEN 'Kepala Unit' THEN 7
        WHEN 'Komandan Regu' THEN 8
        WHEN 'Staf' THEN 9
        ELSE 10
    END ASC,
    pegawai.bagian ASC, 
    pegawai.id ASC, 
    presensi.tanggal_masuk ASC, 
    presensi.jam_masuk ASC;";

$stmt = $connection->prepare($query);
if ($nama_filter) {
  $stmt->bind_param("sss", $tanggal_dari, $tanggal_sampai, $nama_filter);
} else {
  $stmt->bind_param("ss", $tanggal_dari, $tanggal_sampai);
}

$stmt->execute();
$result = $stmt->get_result();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'REKAP PRESENSI HARIAN');
$sheet->setCellValue('A2', 'Periode: ' . date('d F Y', strtotime($tanggal_dari)) . ' s/d ' . date('d F Y', strtotime($tanggal_sampai)));
$sheet->setCellValue('A3', 'Status Filter: ' . ucfirst(str_replace('-', ' ', $status_filter)));
$sheet->mergeCells('A1:O1');
$sheet->mergeCells('A2:O2');
$sheet->mergeCells('A3:O3');
$sheet->getStyle('A1:A3')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$headerRow = 5;
$sheet->setCellValue('A' . $headerRow, 'No.');
$sheet->setCellValue('B' . $headerRow, 'Nama');
$sheet->setCellValue('C' . $headerRow, 'Tanggal Masuk/Izin');
$sheet->setCellValue('D' . $headerRow, 'Jam Masuk/Izin');
$sheet->setCellValue('E' . $headerRow, 'Jam Masuk Kantor');
$sheet->setCellValue('F' . $headerRow, 'Terlambat');
$sheet->setCellValue('G' . $headerRow, 'Tanggal Pulang');
$sheet->setCellValue('H' . $headerRow, 'Jam Pulang');
$sheet->setCellValue('I' . $headerRow, 'Jam Pulang Kantor');
$sheet->setCellValue('J' . $headerRow, 'Pulang Awal');
$sheet->setCellValue('K' . $headerRow, 'Lokasi Kerja');
$sheet->setCellValue('L' . $headerRow, 'Ket. Izin');
$sheet->setCellValue('M' . $headerRow, 'Jam Izin');
$sheet->setCellValue('N' . $headerRow, 'Bagian');
$sheet->setCellValue('O' . $headerRow, 'Jabatan');

$headerStyle = [
  'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
  'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
  'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
  'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];
$sheet->getStyle('A' . $headerRow . ':O' . $headerRow)->applyFromArray($headerStyle);

$columnWidths = [
  'A' => 5,
  'B' => 30,
  'C' => 20,
  'D' => 15,
  'E' => 20,
  'F' => 15,
  'G' => 20,
  'H' => 15,
  'I' => 20,
  'J' => 15,
  'K' => 20,
  'L' => 25,
  'M' => 15,
  'N' => 20,
  'O' => 20,
];
foreach ($columnWidths as $col => $width) {
  $sheet->getColumnDimension($col)->setWidth($width);
}

$row = $headerRow + 1;
$no = 1;

while ($rekap = $result->fetch_assoc()) {
  $status_row = 'hadir';
  $jam_masuk_kantor = '';
  $jam_pulang_kantor = '';
  $terlambat_seconds = 0;
  $pulang_awal_seconds = 0;

  if (empty($rekap['tanggal_masuk']) || empty($rekap['jam_masuk'])) {
    $status_row = 'belum-presensi';
  } elseif (!empty($rekap['keterangan'])) {
    $status_row = 'izin';
  } else {
    if ($rekap['role'] == 'pegawai') {
      $jam_query = "SELECT * FROM jam_kerja WHERE id = 1";
      $jam_result_db = $connection->query($jam_query)->fetch_assoc();
      $shift_day = date('N', strtotime($rekap['tanggal_masuk']));

      switch ($shift_day) {
        case 1:
          $jam_masuk_kantor = date('H:i:s', strtotime($jam_result_db['jam_masuk_senin']));
          $jam_pulang_kantor = date('H:i:s', strtotime($jam_result_db['jam_pulang_senin']));
          break;
        case 2:
          $jam_masuk_kantor = date('H:i:s', strtotime($jam_result_db['jam_masuk_selasa']));
          $jam_pulang_kantor = date('H:i:s', strtotime($jam_result_db['jam_pulang_selasa']));
          break;
        case 3:
          $jam_masuk_kantor = date('H:i:s', strtotime($jam_result_db['jam_masuk_rabu']));
          $jam_pulang_kantor = date('H:i:s', strtotime($jam_result_db['jam_pulang_rabu']));
          break;
        case 4:
          $jam_masuk_kantor = date('H:i:s', strtotime($jam_result_db['jam_masuk_kamis']));
          $jam_pulang_kantor = date('H:i:s', strtotime($jam_result_db['jam_pulang_kamis']));
          break;
        case 5:
          $jam_masuk_kantor = date('H:i:s', strtotime($jam_result_db['jam_masuk_jumat']));
          $jam_pulang_kantor = date('H:i:s', strtotime($jam_result_db['jam_pulang_jumat']));
          break;
        case 6:
          $jam_masuk_kantor = date('H:i:s', strtotime($jam_result_db['jam_masuk_sabtu']));
          $jam_pulang_kantor = date('H:i:s', strtotime($jam_result_db['jam_pulang_sabtu']));
          break;
        case 7:
          $jam_masuk_kantor = date('H:i:s', strtotime($jam_result_db['jam_masuk_minggu']));
          $jam_pulang_kantor = date('H:i:s', strtotime($jam_result_db['jam_pulang_minggu']));
          break;
      }
    } elseif ($rekap['role'] == 'sumber' || $rekap['role'] == 'tidar') {
      $shift_query = "SELECT * FROM shift WHERE id = 1";
      $shift_result_db = $connection->query($shift_query)->fetch_assoc();
      $shift_code = strtolower($rekap['shift'] ?? 'a');
      $jam_masuk_kantor = date('H:i:s', strtotime($shift_result_db['masuk_' . $shift_code]));
      $jam_pulang_kantor = date('H:i:s', strtotime($shift_result_db['pulang_' . $shift_code]));
    }

    if (!empty($rekap['jam_masuk']) && !empty($jam_masuk_kantor)) {
      $terlambat_seconds = strtotime($rekap['jam_masuk']) - strtotime($jam_masuk_kantor);
    }
    if (!empty($rekap['jam_keluar']) && !empty($jam_pulang_kantor)) {
      $pulang_awal_seconds = strtotime($jam_pulang_kantor) - strtotime($rekap['jam_keluar']);
    }

    if ($terlambat_seconds > 0) {
      $status_row = 'terlambat';
    }
    if ($pulang_awal_seconds > 0) {
      $status_row = 'pulang-awal';
    }
    if ($terlambat_seconds > 0 && $pulang_awal_seconds > 0) {
      $status_row = 'terlambat-pulang-awal';
    }
  }

  if ($status_filter == 'all' || $status_row == $status_filter || ($status_filter == 'hadir' && $status_row == 'hadir') || ($status_filter == 'terlambat' && $status_row == 'terlambat') || ($status_filter == 'pulang-awal' && $status_row == 'pulang-awal')) {
    $tgl_masuk = !empty($rekap['tanggal_masuk']) ? date('d F Y', strtotime($rekap['tanggal_masuk'])) : 'Belum presensi';
    $jam_masuk = !empty($rekap['jam_masuk']) ? $rekap['jam_masuk'] : '-';
    $tgl_keluar = !empty($rekap['tanggal_keluar']) ? date('d F Y', strtotime($rekap['tanggal_keluar'])) : '-';
    $jam_keluar = !empty($rekap['jam_keluar']) ? $rekap['jam_keluar'] : '-';
    $keterangan = !empty($rekap['keterangan']) ? $rekap['keterangan'] : '-';
    $jam_absen = !empty($rekap['jam_absen']) ? $rekap['jam_absen'] : '-';

    $terlambat_formatted = '-';
    if ($terlambat_seconds > 0) {
      $jam = floor($terlambat_seconds / 3600);
      $menit = floor(($terlambat_seconds % 3600) / 60);
      $detik = $terlambat_seconds % 60;
      $terlambat_formatted = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);
    }

    $pulang_awal_formatted = '-';
    if ($pulang_awal_seconds > 0) {
      $jam = floor($pulang_awal_seconds / 3600);
      $menit = floor(($pulang_awal_seconds % 3600) / 60);
      $detik = $pulang_awal_seconds % 60;
      $pulang_awal_formatted = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);
    }

    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, $rekap['nama']);
    $sheet->setCellValue('C' . $row, $tgl_masuk);
    $sheet->setCellValue('D' . $row, $jam_masuk);
    $sheet->setCellValue('E' . $row, $jam_masuk_kantor);
    $sheet->setCellValue('F' . $row, $terlambat_formatted);
    $sheet->setCellValue('G' . $row, $tgl_keluar);
    $sheet->setCellValue('H' . $row, $jam_keluar);
    $sheet->setCellValue('I' . $row, $jam_pulang_kantor);
    $sheet->setCellValue('J' . $row, $pulang_awal_formatted);
    $sheet->setCellValue('K' . $row, $rekap['lokasi_presensi']);
    $sheet->setCellValue('L' . $row, $keterangan);
    $sheet->setCellValue('M' . $row, $jam_absen);
    $sheet->setCellValue('N' . $row, $rekap['bagian']);
    $sheet->setCellValue('O' . $row, $rekap['jabatan']);

    $dataStyle = [
      'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
      'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
    ];
    $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray($dataStyle);

    $centerColumns = ['A', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'M'];
    foreach ($centerColumns as $col) {
      $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    if ($terlambat_seconds > 0) {
      $sheet->getStyle('F' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
    }
    if ($pulang_awal_seconds > 0) {
      $sheet->getStyle('J' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
    }
    if ($tgl_masuk == 'Belum presensi') {
      $sheet->getStyle('C' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
    }

    $row++;
  }
}

$lastRow = $row - 1;
if ($lastRow > $headerRow) {
  $sheet->setAutoFilter('A' . $headerRow . ':O' . $lastRow);
}
$sheet->freezePane('A' . ($headerRow + 1));

$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);
$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, $headerRow);

$filename = 'Rekap_Presensi_Harian_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
