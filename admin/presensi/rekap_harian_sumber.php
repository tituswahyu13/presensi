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

$judul = "Rekap Presensi Harian Sumber";
include('../layout/header.php');
include_once('../../config.php');

// DEBUG: Aktifkan error reporting sementara (hapus di produksi)
error_reporting(E_ALL);
ini_set('display_errors', '1');
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$nama_condition = isset($_GET['nama']) ? "AND p.nama LIKE ?" : "";
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

if (empty($_GET["tanggal_dari"])) {
    $tanggal_dari = date('Y-m-d');
    $tanggal_sampai = date('Y-m-d');
} else {
    $tanggal_dari = $_GET["tanggal_dari"];
    $tanggal_sampai = $_GET["tanggal_sampai"];
}

// Kueri yang Diperbaiki: Jadwal Sumber sebagai Tabel Utama
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
if (!$stmt) {
    die("Prepare failed: " . $connection->error);
}

if (isset($_GET['nama'])) {
    $nama = "%" . $_GET['nama'] . "%";
    if (!$stmt->bind_param("sss", $tanggal_dari, $tanggal_sampai, $nama)) {
        die("bind_param failed: " . $stmt->error);
    }
} else {
    if (!$stmt->bind_param("ss", $tanggal_dari, $tanggal_sampai)) {
        die("bind_param failed: " . $stmt->error);
    }
}

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result === false) {
    die("get_result() failed. Kemungkinan extension mysqlnd belum aktif. Pesan: " . $stmt->error);
}

$bulan = empty($_GET['tanggal_dari'])
    ? date('d F Y', strtotime($tanggal_dari))
    : date('d F Y', strtotime($tanggal_dari)) . ' s/d ' . date('d F Y', strtotime($tanggal_sampai));
?>

