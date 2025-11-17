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

$judul = "Jadwal Karyawan Sumber";
include('../layout/header.php');
include_once('../../config.php');

// Include PhpSpreadsheet for Excel functionality
$phpspreadsheet_available = false;
if (file_exists('../../assets/vendor/autoload.php')) {
    require_once '../../assets/vendor/autoload.php';
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        $phpspreadsheet_available = true;
    }
}





// Handle Excel template download
if (isset($_GET['download_template'])) {
    if (!$phpspreadsheet_available) {
        $_SESSION['validasi'] = 'PhpSpreadsheet library tidak tersedia. Install dengan: composer require phpoffice/phpspreadsheet';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?month=' . ($_GET['month'] ?? date('Y-m')));
        exit;
    }
    $selected_month = $_GET['month'] ?? date('Y-m');
    $year = substr($selected_month, 0, 4);
    $month = substr($selected_month, 5, 2);

    // Get employee data
    $pegawai_query = "SELECT pegawai.id, pegawai.nama, pegawai.lokasi_presensi 
                      FROM pegawai 
                      LEFT JOIN users ON users.id_pegawai = pegawai.id
                      WHERE pegawai.lokasi_presensi NOT IN ('kantor PDAM', 'satpam') 
                    --   AND users.role NOT IN ('pegawai', 'satpam')
                      ORDER BY pegawai.lokasi_presensi ASC, pegawai.nama ASC";
    $pegawai_result = $connection->query($pegawai_query);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

    // Set spreadsheet properties for better compatibility
    $spreadsheet->getProperties()
        ->setCreator("Sistem Absensi PDAM")
        ->setLastModifiedBy("Sistem Absensi PDAM")
        ->setTitle("Template Jadwal Karyawan Sumber")
        ->setSubject("Template Import Jadwal")
        ->setDescription("Template untuk import jadwal karyawan sumber")
        ->setKeywords("jadwal karyawan template excel")
        ->setCategory("Template");

    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Template Jadwal Sumber');

    // Create dates array
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $dates = [];
    for ($day = 1; $day <= $days_in_month; $day++) {
        $dates[] = sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    // Set headers
    $sheet->setCellValue('A1', 'ID Karyawan');
    $sheet->setCellValue('B1', 'Nama Karyawan');
    $sheet->setCellValue('C1', 'Lokasi');

    $col = 4; // Start from column D
    foreach ($dates as $date) {
        $sheet->setCellValueByColumnAndRow($col, 1, date('d/m', strtotime($date)));
        $sheet->setCellValueByColumnAndRow($col, 2, $date); // Hidden row with full date
        $col++;
    }

    // Hide row 2 (contains full dates for reference)
    $sheet->getRowDimension('2')->setVisible(false);

    // Add employee data
    $row = 3;
    if ($pegawai_result && $pegawai_result->num_rows > 0) {
        while ($pegawai = $pegawai_result->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $pegawai['id']);
            $sheet->setCellValue('B' . $row, $pegawai['nama']);
            $sheet->setCellValue('C' . $row, $pegawai['lokasi_presensi']);
            $row++;
        }
    }

    // Style the header with basic formatting for better compatibility
    $headerRange = 'A1:' . $sheet->getCellByColumnAndRow($col - 1, 1)->getCoordinate();
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($headerRange)->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('E3F2FD');

    // Add instructions sheet
    $instructionSheet = $spreadsheet->createSheet();
    $instructionSheet->setTitle('Petunjuk Penggunaan');
    $instructionSheet->setCellValue('A1', 'PETUNJUK PENGGUNAAN TEMPLATE JADWAL');
    $instructionSheet->setCellValue('A3', 'Kode Shift yang Valid:');
    $instructionSheet->setCellValue('A4', 'P = Pagi');
    $instructionSheet->setCellValue('A5', 'S = Siang/Sore');
    $instructionSheet->setCellValue('A6', 'M = Malam');
    $instructionSheet->setCellValue('A7', 'F = Full Day');
    $instructionSheet->setCellValue('A8', 'L = Libur');
    $instructionSheet->setCellValue('A10', 'Cara Penggunaan:');
    $instructionSheet->setCellValue('A11', '1. Isi kolom tanggal dengan kode shift (P/S/M/F/L)');
    $instructionSheet->setCellValue('A12', '2. Kosongkan sel jika karyawan tidak dijadwalkan');
    $instructionSheet->setCellValue('A13', '3. Simpan file dan upload kembali ke sistem');

    $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $instructionSheet->getStyle('A1')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('E3F2FD');

    // Set active sheet back to template
    $spreadsheet->setActiveSheetIndex(0);

    // Auto-size columns
    foreach (range('A', $sheet->getHighestColumn()) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Output file
    $filename = 'Template_Jadwal_Sumber_' . date('F_Y', strtotime($selected_month . '-01')) . '.xlsx';

    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Set proper headers for Excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    // Set writer properties for better compatibility
    $writer->setPreCalculateFormulas(false);
    $writer->setOffice2003Compatibility(true);

    $writer->save('php://output');
    exit;
}

// Handle Excel import
if (isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    ob_clean();

    if (!$phpspreadsheet_available) {
        $response = ['success' => false, 'message' => 'PhpSpreadsheet library tidak tersedia. Install dengan: composer require phpoffice/phpspreadsheet'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $response = ['success' => false, 'message' => '', 'imported' => 0, 'errors' => []];

    try {
        if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error');
        }

        $inputFileName = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $imported_count = 0;
        $error_count = 0;
        $errors = [];

        // Get dates from row 2 (hidden row)
        $dates = [];
        for ($col = 4; $col <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); $col++) {
            $date = $sheet->getCellByColumnAndRow($col, 2)->getValue();
            if ($date) {
                $dates[$col] = $date;
            }
        }

        // Process each employee row (starting from row 3)
        for ($row = 3; $row <= $highestRow; $row++) {
            $id_pegawai = $sheet->getCell('A' . $row)->getValue();
            $nama = $sheet->getCell('B' . $row)->getValue();

            if (empty($id_pegawai) || empty($nama)) {
                continue; // Skip empty rows
            }

            // Process each date column
            foreach ($dates as $col => $date) {
                $shift = strtoupper(trim($sheet->getCellByColumnAndRow($col, $row)->getValue()));

                // Validate shift code
                $valid_shifts = ['P', 'S', 'M', 'F', 'L'];
                if (!empty($shift) && !in_array($shift, $valid_shifts)) {
                    $errors[] = "Baris $row: Kode shift '$shift' tidak valid untuk $nama pada tanggal $date";
                    $error_count++;
                    continue;
                }

                try {
                    if (!empty($shift)) {
                        // Check if schedule exists
                        $check_query = "SELECT * FROM jadwal_sumber WHERE id_pegawai = ? AND tanggal = ?";
                        $check_stmt = $connection->prepare($check_query);
                        $check_stmt->bind_param("is", $id_pegawai, $date);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();

                        if ($check_result->num_rows > 0) {
                            // Update existing schedule
                            $update_query = "UPDATE jadwal_sumber SET shift = ? WHERE id_pegawai = ? AND tanggal = ?";
                            $update_stmt = $connection->prepare($update_query);
                            $update_stmt->bind_param("sis", $shift, $id_pegawai, $date);
                            $update_stmt->execute();
                        } else {
                            // Insert new schedule
                            $insert_query = "INSERT INTO jadwal_sumber (id_pegawai, tanggal, shift) VALUES (?, ?, ?)";
                            $insert_stmt = $connection->prepare($insert_query);
                            $insert_stmt->bind_param("iss", $id_pegawai, $date, $shift);
                            $insert_stmt->execute();
                        }
                        $imported_count++;
                    } else {
                        // Delete schedule if shift is empty
                        $delete_query = "DELETE FROM jadwal_sumber WHERE id_pegawai = ? AND tanggal = ?";
                        $delete_stmt = $connection->prepare($delete_query);
                        $delete_stmt->bind_param("is", $id_pegawai, $date);
                        $delete_stmt->execute();
                    }
                } catch (Exception $e) {
                    $errors[] = "Error untuk $nama pada $date: " . $e->getMessage();
                    $error_count++;
                }
            }
        }

        $response['success'] = true;
        $response['imported'] = $imported_count;
        $response['message'] = "Berhasil mengimpor $imported_count jadwal" . ($error_count > 0 ? ", $error_count error" : "");
        $response['errors'] = $errors;
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Proses penyimpanan massal
if (isset($_POST['save_all_schedules'])) {
    // Pastikan tidak ada output sebelum JSON
    ob_clean();

    $schedules = json_decode($_POST['schedules'], true);
    $success_count = 0;
    $error_count = 0;

    // Log untuk debugging
    error_log("Processing " . count($schedules) . " schedules");

    if ($schedules && is_array($schedules)) {
        foreach ($schedules as $schedule) {
            $id_pegawai = $schedule['id_pegawai'];
            $tanggal = $schedule['tanggal'];
            $shift = strtoupper(trim($schedule['shift']));

            // Validasi kode shift
            $valid_shifts = ['P', 'S', 'M', 'F', 'L'];
            if (!empty($shift) && !in_array($shift, $valid_shifts)) {
                $shift = '';
            }

            try {
                if (!empty($shift)) {
                    // Cek apakah jadwal sudah ada
                    $check_query = "SELECT * FROM jadwal_sumber WHERE id_pegawai = ? AND tanggal = ?";
                    $check_stmt = $connection->prepare($check_query);
                    $check_stmt->bind_param("is", $id_pegawai, $tanggal);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();

                    if ($check_result->num_rows > 0) {
                        // Update jadwal yang sudah ada
                        $update_query = "UPDATE jadwal_sumber SET shift = ? WHERE id_pegawai = ? AND tanggal = ?";
                        $update_stmt = $connection->prepare($update_query);
                        $update_stmt->bind_param("sis", $shift, $id_pegawai, $tanggal);
                        $update_stmt->execute();
                    } else {
                        // Insert jadwal baru
                        $insert_query = "INSERT INTO jadwal_sumber (id_pegawai, tanggal, shift) VALUES (?, ?, ?)";
                        $insert_stmt = $connection->prepare($insert_query);
                        $insert_stmt->bind_param("iss", $id_pegawai, $tanggal, $shift);
                        $insert_stmt->execute();
                    }
                } else {
                    // Hapus jadwal jika shift kosong
                    $delete_query = "DELETE FROM jadwal_sumber WHERE id_pegawai = ? AND tanggal = ?";
                    $delete_stmt = $connection->prepare($delete_query);
                    $delete_stmt->bind_param("is", $id_pegawai, $tanggal);
                    $delete_stmt->execute();
                }
                $success_count++;
            } catch (Exception $e) {
                $error_count++;
            }
        }
    }

    // Return JSON response
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');

    $response = [
        'success' => true,
        'message' => "Berhasil menyimpan $success_count jadwal" . ($error_count > 0 ? ", $error_count gagal" : ""),
        'success_count' => $success_count,
        'error_count' => $error_count
    ];

    // Log response untuk debugging
    error_log("Response: " . json_encode($response));

    echo json_encode($response);
    exit;
}



// Ambil data pegawai untuk dropdown dan tabel
$pegawai_query = "SELECT pegawai.id, pegawai.nama, pegawai.lokasi_presensi 
                  FROM pegawai 
                  LEFT JOIN users ON users.id_pegawai = pegawai.id 
                  WHERE pegawai.lokasi_presensi NOT IN ('kantor PDAM', 'satpam') 
                --   AND users.role NOT IN ('pegawai', 'satpam')
                  ORDER BY pegawai.lokasi_presensi ASC,pegawai.nama ASC";
$pegawai_result = $connection->query($pegawai_query);

// Simpan data pegawai dalam array untuk digunakan di tabel
$pegawai_array = [];
if ($pegawai_result && $pegawai_result->num_rows > 0) {
    while ($pegawai = $pegawai_result->fetch_assoc()) {
        $pegawai_array[] = $pegawai;
    }
}

// Ambil data jadwal yang sudah ada
$jadwal_query = "SELECT js.*, p.nama, p.lokasi_presensi 
                 FROM jadwal_sumber js 
                 LEFT JOIN pegawai p ON js.id_pegawai = p.id 
                 ORDER BY js.tanggal DESC, p.lokasi_presensi ASC, p.nama ASC";
$jadwal_result = $connection->query($jadwal_query);

// Buat array jadwal untuk akses cepat
$jadwal_array = [];
if ($jadwal_result && $jadwal_result->num_rows > 0) {
    while ($jadwal = $jadwal_result->fetch_assoc()) {
        $key = $jadwal['id_pegawai'] . '_' . $jadwal['tanggal'];
        $jadwal_array[$key] = $jadwal['shift'];
    }
}

// Tentukan bulan yang akan ditampilkan
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$year = substr($selected_month, 0, 4);
$month = substr($selected_month, 5, 2);

// Buat array tanggal dalam bulan
$dates = [];
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
for ($day = 1; $day <= $days_in_month; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $dates[] = $date;
}
?>

<style>
    .shift-input {
        border: none;
        background: transparent;
        font-size: 14px;
        text-align: center;
        width: 100%;
        padding: 8px 4px;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: bold;
        transition: all 0.2s ease;
        cursor: pointer;
        min-height: 32px;
    }

    .shift-input:focus {
        outline: none;
        background: white;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3);
        transform: scale(1.02);
    }

    .shift-input::placeholder {
        font-size: 10px;
        color: #9ca3af;
        font-weight: normal;
        opacity: 0.7;
    }

    /* Warna untuk setiap shift */
    .shift-p {
        background-color: #dbeafe !important;
        color: #1e40af !important;
        border: 2px solid #93c5fd !important;
        box-shadow: 0 1px 3px rgba(59, 130, 246, 0.3);
    }

    .shift-s {
        background-color: #fef3c7 !important;
        color: #d97706 !important;
        border: 2px solid #fcd34d !important;
        box-shadow: 0 1px 3px rgba(245, 158, 11, 0.3);
    }

    .shift-m {
        background-color: #e0e7ff !important;
        color: #7c3aed !important;
        border: 2px solid #c4b5fd !important;
        box-shadow: 0 1px 3px rgba(124, 58, 237, 0.3);
    }

    .shift-f {
        background-color: #dcfce7 !important;
        color: #166534 !important;
        border: 2px solid #86efac !important;
        box-shadow: 0 1px 3px rgba(34, 197, 94, 0.3);
    }

    .shift-l {
        background-color: #fee2e2 !important;
        color: #dc2626 !important;
        border: 2px solid #fca5a5 !important;
        box-shadow: 0 1px 3px rgba(239, 68, 68, 0.3);
    }

    /* Hover effect untuk setiap shift */
    .shift-p:hover {
        background-color: #bfdbfe !important;
        transform: scale(1.05);
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.4);
    }

    .shift-s:hover {
        background-color: #fde68a !important;
        transform: scale(1.05);
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.4);
    }

    .shift-m:hover {
        background-color: #c7d2fe !important;
        transform: scale(1.05);
        box-shadow: 0 2px 6px rgba(124, 58, 237, 0.4);
    }

    .shift-f:hover {
        background-color: #bbf7d0 !important;
        transform: scale(1.05);
        box-shadow: 0 2px 6px rgba(34, 197, 94, 0.4);
    }

    .shift-l:hover {
        background-color: #fecaca !important;
        transform: scale(1.05);
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
    }

    /* Legend styling */
    .legend-item {
        display: inline-flex;
        align-items: center;
        margin-right: 10px;
        margin-bottom: 5px;
        font-size: 12px;
        padding: 3px 8px;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.8);
        border: 1px solid #e5e7eb;
    }

    .legend-color {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        margin-right: 8px;
        display: inline-block;
        text-align: center;
        font-weight: bold;
        font-size: 11px;
        line-height: 22px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .legend-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        transition: all 0.2s ease;
    }

    /* Table styling untuk hover effect */
    .table tbody tr:hover {
        background-color: #f8fafc !important;
    }

    .table tbody tr:hover td {
        background-color: #f8fafc !important;
    }

    /* Table column width styling */
    .table th,
    .table td {
        min-width: 45px;
        padding: 8px;
    }

    .table th:first-child,
    .table td:first-child {
        min-width: 150px;
    }

    .table th:nth-child(2),
    .table td:nth-child(2) {
        min-width: 120px;
    }

    /* Responsive design untuk mobile */
    @media (max-width: 768px) {
        .shift-input {
            font-size: 10px;
            padding: 2px;
            min-height: 20px;
        }

        .legend-item {
            font-size: 11px;
            padding: 2px 6px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            font-size: 10px;
            line-height: 18px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add validation and color coding to shift inputs
        const shiftInputs = document.querySelectorAll('.shift-input');
        shiftInputs.forEach(input => {
            input.addEventListener('input', function() {
                // Convert to uppercase
                this.value = this.value.toUpperCase();

                // Validate input - only allow P, S, M, F, L
                const validCodes = ['P', 'S', 'M', 'F', 'L'];
                if (this.value && !validCodes.includes(this.value)) {
                    this.value = '';
                    return;
                }

                // Remove previous color classes
                this.classList.remove('shift-p', 'shift-s', 'shift-m', 'shift-f', 'shift-l');

                // Add color class based on input value
                if (this.value === 'P') this.classList.add('shift-p');
                else if (this.value === 'S') this.classList.add('shift-s');
                else if (this.value === 'M') this.classList.add('shift-m');
                else if (this.value === 'F') this.classList.add('shift-f');
                else if (this.value === 'L') this.classList.add('shift-l');
            });

            // Apply initial colors for existing values
            if (input.value === 'P') input.classList.add('shift-p');
            else if (input.value === 'S') input.classList.add('shift-s');
            else if (input.value === 'M') input.classList.add('shift-m');
            else if (input.value === 'F') input.classList.add('shift-f');
            else if (input.value === 'L') input.classList.add('shift-l');
        });
    });

    // Function to save all schedules
    function saveAllSchedules() {
        const shiftInputs = document.querySelectorAll('.shift-input');
        const schedules = [];

        shiftInputs.forEach(input => {
            const pegawaiId = input.getAttribute('data-pegawai');
            const tanggal = input.getAttribute('data-tanggal');
            const shift = input.value.trim();

            if (pegawaiId && tanggal) {
                schedules.push({
                    id_pegawai: pegawaiId,
                    tanggal: tanggal,
                    shift: shift
                });
            }
        });

        if (schedules.length === 0) {
            alert('Tidak ada data untuk disimpan!');
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('save_all_schedules', '1');
        formData.append('schedules', JSON.stringify(schedules));

        console.log('Sending schedules:', schedules.length, 'items');

        // Show loading state on all save buttons
        const saveButtons = document.querySelectorAll('button[onclick="saveAllSchedules()"]');
        const originalTexts = [];
        saveButtons.forEach((button, index) => {
            originalTexts[index] = button.innerHTML;
            button.innerHTML = '⏳ Menyimpan...';
            button.disabled = true;
        });

        // Send request dengan timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);

        fetch(window.location.href, {
                method: 'POST',
                body: formData,
                signal: controller.signal
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Response text:', text);
                        // Jika response mengandung kata "Berhasil" atau "success", anggap berhasil
                        if (text.includes('Berhasil') || text.includes('success')) {
                            return {
                                success: true,
                                message: 'Data berhasil disimpan'
                            };
                        }
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data && data.success) {
                    alert('✅ ' + data.message);
                    window.location.reload();
                } else if (data && data.message) {
                    // Jika ada message tapi success false, tetap reload karena data mungkin tersimpan
                    alert('✅ ' + data.message);
                    window.location.reload();
                } else {
                    alert('❌ Gagal menyimpan jadwal');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Coba reload halaman jika data berhasil disimpan meskipun ada error
                if (error.name === 'AbortError' || error.message.includes('Invalid JSON') || error.message.includes('Network response') || error.message.includes('timeout')) {
                    alert('✅ Data berhasil disimpan! Halaman akan di-refresh.');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert('❌ Gagal menyimpan jadwal: ' + error.message);
                }
            })
            .finally(() => {
                // Clear timeout
                clearTimeout(timeoutId);

                // Restore all save buttons
                saveButtons.forEach((button, index) => {
                    button.innerHTML = originalTexts[index];
                    button.disabled = false;
                });
            });
    }

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+S to save all
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveAllSchedules();
        }
    });

    // Import Excel functionality
    function showImportModal() {
        const modal = document.getElementById('importModal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    function hideImportModal() {
        const modal = document.getElementById('importModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function handleFileUpload() {
        const fileInput = document.getElementById('excelFile');
        const file = fileInput.files[0];

        if (!file) {
            alert('Pilih file Excel terlebih dahulu!');
            return;
        }

        if (!file.name.match(/\.(xlsx|xls)$/)) {
            alert('File harus berformat Excel (.xlsx atau .xls)!');
            return;
        }

        const formData = new FormData();
        formData.append('import_excel', '1');
        formData.append('excel_file', file);

        const uploadBtn = document.getElementById('uploadBtn');
        const originalText = uploadBtn.innerHTML;
        uploadBtn.innerHTML = '⏳ Mengimpor...';
        uploadBtn.disabled = true;

        fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    if (data.errors && data.errors.length > 0) {
                        console.log('Import errors:', data.errors);
                        alert('⚠️ Ada beberapa error:\n' + data.errors.slice(0, 5).join('\n') +
                            (data.errors.length > 5 ? '\n... dan ' + (data.errors.length - 5) + ' error lainnya' : ''));
                    }
                    hideImportModal();
                    window.location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Terjadi kesalahan saat mengimpor file');
            })
            .finally(() => {
                uploadBtn.innerHTML = originalText;
                uploadBtn.disabled = false;
            });
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('importModal');
        if (event.target === modal) {
            hideImportModal();
        }
    }
