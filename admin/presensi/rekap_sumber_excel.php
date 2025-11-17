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
$tanggal_dari = isset($_GET['tanggal_dari']) ? mysqli_real_escape_string($connection, $_GET['tanggal_dari']) : date('Y-m-d');
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? mysqli_real_escape_string($connection, $_GET['tanggal_sampai']) : date('Y-m-d');
$nama_filter = isset($_GET['nama']) ? '%' . mysqli_real_escape_string($connection, $_GET['nama']) . '%' : null;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build the query exactly like in rekap_harian_sumber.php
$nama_condition = $nama_filter ? "AND p.nama LIKE ?" : "";

$query = "
    SELECT
        p.id AS pegawai_id,
        p.nama,
        p.lokasi_presensi AS lokasi_kerja,
        u.role,
        js.tanggal AS jadwal_tanggal,
        js.shift AS shift_sumber,
        pr.tanggal_masuk,
        pr.jam_masuk,
        pr.tanggal_keluar,
        pr.jam_keluar,
        pr.shift AS shift_presensi,
        pr.foto_masuk,
        pr.foto_keluar,
        pr.keterangan,
        pr.jam_absen,
        pr.id AS presensi_id
    FROM
        jadwal_sumber AS js
    LEFT JOIN pegawai AS p
        ON js.id_pegawai = p.id
    LEFT JOIN users AS u 
        ON p.id = u.id_pegawai 
    LEFT JOIN presensi AS pr 
        ON p.id = pr.id_pegawai 
        AND js.tanggal = pr.tanggal_masuk
    WHERE
        p.lokasi_presensi NOT IN ('kantor PDAM', 'satpam')
        AND u.status = 'aktif'
        AND js.tanggal BETWEEN ? AND ?
        $nama_condition
    ORDER BY
        js.tanggal ASC, p.nama ASC";

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

// Set document properties
$spreadsheet->getProperties()
    ->setCreator("Sistem Presensi PDAM")
    ->setLastModifiedBy("Sistem Presensi PDAM")
    ->setTitle("Rekap Presensi Sumber")
    ->setSubject("Rekap Presensi")
    ->setDescription("Rekap Presensi Harian Sumber");

// Set title and date range
$sheet->setCellValue('A1', 'REKAP PRESENSI SUMBER');
$sheet->setCellValue('A2', 'Periode: ' . date('d F Y', strtotime($tanggal_dari)) . ' s/d ' . date('d F Y', strtotime($tanggal_sampai)));
$sheet->setCellValue('A3', 'Status Filter: ' . ucfirst(str_replace('-', ' ', $status_filter)));
$sheet->mergeCells('A1:O1');
$sheet->mergeCells('A2:O2');
$sheet->mergeCells('A3:O3');
$sheet->getStyle('A1:A3')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set headers
$headerRow = 5;
$sheet->setCellValue('A' . $headerRow, 'No.');
$sheet->setCellValue('B' . $headerRow, 'Nama');
$sheet->setCellValue('C' . $headerRow, 'Jadwal Jaga');
$sheet->setCellValue('D' . $headerRow, 'Tanggal Masuk/Izin');
$sheet->setCellValue('E' . $headerRow, 'Jam Masuk/Izin');
$sheet->setCellValue('F' . $headerRow, 'Jam Masuk Kantor');
$sheet->setCellValue('G' . $headerRow, 'Terlambat');
$sheet->setCellValue('H' . $headerRow, 'Tanggal Pulang');
$sheet->setCellValue('I' . $headerRow, 'Jam Pulang');
$sheet->setCellValue('J' . $headerRow, 'Jam Pulang Kantor');
$sheet->setCellValue('K' . $headerRow, 'Pulang Awal');
$sheet->setCellValue('L' . $headerRow, 'Lokasi Kerja');
$sheet->setCellValue('M' . $headerRow, 'Shift Presensi');
$sheet->setCellValue('N' . $headerRow, 'Ket. Izin');
$sheet->setCellValue('O' . $headerRow, 'Jam Izin');

// Style for header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];
$sheet->getStyle('A' . $headerRow . ':O' . $headerRow)->applyFromArray($headerStyle);

// Set column widths
$columnWidths = [
    'A' => 5, 'B' => 30, 'C' => 15, 'D' => 20, 'E' => 15, 'F' => 20,
    'G' => 15, 'H' => 20, 'I' => 15, 'J' => 20, 'K' => 15, 'L' => 20,
    'M' => 20, 'N' => 25, 'O' => 15,
];
foreach ($columnWidths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

// Data row counter
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
        if ($rekap['role'] == 'sumber' || $rekap['role'] == 'tidar') {
            $shift_query = "SELECT * FROM shift WHERE id = 1";
            $shift_result_db = $connection->query($shift_query)->fetch_assoc();
            $shift_code = strtolower($rekap['shift_sumber'] ?? 'a');

            if (isset($shift_result_db['masuk_' . $shift_code])) {
                $jam_masuk_kantor = date('H:i:s', strtotime($shift_result_db['masuk_' . $shift_code]));
            }
            if (isset($shift_result_db['pulang_' . $shift_code])) {
                $jam_pulang_kantor = date('H:i:s', strtotime($shift_result_db['pulang_' . $shift_code]));
            }
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
    }

    if ($status_filter == 'all' || $status_row == $status_filter) {
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

        $shift_jaga = $rekap['shift_sumber'] ?? '-';
        $shift_presensi_display = '';
        switch ($rekap['shift_presensi'] ?? '') {
            case 'A': $shift_presensi_display = 'Pagi'; break;
            case 'B': $shift_presensi_display = 'Siang'; break;
            case 'C': $shift_presensi_display = 'Pagi'; break;
            case 'D': $shift_presensi_display = 'Siang'; break;
            case 'E': $shift_presensi_display = 'Malam'; break;
            default: $shift_presensi_display = $rekap['shift_presensi'] ?? '-'; break;
        }
        
        $sheet->setCellValue('A' . $row, $no++);
        $sheet->setCellValue('B' . $row, $rekap['nama']);
        $sheet->setCellValue('C' . $row, $shift_jaga);
        $sheet->setCellValue('D' . $row, !empty($rekap['jadwal_tanggal']) ? date('d F Y', strtotime($rekap['jadwal_tanggal'])) : 'Belum presensi');
        $sheet->setCellValue('E' . $row, $jam_masuk);
        $sheet->setCellValue('F' . $row, $jam_masuk_kantor);
        $sheet->setCellValue('G' . $row, $terlambat_formatted);
        $sheet->setCellValue('H' . $row, $tgl_keluar);
        $sheet->setCellValue('I' . $row, $jam_keluar);
        $sheet->setCellValue('J' . $row, $jam_pulang_kantor);
        $sheet->setCellValue('K' . $row, $pulang_awal_formatted);
        $sheet->setCellValue('L' . $row, $rekap['lokasi_kerja'] ?? '-');
        $sheet->setCellValue('M' . $row, $shift_presensi_display);
        $sheet->setCellValue('N' . $row, $keterangan);
        $sheet->setCellValue('O' . $row, $jam_absen);

        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray($dataStyle);
        
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        if ($terlambat_seconds > 0) {
            $sheet->getStyle('G' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
        }
        if ($pulang_awal_seconds > 0) {
            $sheet->getStyle('K' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
        }
        if ($tgl_masuk == 'Belum presensi') {
            $sheet->getStyle('D' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
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

$filename = 'Rekap_Presensi_Sumber_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;