<style>
    /* Variabel warna - Flat Design */
    :root {
        --primary-color: #2563eb;
        --primary-hover: #1d4ed8;
        --secondary-color: #64748b;
        --secondary-hover: #475569;
        --success-color: #22c55e;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --background-light: #ffffff;
        --border-color: #e5e7eb;
        --text-dark: #1f2937;
        --text-muted: #6b7280;
    }

    .page-body {
        padding: 20px;
        background-color: #f9fafb;
    }

    .container-xl {
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        max-width: 100%;
        width: 100%;
    }

    .btn {
        border-radius: 6px;
        padding: 10px 16px;
        font-weight: 500;
        font-size: 14px;
        text-transform: none;
        letter-spacing: 0;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        background: white;
        color: var(--text-dark);
    }

    .btn:hover {
        transform: none;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        border-color: var(--primary-hover);
        transform: none;
        box-shadow: none;
    }

    .btn-secondary {
        background: var(--secondary-color);
        color: white;
        border-color: var(--secondary-color);
    }

    .btn-secondary:hover {
        background: var(--secondary-hover);
        border-color: var(--secondary-hover);
        transform: none;
        box-shadow: none;
    }

    .table {
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: white;
    }

    .table thead th {
        background: var(--primary-color);
        color: white;
        font-weight: 500;
        border: none;
        padding: 12px;
        font-size: 13px;
        text-transform: none;
        letter-spacing: 0;
    }

    /* Warna baris tabel - Flat */
    tr[style*="Kantor PDAM"] {
        background-color: #f0f9ff !important;
    }

    tr[style*="background-color: #d1e7dd"] {
        background-color: #f0fdf4 !important;
    }

    td[style*="background-color: #f0f8ff"] {
        background-color: #f8fafc !important;
    }

    td[style*="background-color: #e6ffe6"] {
        background-color: #f0fdf4 !important;
    }

    td[style*="background-color: #ffe6e6"] {
        background-color: #fef2f2 !important;
    }

    td[style*="background-color: #fff3e6"] {
        background-color: #fff7ed !important;
    }

    /* Badge styles - Flat */
    .badge {
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 500;
        font-size: 12px;
        text-transform: none;
        letter-spacing: 0;
        border: 1px solid transparent;
        min-width: 40px;
        display: inline-block;
    }

    .badge:hover {
        transform: none;
        box-shadow: none;
    }

    .badge.bg-success {
        background: var(--success-color) !important;
        color: white !important;
        border-color: var(--success-color);
    }

    .badge.bg-primary {
        background: var(--primary-color) !important;
        color: white !important;
        border-color: var(--primary-color);
    }

    .badge.bg-secondary {
        background: var(--secondary-color) !important;
        color: white !important;
        border-color: var(--secondary-color);
    }

    .badge.bg-danger {
        background: var(--danger-color) !important;
        color: white !important;
        border-color: var(--danger-color);
    }

    /* Status colors */
    span[style*="color: red"] {
        color: var(--danger-color) !important;
    }

    /* Input groups - Flat */
    .input-group {
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: white;
    }

    .input-group:hover {
        box-shadow: none;
    }

    .input-group input {
        border: none;
        padding: 10px 12px;
        font-size: 14px;
        font-weight: 400;
        background: white;
    }

    .input-group input:focus {
        outline: none;
        background: white;
        box-shadow: none;
    }

    .input-group .btn {
        border-radius: 0 6px 6px 0;
        margin: 0;
    }

    /* Modal styling - Flat */
    .modal-content {
        border-radius: 8px;
        border: 1px solid var(--border-color);
        box-shadow: none;
    }

    .modal-header {
        background: var(--primary-color);
        color: white;
        border-radius: 8px 8px 0 0;
        border-bottom: none;
        padding: 16px 20px;
    }

    .modal-header .modal-title {
        font-weight: 600;
        text-transform: none;
        letter-spacing: 0;
    }

    .modal-body {
        padding: 20px;
        background: white;
    }

    .modal-body .form-label {
        font-weight: 500;
        color: var(--text-dark);
        text-transform: none;
        letter-spacing: 0;
        margin-bottom: 8px;
    }

    .modal-body .form-control {
        border-radius: 6px;
        border: 1px solid var(--border-color);
        padding: 10px 12px;
        font-weight: 400;
    }

    .modal-body .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: none;
        outline: none;
    }

    /* Jadwal Jaga - Minimalist Elegant Design */
    .jadwal-pagi {
        background: #fff8f0;
        color: #d97706;
        border: 1px solid #fed7aa;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 11px;
        text-align: center;
        letter-spacing: 0.5px;
        display: inline-block;
        min-width: 75px;
        width: 75px;
        transition: all 0.2s ease;
    }

    .jadwal-siang {
        background: #f0f9ff;
        color: #0284c7;
        border: 1px solid #bae6fd;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 11px;
        text-align: center;
        letter-spacing: 0.5px;
        display: inline-block;
        min-width: 75px;
        width: 75px;
        transition: all 0.2s ease;
    }

    .jadwal-malam {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 11px;
        text-align: center;
        letter-spacing: 0.5px;
        display: inline-block;
        min-width: 75px;
        width: 75px;
        transition: all 0.2s ease;
    }

    .jadwal-full {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 11px;
        text-align: center;
        letter-spacing: 0.5px;
        display: inline-block;
        min-width: 75px;
        width: 75px;
        transition: all 0.2s ease;
    }

    .jadwal-libur {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 11px;
        text-align: center;
        letter-spacing: 0.5px;
        display: inline-block;
        min-width: 75px;
        width: 75px;
        transition: all 0.2s ease;
    }

    .jadwal-default {
        background: #f9fafb;
        color: #6b7280;
        border: 1px solid #e5e7eb;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 11px;
        text-align: center;
        letter-spacing: 0.5px;
        display: inline-block;
        min-width: 75px;
        width: 75px;
        transition: all 0.2s ease;
    }

    /* Hover effects for elegant interaction */
    .jadwal-pagi:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(217, 119, 6, 0.15);
    }

    .jadwal-siang:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.15);
    }

    .jadwal-malam:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(71, 85, 105, 0.15);
    }

    .jadwal-full:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.15);
    }

    .jadwal-libur:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.15);
    }

    .jadwal-default:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(107, 114, 128, 0.15);
    }

    /* Date display - Flat */
    .date-display {
        background: white;
        color: var(--text-dark);
        font-size: 14px;
        font-weight: 500;
        margin: 20px 0;
        padding: 12px 16px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        text-align: center;
        text-transform: none;
        letter-spacing: 0;
    }

    /* Action buttons container - Flat */
    .action-buttons {
        background: white;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid var(--border-color);
    }

    /* Filter buttons container - Flat */
    .filter-buttons {
        background: white;
        padding: 12px;
        border-radius: 8px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        border: 1px solid var(--border-color);
    }

    /* Tambahkan CSS baru untuk tombol - Flat */
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .action-buttons .btn {
        min-width: 100px;
        padding: 8px 12px;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 36px;
    }

    .filter-buttons {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
    }

    .filter-buttons .btn {
        min-width: 90px;
        white-space: nowrap;
    }

    .search-container {
        flex: 1;
        margin-left: 15px;
    }

    .search-container .input-group {
        max-width: 100%;
    }

    .date-filter {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .date-filter input[type="date"] {
        width: 200px;
    }

    .export-btn {
        width: 100%;
        margin-bottom: 15px;
    }

    .badge-pill {
        padding: 6px 12px;
        text-decoration: none;
        margin: 0 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0;
        border-radius: 4px;
        border: 1px solid transparent;
        display: inline-block;
        min-width: 60px;
    }

    .badge-pill:hover {
        transform: none;
        box-shadow: none;
        text-decoration: none;
    }

    /* Foto column - Flat */
    .foto-column img {
        border-radius: 6px;
        border: 1px solid var(--border-color);
        transition: none;
        max-height: 80px;
        width: auto;
        margin: 0 auto;
        display: block;
    }

    .foto-column img:hover {
        transform: none;
    }

    /* Sort button styling */
    .sort-btn {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 12px;
        margin-left: 5px;
        padding: 2px 4px;
        border-radius: 3px;
        transition: background-color 0.2s ease;
    }

    .sort-btn:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }
</style>

<style>
    /* Tambahan agar tabel tidak melebihi container dan bisa di-scroll - Flat */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        position: relative;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .table {
        min-width: 1200px;
        margin-bottom: 0;
        border-collapse: collapse;
        background: white;
    }

    /* Sticky header saat scroll horizontal - Flat */
    .table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--primary-color) !important;
        color: white !important;
        font-weight: 500;
        font-size: 12px;
        text-align: center;
        vertical-align: middle;
        border: none;
        white-space: nowrap;
        padding: 12px 10px;
        text-transform: none;
        letter-spacing: 0;
    }

    /* Sticky kolom pertama - Flat */
    .table th:first-child,
    .table td:first-child {
        position: sticky;
        left: 0;
        z-index: 3;
        background: white;
        border-right: 1px solid var(--border-color);
    }

    .table th,
    .table td {
        vertical-align: middle;
        text-align: center;
        white-space: nowrap;
        border: 1px solid var(--border-color);
        font-size: 13px;
        padding: 10px 8px;
        background: white;
    }

    .table th {
        color: white !important;
    }

    .table td {
        color: var(--text-dark) !important;
        background: white;
        font-weight: 400;
    }

    .table td:first-child {
        text-align: left;
        font-weight: 500;
        color: var(--primary-color) !important;
        background: #f8fafc;
    }

    .table tbody tr {
        border-bottom: 1px solid var(--border-color);
    }

    .table tbody tr:hover {
        background: #f9fafb;
        transform: none;
        box-shadow: none;
    }

    .table tbody tr:nth-child(even) {
        background: #fafbfc;
    }

    .table tbody tr:nth-child(even):hover {
        background: #f1f5f9;
    }

    @media (max-width: 900px) {
        .table {
            min-width: 1000px;
        }

        .table thead th {
            font-size: 11px;
            padding: 10px 6px;
        }

        .table td {
            font-size: 12px;
            padding: 8px 6px;
        }
    }
</style>

