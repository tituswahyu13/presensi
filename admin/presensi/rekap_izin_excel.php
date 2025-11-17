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

// --- LOGIKA QUERY DARI REKAP_ALL.PHP ---
$nama_condition = $nama_filter ? "AND pegawai.nama LIKE ?" : "";

$select_clauses = "
    pegawai.id as pegawai_id,
    pegawai.nama,
    pegawai.lokasi_presensi,
    pegawai.bagian,
    pegawai.jabatan,
    users.role,
    COUNT(CASE WHEN presensi.tanggal_masuk IS NOT NULL THEN 1 END) as jumlah_hadir,
    COUNT(CASE WHEN presensi.tanggal_masuk IS NOT NULL 
                AND (presensi.tanggal_keluar IS NULL OR presensi.jam_keluar IS NULL) THEN 1 END) as jumlah_tidak_presensi_pulang,
    COUNT(CASE WHEN presensi.tanggal_masuk IS NOT NULL AND (
        (users.role = 'pegawai' AND presensi.jam_masuk > (
            CASE DAYOFWEEK(presensi.tanggal_masuk)
                WHEN 1 THEN jam_kerja.jam_masuk_senin
                WHEN 2 THEN jam_kerja.jam_masuk_selasa
                WHEN 3 THEN jam_kerja.jam_masuk_rabu
                WHEN 4 THEN jam_kerja.jam_masuk_kamis
                WHEN 5 THEN jam_kerja.jam_masuk_jumat
                ELSE jam_kerja.jam_masuk_sabtu
            END
        )) OR 
        (users.role IN ('sumber', 'tidar') AND presensi.jam_masuk > (
            CASE presensi.shift
                WHEN 'A' THEN shift.masuk_a
                WHEN 'B' THEN shift.masuk_b
                WHEN 'C' THEN shift.masuk_c
                WHEN 'D' THEN shift.masuk_d
                ELSE shift.masuk_e
            END
        ))
    ) THEN 1 END) as jumlah_terlambat,
    SUM(CASE WHEN presensi.tanggal_masuk IS NOT NULL AND (
        (users.role = 'pegawai' AND presensi.jam_masuk > (
            CASE DAYOFWEEK(presensi.tanggal_masuk)
                WHEN 1 THEN jam_kerja.jam_masuk_senin
                WHEN 2 THEN jam_kerja.jam_masuk_selasa
                WHEN 3 THEN jam_kerja.jam_masuk_rabu
                WHEN 4 THEN jam_kerja.jam_masuk_kamis
                WHEN 5 THEN jam_kerja.jam_masuk_jumat
                ELSE jam_kerja.jam_masuk_sabtu
            END
        )) OR 
        (users.role IN ('sumber', 'tidar') AND presensi.jam_masuk > (
            CASE presensi.shift
                WHEN 'A' THEN shift.masuk_a
                WHEN 'B' THEN shift.masuk_b
                WHEN 'C' THEN shift.masuk_c
                WHEN 'D' THEN shift.masuk_d
                ELSE shift.masuk_e
            END
        ))
    ) THEN 
        CASE 
            WHEN users.role = 'pegawai' THEN 
                TIMESTAMPDIFF(MINUTE, 
                    CONCAT(presensi.tanggal_masuk, ' ', 
                        CASE DAYOFWEEK(presensi.tanggal_masuk)
                            WHEN 1 THEN jam_kerja.jam_masuk_senin
                            WHEN 2 THEN jam_kerja.jam_masuk_selasa
                            WHEN 3 THEN jam_kerja.jam_masuk_rabu
                            WHEN 4 THEN jam_kerja.jam_masuk_kamis
                            WHEN 5 THEN jam_kerja.jam_masuk_jumat
                            ELSE jam_kerja.jam_masuk_sabtu
                        END
                    ),
                    CONCAT(presensi.tanggal_masuk, ' ', presensi.jam_masuk)
                )
            WHEN users.role IN ('sumber', 'tidar') THEN
                TIMESTAMPDIFF(MINUTE, 
                    CONCAT(presensi.tanggal_masuk, ' ', 
                        CASE presensi.shift
                            WHEN 'A' THEN shift.masuk_a
                            WHEN 'B' THEN shift.masuk_b
                            WHEN 'C' THEN shift.masuk_c
                            WHEN 'D' THEN shift.masuk_d
                            ELSE shift.masuk_e
                        END
                    ),
                    CONCAT(presensi.tanggal_masuk, ' ', presensi.jam_masuk)
                )
            ELSE 0
        END
    ELSE 0 END) as total_menit_terlambat,
    COUNT(CASE WHEN presensi.tanggal_keluar IS NOT NULL AND presensi.jam_keluar IS NOT NULL AND (
        (users.role = 'pegawai' AND presensi.jam_keluar < (
            CASE DAYOFWEEK(presensi.tanggal_keluar)
                WHEN 1 THEN jam_kerja.jam_pulang_senin
                WHEN 2 THEN jam_kerja.jam_pulang_selasa
                WHEN 3 THEN jam_kerja.jam_pulang_rabu
                WHEN 4 THEN jam_kerja.jam_pulang_kamis
                WHEN 5 THEN jam_kerja.jam_pulang_jumat
                ELSE jam_kerja.jam_pulang_sabtu
            END
        )) OR 
        (users.role IN ('sumber', 'tidar') AND presensi.jam_keluar < (
            CASE presensi.shift
                WHEN 'A' THEN shift.pulang_a
                WHEN 'B' THEN shift.pulang_b
                WHEN 'C' THEN shift.pulang_c
                WHEN 'D' THEN shift.pulang_d
                ELSE shift.pulang_e
            END
        ))
    ) THEN 1 END) as jumlah_pulang_awal,
    SUM(CASE WHEN presensi.tanggal_keluar IS NOT NULL AND presensi.jam_keluar IS NOT NULL AND (
        (users.role = 'pegawai' AND presensi.jam_keluar < (
            CASE DAYOFWEEK(presensi.tanggal_keluar)
                WHEN 1 THEN jam_kerja.jam_pulang_senin
                WHEN 2 THEN jam_kerja.jam_pulang_selasa
                WHEN 3 THEN jam_kerja.jam_pulang_rabu
                WHEN 4 THEN jam_kerja.jam_pulang_kamis
                WHEN 5 THEN jam_kerja.jam_pulang_jumat
                ELSE jam_kerja.jam_pulang_sabtu
            END
        )) OR 
        (users.role IN ('sumber', 'tidar') AND presensi.jam_keluar < (
            CASE presensi.shift
                WHEN 'A' THEN shift.pulang_a
                WHEN 'B' THEN shift.pulang_b
                WHEN 'C' THEN shift.pulang_c
                WHEN 'D' THEN shift.pulang_d
                ELSE shift.pulang_e
            END
        ))
    ) THEN 
        CASE 
            WHEN users.role = 'pegawai' THEN 
                TIMESTAMPDIFF(MINUTE, 
                    CONCAT(presensi.tanggal_keluar, ' ', presensi.jam_keluar),
                    CONCAT(presensi.tanggal_keluar, ' ', 
                        CASE DAYOFWEEK(presensi.tanggal_keluar)
                            WHEN 1 THEN jam_kerja.jam_pulang_senin
                            WHEN 2 THEN jam_kerja.jam_pulang_selasa
                            WHEN 3 THEN jam_kerja.jam_pulang_rabu
                            WHEN 4 THEN jam_kerja.jam_pulang_kamis
                            WHEN 5 THEN jam_kerja.jam_pulang_jumat
                            ELSE jam_kerja.jam_pulang_sabtu
                        END
                    )
                )
            WHEN users.role IN ('sumber', 'tidar') THEN
                TIMESTAMPDIFF(MINUTE, 
                    CONCAT(presensi.tanggal_keluar, ' ', presensi.jam_keluar),
                    CONCAT(presensi.tanggal_keluar, ' ', 
                        CASE presensi.shift
                            WHEN 'A' THEN shift.pulang_a
                            WHEN 'B' THEN shift.pulang_b
                            WHEN 'C' THEN shift.pulang_c
                            WHEN 'D' THEN shift.pulang_d
                            ELSE shift.pulang_e
                        END
                    )
                )
            ELSE 0
        END
    ELSE 0 END) as total_menit_pulang_awal,
    COUNT(CASE WHEN presensi.keterangan = 'Sakit' THEN 1 END) as jumlah_sakit,
    COUNT(CASE WHEN presensi.keterangan = 'Surat Dokter' THEN 1 END) as jumlah_surat_dokter,
    COUNT(CASE WHEN presensi.keterangan = 'Izin' THEN 1 END) as jumlah_izin,
    COUNT(CASE WHEN presensi.keterangan = 'Cuti' THEN 1 END) as jumlah_cuti,
    COUNT(CASE WHEN presensi.keterangan = 'Dinas Luar' THEN 1 END) as jumlah_dinas_luar,
    COUNT(CASE WHEN presensi.keterangan = 'Work From Home' THEN 1 END) as jumlah_work_from_home,
    COUNT(CASE WHEN presensi.tanggal_masuk IS NULL AND presensi.keterangan IS NULL THEN 1 END) as jumlah_tanpa_keterangan
