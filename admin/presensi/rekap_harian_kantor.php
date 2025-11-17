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

$judul = "Presensi Kantor";
include('../layout/header.php');
include_once('../../config.php');

// Filter pencarian berdasarkan nama jika parameter pencarian diberikan
$nama_condition = isset($_GET['nama']) ? "AND pegawai.nama LIKE ?" : "";

if (empty($_GET["tanggal_dari"])) {
    $tanggal_hari_ini = date('Y-m-d');
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
                    AND presensi.tanggal_masuk = ?
                    LEFT JOIN users ON users.id_pegawai = pegawai.id 
                WHERE
                    users.role != 'admin'
                    AND users.status = 'aktif'
                    AND pegawai.lokasi_presensi = 'Kantor PDAM'
                    $nama_condition
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
                    pegawai.id ASC, 
                    presensi.tanggal_masuk ASC, 
                    presensi.jam_masuk ASC;";
    $stmt = $connection->prepare($query);
    if (isset($_GET['nama'])) {
        $nama = "%" . $_GET['nama'] . "%";
        $stmt->bind_param("ss", $tanggal_hari_ini, $nama);
    } else {
        $stmt->bind_param("s", $tanggal_hari_ini);
    }
} else {
    $tanggal_dari = $_GET["tanggal_dari"];
    $tanggal_sampai = $_GET["tanggal_sampai"];
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
                    AND pegawai.lokasi_presensi = 'Kantor PDAM'
                    $nama_condition
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
                    pegawai.id ASC, 
                    presensi.tanggal_masuk ASC, 
                    presensi.jam_masuk ASC;";
    $stmt = $connection->prepare($query);
    if (isset($_GET['nama'])) {
        $nama = "%" . $_GET['nama'] . "%";
        $stmt->bind_param("sss", $tanggal_dari, $tanggal_sampai, $nama);
    } else {
        $stmt->bind_param("ss", $tanggal_dari, $tanggal_sampai);
    }
}
$stmt->execute();
$result = $stmt->get_result();