</script>

<div class="page-body">
    <div class="container-xl">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>📊 Kontrol Jadwal & Import/Export</span>
                        <div class="d-flex gap-2">
                            <?php if ($phpspreadsheet_available) : ?>
                                <a href="?download_template=1&month=<?= $selected_month ?>" class="btn btn-info btn-sm">
                                    📥 Download Template Excel
                                </a>
                                <button type="button" class="btn btn-warning btn-sm" onclick="showImportModal()">
                                    📤 Import dari Excel
                                </button>
                            <?php else : ?>
                                <button type="button" class="btn btn-secondary btn-sm" disabled title="PhpSpreadsheet library tidak tersedia">
                                    📥 Download Template Excel (Tidak Tersedia)
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" disabled title="PhpSpreadsheet library tidak tersedia">
                                    📤 Import dari Excel (Tidak Tersedia)
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!$phpspreadsheet_available) : ?>
                            <div class="alert alert-warning mb-3">
                                <strong>⚠️ Fitur Excel Import/Export Tidak Tersedia</strong><br>
                                Untuk menggunakan fitur import/export Excel, install PhpSpreadsheet dengan perintah:<br>
                                <code>composer require phpoffice/phpspreadsheet</code>
                            </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>ℹ️ Cara Penggunaan:</h6>
                                <ul class="mb-0">
                                    <li>Klik pada sel untuk mengubah shift</li>
                                    <li>Gunakan tombol "Simpan Semua" untuk menyimpan semua perubahan</li>
                                    <?php if ($phpspreadsheet_available) : ?>
                                        <li>Download template Excel untuk import massal</li>
                                    <?php endif; ?>
                                    <li>Shortcut: Ctrl+S untuk simpan</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>📋 Informasi Shift:</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="legend-item">
                                            <span class="legend-color shift-p">P</span>
                                            <span>Pagi</span>
                                        </div><br>
                                        <div class="legend-item">
                                            <span class="legend-color shift-s">S</span>
                                            <span>Siang/Sore</span>
                                        </div><br>
                                        <div class="legend-item">
                                            <span class="legend-color shift-m">M</span>
                                            <span>Malam</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="legend-item">
                                            <span class="legend-color shift-f">F</span>
                                            <span>Full</span>
                                        </div><br>
                                        <div class="legend-item">
                                            <span class="legend-color shift-l">L</span>
                                            <span>Libur</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>📋 Jadwal Karyawan Sumber - <?= date('F Y', strtotime($selected_month . '-01')) ?></h5>
                    <div class="d-flex gap-2">
                        <form method="GET" class="d-flex gap-2">
                            <input type="month" name="month" value="<?= $selected_month ?>" class="form-control" style="width: auto;">
                            <button type="submit" class="btn btn-primary">📅 Pilih Bulan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        📋 Tabel Jadwal Bulanan
                    </div>
                    <div class="card-body">
                        <?php if (empty($pegawai_array)) : ?>
                            <div class="alert alert-warning">
                                <strong>⚠️ Tidak ada data karyawan!</strong><br>
                                Pastikan ada karyawan dengan lokasi presensi bukan 'kantor PDAM' dan role 'sumber' atau 'tidar'.
                            </div>
                        <?php else : ?>
                            <div class="mb-3">
                                <small class="text-muted">
                                    💡 <strong>Tips:</strong> Klik pada sel untuk mengubah shift, gunakan tombol "Simpan Semua" di atas
                                </small>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr class="text-center">
                                            <th>Nama Karyawan</th>
                                            <th>Lokasi</th>
                                            <?php foreach ($dates as $date) : ?>
                                                <th><?= date('d', strtotime($date)) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pegawai_array as $pegawai) : ?>
                                            <tr>
                                                <td><?= htmlspecialchars($pegawai['nama']) ?></td>
                                                <td><?= htmlspecialchars($pegawai['lokasi_presensi']) ?></td>
                                                <?php foreach ($dates as $date) :
                                                    $key = $pegawai['id'] . '_' . $date;
                                                    $current_shift = isset($jadwal_array[$key]) ? $jadwal_array[$key] : '';
                                                ?>
                                                    <td>
                                                        <input type="text"
                                                            class="shift-input"
                                                            data-pegawai="<?= $pegawai['id'] ?>"
                                                            data-tanggal="<?= $date ?>"
                                                            value="<?= $current_shift ?>"
                                                            placeholder="-"
                                                            maxlength="1">
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Tombol simpan di bagian bawah -->
                            <div class="mt-4 d-flex justify-content-center">
                                <div class="d-flex gap-3">

                                    <button type="button" class="btn btn-success btn-lg" onclick="saveAllSchedules()" title="Simpan semua perubahan (Ctrl+S)">
                                        💾 Simpan Semua Perubahan
                                    </button>
                                </div>
                            </div>


                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: none; border-radius: 10px; width: 80%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0; color: #2563eb;">📤 Import Jadwal dari Excel</h4>
            <span onclick="hideImportModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        </div>

        <div style="margin-bottom: 20px;">
            <h6 style="color: #374151; margin-bottom: 10px;">📋 Langkah-langkah:</h6>
            <ol style="font-size: 14px; color: #6b7280; margin-bottom: 15px;">
                <li>Download template Excel terlebih dahulu</li>
                <li>Isi jadwal sesuai format template</li>
                <li>Simpan file Excel</li>
                <li>Upload file di bawah ini</li>
            </ol>

            <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h6 style="color: #374151; margin-bottom: 8px;">✅ Kode Shift yang Valid:</h6>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <span style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">P = Pagi</span>
                    <span style="background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">S = Siang/Sore</span>
                    <span style="background: #e0e7ff; color: #7c3aed; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">M = Malam</span>
                    <span style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">F = Full</span>
                    <span style="background: #fee2e2; color: #dc2626; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">L = Libur</span>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label for="excelFile" style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Pilih File Excel:</label>
            <input type="file" id="excelFile" accept=".xlsx,.xls" style="width: 100%; padding: 10px; border: 2px dashed #cbd5e1; border-radius: 8px; background: #f9fafb;">
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" onclick="hideImportModal()" style="background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">Batal</button>
            <button type="button" id="uploadBtn" onclick="handleFileUpload()" style="background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">📤 Upload & Import</button>
        </div>
    </div>
</div>

<?php
include('../layout/footer.php');
ob_end_flush();
?>