";

$query = "SELECT $select_clauses
            FROM
                pegawai
                LEFT JOIN presensi ON pegawai.id = presensi.id_pegawai 
                AND presensi.tanggal_masuk BETWEEN ? AND ?
                LEFT JOIN users ON users.id_pegawai = pegawai.id 
                LEFT JOIN jam_kerja ON jam_kerja.id = 1
                LEFT JOIN shift ON shift.id = 1
            WHERE
                users.role != 'admin'
                AND users.status = 'aktif'
                $nama_condition
            GROUP BY pegawai.id, pegawai.nama, pegawai.lokasi_presensi, pegawai.bagian, pegawai.jabatan, users.role
            ORDER BY
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
                pegawai.id ASC;";

// Prepare and execute the query
$stmt = $connection->prepare($query);
if (!$stmt) {
    die('Error preparing query: ' . $connection->error);
}

if ($nama_filter) {
    $stmt->bind_param("sss", $tanggal_dari, $tanggal_sampai, $nama_filter);
} else {
    $stmt->bind_param("ss", $tanggal_dari, $tanggal_sampai);
}

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();

// --- AWAL PHP SPREADSHEET ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set title and date range
$sheet->setCellValue('A1', 'REKAPITULASI IZIN/CUTI KARYAWAN');
$sheet->setCellValue('A2', 'Periode: ' . date('d F Y', strtotime($tanggal_dari)) . ' s/d ' . date('d F Y', strtotime($tanggal_sampai)));
$sheet->mergeCells('A1:I1');
$sheet->mergeCells('A2:I2');
$sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set headers
$headerRow = 4;
$sheet->setCellValue('A' . $headerRow, 'No.');
$sheet->setCellValue('B' . $headerRow, 'Nama');
$sheet->setCellValue('C' . $headerRow, 'Sakit');
$sheet->setCellValue('D' . $headerRow, 'Surat Dokter');
$sheet->setCellValue('E' . $headerRow, 'Izin');
$sheet->setCellValue('F' . $headerRow, 'Cuti');
$sheet->setCellValue('G' . $headerRow, 'Dinas Luar');
$sheet->setCellValue('H' . $headerRow, 'Work From Home');
$sheet->setCellValue('I' . $headerRow, 'Tanpa Keterangan');