$bulan = empty($_GET['tanggal_dari']) ? date('Y-m-d') : $_GET['tanggal_dari'] . '-' . $_GET['tanggal_sampai'];
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
        <!-- Bagian Export dan Filter Tanggal & Nama -->
        <div class="action-buttons">
            <div class="col-md-2">
                <button type="button" class="btn btn-primary export-btn" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    📊 Export Excel
                </button>
            </div>
            <div class="col-md-10">
                <form method="GET" class="date-filter" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="date" class="form-control" name="tanggal_dari" value="<?= isset($_GET['tanggal_dari']) ? htmlspecialchars($_GET['tanggal_dari']) : '' ?>" required>
                    <input type="date" class="form-control" name="tanggal_sampai" value="<?= isset($_GET['tanggal_sampai']) ? htmlspecialchars($_GET['tanggal_sampai']) : '' ?>" required>
                    <input type="text" class="form-control" name="nama" placeholder="🔍 Cari berdasarkan nama..." value="<?= isset($_GET['nama']) ? htmlspecialchars($_GET['nama']) : '' ?>" style="min-width:200px;">
                    <button type="submit" class="btn btn-primary">🔎 Filter</button>
                </form>
            </div>
        </div>

        <!-- Bagian Filter (button shortcut) -->
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="filter-buttons">
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian.php'">📊 Semua</button>
                    <button class="btn btn-primary" onclick="location.href='../presensi/rekap_harian_kantor.php'">🏢 Kantor</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_satpam.php'">👮 Satpam</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_sumber.php'">💧 Sumber</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_absen.php'">📝 Izin</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_event.php'">🎉 Event</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_all.php'">📈 Rekap</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_izin.php'">📈 Izin/Cuti</button>
                </div>
            </div>
        </div>

        <!-- Tampilan tanggal -->
        <div class="date-display mb-3">
            <?php if (empty($_GET['tanggal_dari'])) : ?>
                <span>📅 Presensi Tanggal: <?= date('d F Y') ?></span>
            <?php else : ?>
                <span>📅 Presensi Tanggal: <?= date('d F Y', strtotime($_GET['tanggal_dari'])) . ' sampai ' . date('d F Y', strtotime($_GET['tanggal_sampai'])) ?></span>
            <?php endif; ?>
        </div>

        <!-- Filter Status Presensi -->
        <div class="filter-buttons mb-3">
            <button class="btn btn-secondary filter-status" data-status="all">📊 Semua Status</button>
            <button class="btn btn-secondary filter-status" data-status="belum-presensi">❌ Belum Presensi</button>
            <button class="btn btn-secondary filter-status" data-status="izin">📝 Izin</button>
            <button class="btn btn-secondary filter-status" data-status="hadir">✅ Hadir</button>
            <button class="btn btn-secondary filter-status" data-status="terlambat">⏰ Terlambat</button>
            <button class="btn btn-secondary filter-status" data-status="pulang-awal">🏃 Pulang Awal</button>
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-bordered mt-2">
                <thead>
                    <tr class="text-center">
                        <th>Nama</th>
                        <th>Tanggal Masuk/Izin <button class="sort-btn" data-column="1">▲▼</button></th>
                        <th>Jam Masuk/Izin</th>
                        <th>Jam Masuk Kantor</th>
                        <th>Terlambat</th>
                        <!-- <th>Total Terlambat</th> -->
                        <th>Tanggal Pulang</th>
                        <th>Jam Pulang</th>
                        <th>Jam Pulang Kantor</th>
                        <th>Pulang Awal</th>
                        <!-- <th>Total Pulang Awal</th> -->
                        <th>Lokasi</th>
                        <th>Foto Masuk</th>
                        <th>Foto Pulang</th>
                        <th>Ket. Izin</th>
                        <th>Jam Izin</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0) { ?>
                        <tr>
                            <td colspan="13" class="text-center">Belum ada data</td>
                        </tr>
                        <?php } else {
                        while ($rekap = $result->fetch_assoc()) :
                            // Calculate total work hours
                            $jam_tanggal_masuk = date('Y-m-d H:i:s', strtotime($rekap['tanggal_masuk'] . ' ' . $rekap['jam_masuk']));
                            $jam_tanggal_keluar = date('Y-m-d H:i:s', strtotime($rekap['tanggal_keluar'] . ' ' . $rekap['jam_keluar']));

                            $timestamp_masuk = strtotime($jam_tanggal_masuk);
                            $timestamp_keluar = strtotime($jam_tanggal_keluar);

                            $selisih = $timestamp_keluar - $timestamp_masuk;
                            $total_jam_kerja = floor($selisih / 3600);
                            $selisih -= $total_jam_kerja * 3600;
                            $selisih_menit_kerja = floor($selisih / 60);

                            if ($rekap['role'] == 'pegawai') {
                                // Calculate total late hours
                                $jam_presensi = $rekap['lokasi_presensi'];
                                $jam_query = "SELECT * FROM jam_kerja WHERE id = 1";
                                $jam_stmt = $connection->prepare($jam_query);
                                $jam_stmt->bind_param("s", $jam_presensi);
                                $jam_stmt->execute();
                                $jam_result = $jam_stmt->get_result()->fetch_assoc();

                                // Extract day of the week from tanggal_masuk
                                $shift = date('N', strtotime($rekap['tanggal_masuk']));

                                // $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk']));

                                if ($shift == 1) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_senin']));
                                } elseif ($shift == 2) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_selasa']));
                                } elseif ($shift == 3) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_rabu']));
                                } elseif ($shift == 4) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_kamis']));
                                } elseif ($shift == 5) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_jumat']));
                                } else {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_sabtu']));
                                }

                                $jam_masuk = date('H:i:s', strtotime($rekap['jam_masuk']));
                                $timestamp_jam_masuk_real = strtotime($jam_masuk);
                                $timestamp_jam_masuk_kantor = strtotime($jam_masuk_kantor);

                                $terlambat = $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor;
                                $total_jam_terlambat = floor($terlambat / 3600);
                                $terlambat -= $total_jam_terlambat * 3600;
                                $selisih_menit_terlambat = floor($terlambat / 60);
                                // Accumulate the lateness to the total
                                $total_terlambat += $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor;

                                // Extract day of the week from tanggal_keluar
                                $current_day_pulang = date('N', strtotime($rekap['tanggal_keluar']));

                                // Set the office end time
                                if ($current_day_pulang == 1) {
                                    $jam_pulang_kantor = strtotime($jam_result['jam_pulang_senin']);
                                } elseif ($current_day_pulang == 2) {
                                    $jam_pulang_kantor = strtotime($jam_result['jam_pulang_selasa']);
                                } elseif ($current_day_pulang == 3) {
                                    $jam_pulang_kantor = strtotime($jam_result['jam_pulang_rabu']);
                                } elseif ($current_day_pulang == 4) {
                                    $jam_pulang_kantor = strtotime($jam_result['jam_pulang_kamis']);
                                } elseif ($current_day_pulang == 5) {
                                    $jam_pulang_kantor = strtotime($jam_result['jam_pulang_jumat']);
                                } else {
                                    $jam_pulang_kantor = strtotime($jam_result['jam_pulang_sabtu']);
                                }

                                // Calculate early departure
                                $jam_pulang = date('H:i:s', strtotime($rekap['jam_keluar']));
                                $timestamp_jam_pulang_real = strtotime($jam_pulang);
                                $timestamp_jam_pulang_kantor = ($jam_pulang_kantor);


                                // Adjust for overnight shifts
                                if (strtotime($rekap['tanggal_keluar']) > strtotime($rekap['tanggal_masuk'])) {
                                    $timestamp_jam_pulang_real += 24 * 3600; // Add 24 hours
                                }

                                $awal = $timestamp_jam_pulang_kantor - $timestamp_jam_pulang_real;
                                $total_jam_awal = floor(($timestamp_jam_pulang_kantor - $timestamp_jam_pulang_real) / 3600);
                                $awal -= $total_jam_awal * 3600;
                                $selisih_menit_awal = floor($awal / 60);
                                // Accumulate the early to the total
                                // Accumulate the early time to the total only if $timestamp_jam_pulang_real is positive
                                if (!empty($rekap['tanggal_keluar']) && $timestamp_jam_pulang_real > 0) {
                                    $total_awal += max(0, $timestamp_jam_pulang_kantor - $timestamp_jam_pulang_real);
                                }
                            } elseif ($rekap['role'] == 'sumber' || $rekap['role'] == 'tidar') {
                                // Calculate total late hours
                                $jam_presensi = $rekap['lokasi_presensi'];
                                $shift = $rekap['shift'];
                                $jam_query = "SELECT * FROM shift WHERE id = 1";
                                $jam_stmt = $connection->prepare($jam_query);
                                $jam_stmt->bind_param("s", $jam_presensi);
                                $jam_stmt->execute();
                                $jam_result = $jam_stmt->get_result()->fetch_assoc();

                                if ($shift == 'A') {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['masuk_a']));
                                } elseif ($shift == 'B') {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['masuk_b']));
                                } elseif ($shift == 'C') {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['masuk_c']));
                                } elseif ($shift == 'D') {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['masuk_d']));
                                } else {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['masuk_e']));
                                }

                                $jam_masuk = date('H:i:s', strtotime($rekap['jam_masuk']));
                                $timestamp_jam_masuk_real = strtotime($jam_masuk);
                                $timestamp_jam_masuk_kantor = strtotime($jam_masuk_kantor);

                                $terlambat = $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor;
                                $total_jam_terlambat = floor($terlambat / 3600);
                                $terlambat -= $total_jam_terlambat * 3600;
                                $selisih_menit_terlambat = floor($terlambat / 60);
                                // Accumulate the lateness to the total
                                $total_terlambat += $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor;

                                // Set the office end time
                                if ($shift == 'A') {
                                    $jam_pulang_kantor = date('H:i:s', strtotime($jam_result['pulang_a']));
                                } elseif ($shift == 'B') {
                                    $jam_pulang_kantor = date('H:i:s', strtotime($jam_result['pulang_b']));
                                } elseif ($shift == 'C') {
                                    $jam_pulang_kantor = date('H:i:s', strtotime($jam_result['pulang_c']));
                                } elseif ($shift == 'D') {
                                    $jam_pulang_kantor = date('H:i:s', strtotime($jam_result['pulang_d']));
                                } else {
                                    $jam_pulang_kantor = date('H:i:s', strtotime($jam_result['pulang_e']));
                                }

                                // Calculate early departure
                                $jam_pulang = date('H:i:s', strtotime($rekap['jam_keluar']));
                                $timestamp_jam_pulang_real = strtotime($jam_pulang);
                                $timestamp_jam_pulang_kantor = strtotime($jam_pulang_kantor);

                                // Adjust for overnight shifts
                                if (strtotime($rekap['tanggal_keluar']) > strtotime($rekap['tanggal_masuk'])) {
                                    $timestamp_jam_pulang_real += 24 * 3600; // Add 24 hours
                                }

                                $awal = $timestamp_jam_pulang_kantor - $timestamp_jam_pulang_real;
                                $total_jam_awal = floor(($timestamp_jam_pulang_kantor - $timestamp_jam_pulang_real) / 3600);
                                $awal -= $total_jam_awal * 3600;
                                $selisih_menit_awal = floor($awal / 60);
                                // Accumulate the early to the total
                                // Accumulate the early time to the total only if $timestamp_jam_pulang_real is positive
                                if (!empty($rekap['tanggal_keluar']) && $timestamp_jam_pulang_real > 0) {
                                    $total_awal += max(0, $timestamp_jam_pulang_kantor - $timestamp_jam_pulang_real);
                                }
                            }

                            // Determine the photo paths
                            $foto_masuk = "/absensi/pegawai/presensi/foto/" . htmlspecialchars($rekap['foto_masuk']);
                            $foto_masuk_path = "/var/www/html/absensi/pegawai/presensi/foto/" . htmlspecialchars($rekap['foto_masuk']);
                            if (!file_exists($foto_masuk_path)) {
                                $foto_masuk = "/absensi/shift/presensi/foto/" . htmlspecialchars($rekap['foto_masuk']);
                            }

                            $foto_keluar = "/absensi/pegawai/presensi/foto/" . htmlspecialchars($rekap['foto_keluar']);
                            $foto_keluar_path = "/var/www/html/absensi/pegawai/presensi/foto/" . htmlspecialchars($rekap['foto_keluar']);
                            if (!file_exists($foto_keluar_path)) {
                                $foto_keluar = "/absensi/shift/presensi/foto/" . htmlspecialchars($rekap['foto_keluar']);
                            }
                        ?>
                            <tr style="<?= $rekap['lokasi_presensi'] == 'Kantor PDAM' ? 'background-color: #add8e6;' : 'background-color: #d1e7dd;'; ?>">
                                <td style="background-color: #f0f8ff;"><?= htmlspecialchars($rekap['nama']) ?></td>
                                <td style="background-color: #e6ffe6;" class="text-center"><?= !empty($rekap['tanggal_masuk']) ? date('d F Y', strtotime($rekap['tanggal_masuk'])) : '<span style="color: red;">Belum presensi</span>' ?></td>
                                <td style="background-color: #e6ffe6;" class="text-center"><?= htmlspecialchars($rekap['jam_masuk']) ?></td>
                                <td style="background-color: #e6ffe6;" class="text-center"><?= htmlspecialchars($rekap['jam_masuk_kantor']) ?></td>
                                <td class="text-center">
                                    <?php
                                    if (!empty($rekap['jam_masuk']) && isset($rekap['jam_masuk_kantor'])) {
                                        $terlambat_seconds = strtotime($rekap['jam_masuk']) - strtotime($rekap['jam_masuk_kantor']);
                                        if ($terlambat_seconds > 0) {
                                            $jam = floor($terlambat_seconds / 3600);
                                            $menit = floor(($terlambat_seconds % 3600) / 60);
                                            $detik = $terlambat_seconds % 60;
                                            echo '<span style="color: red; font-weight: bold;">' . sprintf('%02d:%02d:%02d', $jam, $menit, $detik) . '</span>';
                                        } else {
                                            echo '<span class="badge bg-success">On Time</span>';
                                        }
                                    } else {
                                        echo '';
                                    }
                                    ?>
                                </td>
                                <td style="background-color: #ffe6e6;"><?= !empty($rekap['tanggal_keluar']) ? date('d F Y', strtotime($rekap['tanggal_keluar'])) : '' ?></td>
                                <td style="background-color: #fff3e6;" class="text-center"><?= htmlspecialchars($rekap['jam_keluar']) ?></td>
                                <td style="background-color: #fff3e6;" class="text-center"><?= htmlspecialchars($rekap['jam_pulang_kantor']) ?></td>
                                <!-- <td class="text-center">
                                <?= !empty($rekap['tanggal_keluar']) ? ($total_jam_awal < 0 ? '<span class="badge bg-success">On Time</span>' : '<span style="color: red; font-weight: bold;">' . $total_jam_awal . ' Jam ' . $selisih_menit_awal . ' Menit</span>') : '' ?>
                            </td> -->
                                <td class="text-center">
                                    <?php
                                    if (!empty($rekap['jam_pulang_kantor']) && isset($rekap['jam_keluar'])) {
                                        $awal_seconds = strtotime($rekap['jam_pulang_kantor']) - strtotime($rekap['jam_keluar']);
                                        if ($awal_seconds > 0) {
                                            $jam = floor($awal_seconds / 3600);
                                            $menit = floor(($awal_seconds % 3600) / 60);
                                            $detik = $awal_seconds % 60;
                                            echo '<span style="color: red; font-weight: bold;">' . sprintf('%02d:%02d:%02d', $jam, $menit, $detik) . '</span>';
                                        } else {
                                            echo '<span class="badge bg-success">On Time</span>';
                                        }
                                    } else {
                                        echo '';
                                    }
                                    ?>
                                </td>
                                <td><?= ($rekap['lokasi_presensi']) ?></td>
                                <td class="foto-column">
                                    <img class="img-fluid" src="<?= $foto_masuk ?>">
                                </td>
                                <td class="foto-column">
                                    <img class="img-fluid" src="<?= $foto_keluar ?>">
                                </td>
                                <td><?= ($rekap['keterangan']) ?></td>
                                <td><?= ($rekap['jam_absen']) ?></td>
                                <td class=" text-center">
                                    <a href="/absensi/admin/presensi/rekap.php?id=<?= htmlspecialchars($rekap['pegawai_id']) ?>" class="badge badge-pill bg-primary">Rekap</a>
                                    <a href="/absensi/admin/presensi/edit.php?id=<?= htmlspecialchars($rekap['id']) ?>" class="badge badge-pill bg-secondary">Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal" id="exampleModal" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ekspor Excel Rekap Harian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="rekap_harian_excel.php" method="GET">
                    <div class="mb-3">
                        <label for="tanggal_dari" class="form-label">Tanggal Dari</label>
                        <input type="date" class="form-control" id="tanggal_dari" name="tanggal_dari" required>
                    </div>
                    <div class="mb-3">
                        <label for="tanggal_sampai" class="form-label">Tanggal Sampai</label>
                        <input type="date" class="form-control" id="tanggal_sampai" name="tanggal_sampai" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Export</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include('../layout/footer.php');