<div class="page-body">
    <div class="container-xl">
        <div class="action-buttons">
            <div class="col-md-12" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <form id="filterForm" method="GET" class="date-filter" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="date" class="form-control" name="tanggal_dari" value="<?= htmlspecialchars($tanggal_dari) ?>" required>
                    <input type="date" class="form-control" name="tanggal_sampai" value="<?= htmlspecialchars($tanggal_sampai) ?>" required>
                    <input type="text" class="form-control" name="nama" placeholder="🔍 Cari berdasarkan nama..." value="<?= isset($_GET['nama']) ? htmlspecialchars($_GET['nama']) : '' ?>" style="min-width:200px;">
                    <input type="hidden" name="status" id="statusFilter" value="<?= htmlspecialchars($status_filter) ?>">

                    <button type="submit" class="btn btn-primary">🔎 Filter</button>
                    <button type="submit" formaction="rekap_sumber_excel.php" class="btn btn-primary export-btn">
                        📊 Export Excel
                    </button>
                </form>
            </div>
        </div>

        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="filter-buttons">
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian.php'">📊 Semua</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_kantor.php'">🏢 Kantor</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_satpam.php'">👮 Satpam</button>
                    <button class="btn btn-primary" onclick="location.href='../presensi/rekap_harian_sumber.php'">💧 Sumber</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_absen.php'">📝 Izin</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_event.php'">🎉 Event</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_all.php'">📈 Rekap</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_izin.php'">📈 Izin/Cuti</button>
                </div>
            </div>
        </div>

        <div class="date-display mb-3">
            <span>📅 Presensi Tanggal: <?= $bulan ?></span>
        </div>

        <div class="filter-buttons mb-3">
            <button class="btn <?= $status_filter == 'all' ? 'btn-primary' : 'btn-secondary' ?> filter-status" data-status="all">📊 Semua Status</button>
            <button class="btn <?= $status_filter == 'belum-presensi' ? 'btn-primary' : 'btn-secondary' ?> filter-status" data-status="belum-presensi">❌ Belum Presensi</button>
            <button class="btn <?= $status_filter == 'izin' ? 'btn-primary' : 'btn-secondary' ?> filter-status" data-status="izin">📝 Izin</button>
            <button class="btn <?= $status_filter == 'hadir' ? 'btn-primary' : 'btn-secondary' ?> filter-status" data-status="hadir">✅ Hadir</button>
            <button class="btn <?= $status_filter == 'terlambat' ? 'btn-primary' : 'btn-secondary' ?> filter-status" data-status="terlambat">⏰ Terlambat</button>
            <button class="btn <?= $status_filter == 'pulang-awal' ? 'btn-primary' : 'btn-secondary' ?> filter-status" data-status="pulang-awal">🏃 Pulang Awal</button>
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-bordered mt-2">
                <thead>
                    <tr class="text-center">
                        <th>Nama</th>
                        <th>Jadwal Jaga</th>
                        <th>Tanggal Masuk/Izin<button class="sort-btn" data-column="2">▲▼</button></th>
                        <th>Jam Masuk/Izin</th>
                        <th>Jam Masuk Kantor</th>
                        <th>Terlambat</th>
                        <th>Tanggal Pulang</th>
                        <th>Jam Pulang</th>
                        <th>Jam Pulang Kantor</th>
                        <th>Pulang Awal</th>
                        <th>Shift Presensi</th>
                        <th>Lokasi</th>
                        <th>Foto Masuk</th>
                        <th>Foto Pulang</th>
                        <th>Ket. Izin</th>
                        <th>Jam Izin</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $filtered_data = [];
                    while ($rekap = $result->fetch_assoc()) {
                        // Logic to determine status for each row
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
                            $rekap['jam_masuk_kantor_calc'] = $jam_masuk_kantor;
                            $rekap['jam_pulang_kantor_calc'] = $jam_pulang_kantor;
                            $rekap['terlambat_seconds'] = $terlambat_seconds;
                            $rekap['pulang_awal_seconds'] = $pulang_awal_seconds;

                            $filtered_data[] = $rekap;
                        }
                    }

                    if (empty($filtered_data)) { ?>
                        <tr>
                            <td colspan="17" class="text-center">Belum ada data</td>
                        </tr>
                        <?php } else {
                        foreach ($filtered_data as $rekap) {
                            $foto_masuk = "/absensi/pegawai/presensi/foto/" . htmlspecialchars($rekap['foto_masuk']);
                            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $foto_masuk)) {
                                $foto_masuk = "/absensi/shift/presensi/foto/" . htmlspecialchars($rekap['foto_masuk']);
                            }
                            $foto_keluar = "/absensi/pegawai/presensi/foto/" . htmlspecialchars($rekap['foto_keluar']);
                            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $foto_keluar)) {
                                $foto_keluar = "/absensi/shift/presensi/foto/" . htmlspecialchars($rekap['foto_keluar']);
                            }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($rekap['nama']) ?></td>
                                <td class="text-center">
                                    <?php
                                    $shift_sumber = $rekap['shift_sumber'] ?? '';
                                    if ($shift_sumber == 'P') {
                                        echo '<span class="jadwal-pagi">🌅 Pagi</span>';
                                    } elseif ($shift_sumber == 'S') {
                                        echo '<span class="jadwal-siang">☀️ Siang</span>';
                                    } elseif ($shift_sumber == 'M') {
                                        echo '<span class="jadwal-malam">🌙 Malam</span>';
                                    } elseif ($shift_sumber == 'F') {
                                        echo '<span class="jadwal-full">⚡ Full</span>';
                                    } elseif ($shift_sumber == 'L') {
                                        echo '<span class="jadwal-libur">🏖️ Libur</span>';
                                    } else {
                                        echo '<span class="jadwal-default">' . htmlspecialchars($shift_sumber) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td style="background-color: #e6ffe6;" class="text-center"><?= !empty($rekap['jadwal_tanggal']) ? date('d F Y', strtotime($rekap['jadwal_tanggal'])) : '<span style="color: red;">Belum presensi</span>' ?></td>
                                <td style="background-color: #e6ffe6;" class="text-center"><?= htmlspecialchars($rekap['jam_masuk'] ?? '-') ?></td>
                                <td style="background-color: #e6ffe6;" class="text-center"><?= htmlspecialchars($rekap['jam_masuk_kantor_calc'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php
                                    if ($rekap['terlambat_seconds'] > 0) {
                                        $jam = floor($rekap['terlambat_seconds'] / 3600);
                                        $menit = floor(($rekap['terlambat_seconds'] % 3600) / 60);
                                        $detik = $rekap['terlambat_seconds'] % 60;
                                        echo '<span style="color: red; font-weight: bold;">' . sprintf('%02d:%02d:%02d', $jam, $menit, $detik) . '</span>';
                                    } elseif (!empty($rekap['jam_masuk'])) {
                                        echo '<span class="badge bg-success">On Time</span>';
                                    } else {
                                        echo '';
                                    }
                                    ?>
                                </td>
                                <td style="background-color: #ffe6e6;"><?= !empty($rekap['tanggal_keluar']) ? date('d F Y', strtotime($rekap['tanggal_keluar'])) : '' ?></td>
                                <td style="background-color: #fff3e6;" class="text-center"><?= htmlspecialchars($rekap['jam_keluar'] ?? '-') ?></td>
                                <td style="background-color: #fff3e6;" class="text-center"><?= htmlspecialchars($rekap['jam_pulang_kantor_calc'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php
                                    if ($rekap['pulang_awal_seconds'] > 0) {
                                        $jam = floor($rekap['pulang_awal_seconds'] / 3600);
                                        $menit = floor(($rekap['pulang_awal_seconds'] % 3600) / 60);
                                        $detik = $rekap['pulang_awal_seconds'] % 60;
                                        echo '<span style="color: red; font-weight: bold;">' . sprintf('%02d:%02d:%02d', $jam, $menit, $detik) . '</span>';
                                    } elseif (!empty($rekap['jam_keluar'])) {
                                        echo '<span class="badge bg-success">On Time</span>';
                                    } else {
                                        echo '';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $shift_presensi = $rekap['shift_presensi'] ?? '';
                                    if ($shift_presensi == 'A') echo 'Pagi';
                                    elseif ($shift_presensi == 'B') echo 'Siang';
                                    elseif ($shift_presensi == 'C') echo 'Pagi';
                                    elseif ($shift_presensi == 'D') echo 'Siang';
                                    elseif ($shift_presensi == 'E') echo 'Malam';
                                    else echo htmlspecialchars($shift_presensi);
                                    ?>
                                </td>
                                <td><?= ($rekap['lokasi_kerja'] ?? '-') ?></td>
                                <td class="foto-column">
                                    <img class="img-fluid" src="<?= $foto_masuk ?>">
                                </td>
                                <td class="foto-column">
                                    <img class="img-fluid" src="<?= $foto_keluar ?>">
                                </td>
                                <td><?= ($rekap['keterangan'] ?? '-') ?></td>
                                <td><?= ($rekap['jam_absen'] ?? '-') ?></td>
                                <td class="text-center">
                                    <a href="/absensi/admin/presensi/rekap.php?id=<?= htmlspecialchars($rekap['pegawai_id']) ?>" class="badge badge-pill bg-primary">Rekap</a>
                                    <a href="/absensi/admin/presensi/edit.php?id=<?= htmlspecialchars($rekap['presensi_id']) ?>" class="badge badge-pill bg-secondary">Edit</a>
                                    <a href="/absensi/admin/presensi/hapus.php?id=<?= htmlspecialchars($rekap['presensi_id']) ?>" class="badge badge-pill bg-danger tombol-hapus">Hapus</a>
                                </td>
                            </tr>
                    <?php }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include('../layout/footer.php');
ob_end_flush();
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusButtons = document.querySelectorAll('.filter-status');
        const statusInput = document.getElementById('statusFilter');
        const filterForm = document.getElementById('filterForm');

        statusButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const status = button.dataset.status;
                statusInput.value = status;
                filterForm.submit();
            });
        });

        const currentStatus = statusInput.value;
        const activeBtn = document.querySelector(`.filter-status[data-status="${currentStatus}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('btn-secondary');
            activeBtn.classList.add('btn-primary');
        }

        const buttons = document.querySelectorAll('.sort-btn');
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const column = parseInt(button.dataset.column);
                const order = button.dataset.order === 'asc' ? 'desc' : 'asc';
                sortTable(column, order);
                button.dataset.order = order;

                button.textContent = order === 'asc' ? '▲' : '▼';
            });
        });

        function sortTable(column, order) {
            const table = document.querySelector('table tbody');
            const rows = Array.from(table.querySelectorAll('tr'));

            if (rows.length === 0) return;
            const sortedRows = rows.sort((a, b) => {
                const aCells = a.querySelectorAll('td');
                const bCells = b.querySelectorAll('td');

                if (!aCells[column] || !bCells[column]) return 0;

                let aValue = aCells[column].textContent.trim();
                let bValue = bCells[column].textContent.trim();

                if (aValue.includes('Belum presensi')) aValue = '';
                if (bValue.includes('Belum presensi')) bValue = '';

                if (column === 2) {
                    if (aValue === '' && bValue === '') return 0;
                    if (aValue === '') return order === 'asc' ? 1 : -1;
                    if (bValue === '') return order === 'asc' ? -1 : 1;
                    const aDate = parseIndonesianDate(aValue);
                    const bDate = parseIndonesianDate(bValue);
                    return order === 'asc' ? aDate - bDate : bDate - aDate;
                }

                if (order === 'asc') {
                    return aValue.localeCompare(bValue);
                } else {
                    return bValue.localeCompare(aValue);
                }
            });

            table.innerHTML = '';
            sortedRows.forEach(row => table.appendChild(row));
        }

        function parseIndonesianDate(dateStr) {
            const months = {
                'Januari': 0, 'Februari': 1, 'Maret': 2, 'April': 3, 'Mei': 4, 'Juni': 5, 'Juli': 6, 'Agustus': 7, 'September': 8, 'Oktober': 9, 'November': 10, 'Desember': 11
            };
            const parts = dateStr.split(' ');
            if (parts.length !== 3) return new Date(0);
            const day = parseInt(parts[0]);
            const month = months[parts[1]];
            const year = parseInt(parts[2]);
            if (isNaN(day) || month === undefined || isNaN(year)) return new Date(0);
            return new Date(year, month, day);
        }
    });
</script>