// Style for header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']], // Warna biru primer
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];
$sheet->getStyle('A' . $headerRow . ':I' . $headerRow)->applyFromArray($headerStyle);

// Set column widths
$columnWidths = [
    'A' => 5, 'B' => 30, 'C' => 15, 'D' => 15, 'E' => 15,
    'F' => 15, 'G' => 18, 'H' => 20, 'I' => 20,
];
foreach ($columnWidths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

// Data row counter
$row = $headerRow + 1;
$no = 1;

// Style for data rows
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'DDDDDD'],
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_TOP,
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
];
$leftAlignStyle = [
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
    ],
];
$dangerStyle = [
    'font' => [
        'color' => ['rgb' => 'FF0000'],
        'bold' => true,
    ],
];


while ($data = $result->fetch_assoc()) {
    // Hanya menampilkan data yang memiliki jumlah izin/cuti > 0
    // Kecuali jika ada filter status yang spesifik (yang tidak diimplementasikan di sini, jadi kita tampilkan semua yang relevan)
    
    // Totalkan semua kolom izin/cuti
    $total_izin_cuti = $data['jumlah_sakit'] + $data['jumlah_surat_dokter'] + $data['jumlah_izin'] + $data['jumlah_cuti'] + 
                       $data['jumlah_dinas_luar'] + $data['jumlah_work_from_home'] + $data['jumlah_tanpa_keterangan'];

    // Jika ingin hanya menampilkan pegawai yang memiliki aktivitas izin/cuti:
    // if ($total_izin_cuti == 0) continue; 

    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, $data['nama']);
    $sheet->setCellValue('C' . $row, $data['jumlah_sakit']);
    $sheet->setCellValue('D' . $row, $data['jumlah_surat_dokter']);
    $sheet->setCellValue('E' . $row, $data['jumlah_izin']);
    $sheet->setCellValue('F' . $row, $data['jumlah_cuti']);
    $sheet->setCellValue('G' . $row, $data['jumlah_dinas_luar']);
    $sheet->setCellValue('H' . $row, $data['jumlah_work_from_home']);
    $sheet->setCellValue('I' . $row, $data['jumlah_tanpa_keterangan']);

    // Apply styles
    $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataStyle);
    $sheet->getStyle('B' . $row)->applyFromArray($leftAlignStyle);

    // Apply danger color for 'Tanpa Keterangan'
    if ($data['jumlah_tanpa_keterangan'] > 0) {
        $sheet->getStyle('I' . $row)->applyFromArray($dangerStyle);
    }
    
    $row++;
}

// Set auto filter and freeze pane
$lastRow = $row - 1;
if ($lastRow > $headerRow) {
    $sheet->setAutoFilter('A' . $headerRow . ':I' . $lastRow);
}
$sheet->freezePane('A' . ($headerRow + 1));

// Print settings
$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
$sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);
$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, $headerRow);

// Filename and export
$filename = 'Rekap_Izin_Cuti_Ringkasan_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;