ob_end_flush();
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.sort-btn');
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const column = parseInt(button.dataset.column);
                const order = button.dataset.order === 'asc' ? 'desc' : 'asc';
                sortTable(column, order);
                button.dataset.order = order;

                // Update button visual indicator
                button.textContent = order === 'asc' ? '▲' : '▼';
            });
        });

        // Filter functionality
        const filterButtons = document.querySelectorAll('.filter-status');
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('btn-primary'));
                filterButtons.forEach(btn => btn.classList.add('btn-secondary'));

                // Add active class to clicked button
                button.classList.remove('btn-secondary');
                button.classList.add('btn-primary');

                const status = button.dataset.status;
                filterTable(status);
            });
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

            // Handle "Belum presensi" case
            if (aValue.includes('Belum presensi')) aValue = '';
            if (bValue.includes('Belum presensi')) bValue = '';

            // For date column (column 1), parse dates properly
            if (column === 1) {
                if (aValue === '' && bValue === '') return 0;
                if (aValue === '') return order === 'asc' ? 1 : -1;
                if (bValue === '') return order === 'asc' ? -1 : 1;

                // Parse Indonesian date format (dd Month yyyy)
                const aDate = parseIndonesianDate(aValue);
                const bDate = parseIndonesianDate(bValue);

                if (order === 'asc') {
                    return aDate - bDate;
                } else {
                    return bDate - aDate;
                }
            }

            // For other columns, use string comparison
            if (order === 'asc') {
                return aValue.localeCompare(bValue);
            } else {
                return bValue.localeCompare(aValue);
            }
        });

        // Clear and re-append sorted rows
        table.innerHTML = '';
        sortedRows.forEach(row => table.appendChild(row));
    }

    function parseIndonesianDate(dateStr) {
        const months = {
            'January': 0,
            'February': 1,
            'March': 2,
            'April': 3,
            'May': 4,
            'June': 5,
            'July': 6,
            'August': 7,
            'September': 8,
            'October': 9,
            'November': 10,
            'December': 11,
            'Januari': 0,
            'Februari': 1,
            'Maret': 2,
            'April': 3,
            'Mei': 4,
            'Juni': 5,
            'Juli': 6,
            'Agustus': 7,
            'September': 8,
            'Oktober': 9,
            'November': 10,
            'Desember': 11
        };

        const parts = dateStr.split(' ');
        if (parts.length !== 3) return new Date(0);

        const day = parseInt(parts[0]);
        const month = months[parts[1]];
        const year = parseInt(parts[2]);

        if (isNaN(day) || month === undefined || isNaN(year)) return new Date(0);

        return new Date(year, month, day);
    }

    function filterTable(status) {
        const table = document.querySelector('table tbody');
        const rows = Array.from(table.querySelectorAll('tr'));

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 0) return;

            const tanggalMasukCell = cells[1]; // Tanggal Masuk/Izin column
            const keteranganCell = cells[10]; // Ket. Izin column

            let shouldShow = false;

            switch (status) {
                case 'all':
                    shouldShow = true;
                    break;
                case 'belum-presensi':
                    shouldShow = tanggalMasukCell.textContent.includes('Belum presensi');
                    break;
                case 'izin':
                    shouldShow = keteranganCell.textContent.trim() !== '' &&
                        !tanggalMasukCell.textContent.includes('Belum presensi');
                    break;
                case 'hadir':
                    shouldShow = !tanggalMasukCell.textContent.includes('Belum presensi') &&
                        keteranganCell.textContent.trim() === '';
                    break;
                case 'terlambat':
                    const terlambatCell = cells[4];
                    shouldShow = terlambatCell.querySelector('span[style*="color: red"]') !== null;
                    break;
                case 'pulang-awal':
                    const pulangAwalCell = cells[8];
                    shouldShow = pulangAwalCell.querySelector('span[style*="color: red"]') !== null;
                    break;
            }

            row.style.display = shouldShow ? '' : 'none';
        });
    }
</script>