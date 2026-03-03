<?php
ob_start();
session_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
    exit;
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
    exit;
}

$judul = "Rekap Presensi";
include('../layout/header.php');
include_once('../../config.php');

$id = $_GET["id"];
$bulan_sekarang = date("Y-m");
$filter_bulan = $_GET["filter_bulan"] ?? date("m");
$filter_tahun = $_GET["filter_tahun"] ?? date("Y");
$bulan = $filter_tahun . '-' . $filter_bulan;

$query = "SELECT presensi.*, pegawai.nama, pegawai.lokasi_presensi, users.role 
    FROM presensi 
    JOIN pegawai ON presensi.id_pegawai = pegawai.id
    JOIN users on users.id_pegawai = pegawai.id 
    WHERE pegawai.id = ? AND DATE_FORMAT(tanggal_masuk, '%Y-%m') = ?
    ORDER BY tanggal_masuk DESC";

$stmt = $connection->prepare($query);
$stmt->bind_param("is", $id, $bulan);
$stmt->execute();
$result = $stmt->get_result();

$total_terlambat = 0;
$total_awal = 0;
$total_kerja = 0;
?>

<style>
    /* Variabel warna - Flat Design */
    :root {
        --primary-color: #2563eb;
        --primary-hover: #1d4ed8;
        --secondary-color: #6b7280;
        --success-color: #10b981;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --info-color: #3b82f6;
        --light-color: #f9fafb;
        --dark-color: #111827;
        --text-dark: #1f2937;
        --text-light: #6b7280;
        --border-color: #e5e7eb;
        --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Styling untuk tombol */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-weight: 500;
        font-size: 0.875rem;
        line-height: 1.25rem;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
        border: 1px solid transparent;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background-color: var(--primary-hover);
    }

    .btn-outline-primary {
        background-color: transparent;
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        color: white;
    }

    /* Styling untuk form controls */
    .form-control {
        padding: 0.5rem 0.75rem;
        border-radius: 0.375rem;
        border: 1px solid var(--border-color);
        font-size: 0.875rem;
        line-height: 1.25rem;
        transition: all 0.2s ease-in-out;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }

    /* Styling untuk tabel */
    .table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
        font-size: 0.875rem;
    }

    .table th,
    .table td {
        padding: 0.75rem;
        text-align: left;
        border: 1px solid var(--border-color);
    }

    .table thead th {
        background-color: #f3f4f6;
        font-weight: 600;
        color: var(--text-dark);
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
    }

    .table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    .table tbody tr:hover {
        background-color: #f3f4f6;
    }

    /* Responsive table container */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
    }

    /* Styling untuk action buttons */
    .action-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    /* Styling untuk filter section */
    .filter-section {
        background-color: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
    }

    .filter-section h5 {
        margin-top: 0;
        margin-bottom: 1rem;
        color: var(--text-dark);
        font-size: 1.125rem;
        font-weight: 600;
    }

    /* Styling untuk status badges */
    .badge {
        display: inline-block;
        padding: 0.25em 0.6em;
        font-size: 75%;
        font-weight: 600;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }

    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .badge-warning {
        background-color: #fef3c7;
        color: #92400e;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* Styling untuk modal */
    .modal-content {
        border: none;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .modal-header {
        border-bottom: 1px solid var(--border-color);
        padding: 1.25rem 1.5rem;
    }

    .modal-title {
        font-weight: 600;
        color: var(--text-dark);
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        border-top: 1px solid var(--border-color);
        padding: 1.25rem 1.5rem;
    }

    /* Styling untuk form groups */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--text-dark);
    }

    /* Styling untuk select2 */
    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid var(--border-color);
        border-radius: 0.375rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    /* Styling untuk datepicker */
    .datepicker {
        border-radius: 0.375rem;
        border: 1px solid var(--border-color);
        padding: 0.5rem 0.75rem;
        width: 100%;
    }

    /* Styling untuk card */
    .card {
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
        border: 1px solid var(--border-color);
    }

    .card-header {
        padding: 1rem 1.5rem;
        background-color: #f9fafb;
        border-bottom: 1px solid var(--border-color);
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Styling untuk tabs */
    .nav-tabs {
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 1rem;
    }

    .nav-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: 0.375rem;
        border-top-right-radius: 0.375rem;
        padding: 0.5rem 1rem;
        color: var(--text-light);
        font-weight: 500;
        margin-right: 0.25rem;
    }

    .nav-tabs .nav-link.active {
        color: var(--primary-color);
        background-color: white;
        border-color: var(--border-color);
        border-bottom-color: white;
    }

    /* Utility classes */
    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .mb-3 {
        margin-bottom: 1rem;
    }

    .mb-4 {
        margin-bottom: 1.5rem;
    }

    .mt-3 {
        margin-top: 1rem;
    }

    .mt-4 {
        margin-top: 1.5rem;
    }

    .d-flex {
        display: flex;
    }

    .justify-content-between {
        justify-content: space-between;
    }

    .align-items-center {
        align-items: center;
    }

    /* Styling untuk tombol sort */
    .sort-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0 0.25rem;
        margin-left: 0.25rem;
        color: var(--text-light);
    }

    .sort-btn:hover {
        color: var(--primary-color);
    }

    /* Styling untuk status presensi */
    .status-presensi {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: capitalize;
    }

    .status-hadir {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-terlambat {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-tidak-hadir {
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* Styling untuk foto presensi */
    .foto-presensi {
        width: 100%;
        height: auto;
        border-radius: 0.25rem;
        border: 1px solid var(--border-color);
    }

    /* Styling untuk status filter */
    .status-filter {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .status-filter-btn {
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid var(--border-color);
        background-color: white;
        color: var(--text-light);
        transition: all 0.2s ease-in-out;
    }

    .status-filter-btn:hover,
    .status-filter-btn.active {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    /* Styling untuk mobile */
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
            align-items: flex-start;
        }

        .table-responsive {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
        }

        .table {
            min-width: 100%;
        }
    }

    /* Styling untuk tooltip */
    [data-bs-toggle="tooltip"] {
        cursor: pointer;
    }

    /* Styling untuk pagination */
    .pagination {
        display: flex;
        padding-left: 0;
        list-style: none;
        border-radius: 0.25rem;
        margin: 1rem 0;
    }

    .page-item:first-child .page-link {
        border-top-left-radius: 0.25rem;
        border-bottom-left-radius: 0.25rem;
    }

    .page-item:last-child .page-link {
        border-top-right-radius: 0.25rem;
        border-bottom-right-radius: 0.25rem;
    }

    .page-link {
        position: relative;
        display: block;
        padding: 0.5rem 0.75rem;
        margin-left: -1px;
        line-height: 1.25;
        color: var(--primary-color);
        background-color: white;
        border: 1px solid var(--border-color);
        text-decoration: none;
    }

    .page-link:hover {
        background-color: #f3f4f6;
        border-color: var(--border-color);
    }

    .page-item.active .page-link {
        z-index: 3;
        color: white;
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .page-item.disabled .page-link {
        color: #9ca3af;
        pointer-events: none;
        background-color: white;
        border-color: var(--border-color);
    }
</style>

<div class="page-body">
    <div class="container-xl">
        <!-- Bagian Filter Tanggal -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filter Rekap Presensi</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                    <div class="col-md-3">
                        <label class="form-label">Bulan</label>
                        <select name="filter_bulan" class="form-select">
                            <option value="">-- Pilih Bulan --</option>
                            <?php for ($m = 1; $m <= 12; $m++) : ?>
                                <option value="<?= str_pad($m, 2, "0", STR_PAD_LEFT) ?>" <?= $m == $filter_bulan ? 'selected' : '' ?>>
                                    <?= date("F", mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tahun</label>
                        <select name="filter_tahun" class="form-select">
                            <option value="">-- Pilih Tahun --</option>
                            <?php for ($y = date("Y"); $y <= date("Y") + 6; $y++) : ?>
                                <option value="<?= $y ?>" <?= $y == $filter_tahun ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Informasi Rekap -->
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <div>
                    <strong>Rekap Presensi Bulan:</strong> <?= date('F Y', strtotime($bulan)) ?>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">No</th>
                        <th>Nama</th>
                        <th class="text-center">Tanggal Masuk/Izin <button class="sort-btn" data-column="1">▲▼</button></th>
                        <th class="text-center">Jam Masuk</th>
                        <th class="text-center">Jam Masuk Kantor</th>
                        <th class="text-center">Keterlambatan</th>
                        <th class="text-center">Tanggal Pulang</th>
                        <th class="text-center">Jam Pulang</th>
                        <th class="text-center">Jam Pulang Kantor</th>
                        <th class="text-center">Pulang Awal</th>
                        <th class="text-center">Jam Kerja</th>
                        <th class="text-center">Lokasi</th>
                        <th class="text-center">Shift</th>
                        <th class="text-center">Foto Masuk</th>
                        <th class="text-center">Foto Pulang</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if ($result->num_rows === 0) {
                    ?>
                        <tr>
                            <td colspan="15" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="mb-0">Tidak ada data presensi untuk ditampilkan</p>
                                </div>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php while ($rekap = $result->fetch_assoc()) {
                            // $tanggal_keluar = !empty($rekap['tanggal_keluar']) ? $rekap['tanggal_keluar'] : date('Y-m-d');
                            // $jam_keluar = !empty($rekap['jam_keluar']) ? $rekap['jam_keluar'] : date('H:i:s');

                            // $timestamp_masuk = strtotime($rekap['tanggal_masuk'] . ' ' . $rekap['jam_masuk']);
                            // $timestamp_keluar = strtotime($tanggal_keluar . ' ' . $jam_keluar);
                            // $selisih = $timestamp_keluar - $timestamp_masuk;

                            // $total_jam_kerja = floor($selisih / 3600);
                            // $selisih -= $total_jam_kerja * 3600;
                            // $selisih_menit_kerja = floor($selisih / 60);

                            // menghitung total jam kerja
                            $timestamp_masuk = strtotime($rekap['tanggal_masuk'] . ' ' . $rekap['jam_masuk']);
                            $timestamp_keluar = strtotime($rekap['tanggal_keluar'] . ' ' . $rekap['jam_keluar']);

                            if ($timestamp_masuk && $timestamp_keluar) {
                                $selisih = $timestamp_keluar - $timestamp_masuk;
                                $total_jam_kerja = floor($selisih / 3600);
                                $selisih_rem = $selisih % 3600;
                                $selisih_menit_kerja = floor($selisih_rem / 60);
                                $selisih_detik_kerja = $selisih_rem % 60;
                                $total_kerja += max(0, $selisih);
                            } else {
                                $total_jam_kerja = 0;
                                $selisih_menit_kerja = 0;
                                $selisih_detik_kerja = 0;
                            }


                            if ($rekap['role'] == 'pegawai') {
                                // Calculate total late hours
                                $jam_presensi = $rekap['lokasi_presensi'];
                                $jam_query = "SELECT * FROM jam_kerja WHERE id = 1";
                                $jam_stmt = $connection->prepare($jam_query);
                                $jam_stmt->execute();
                                $jam_result = $jam_stmt->get_result()->fetch_assoc();

                                // Extract day of the week from tanggal_masuk
                                $shift = date('N', strtotime($rekap['tanggal_masuk']));

                                if ($shift == 1) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_senin']));
                                    $jam_pulang_kantor_str = $jam_result['jam_pulang_senin'];
                                } elseif ($shift == 2) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_selasa']));
                                    $jam_pulang_kantor_str = $jam_result['jam_pulang_selasa'];
                                } elseif ($shift == 3) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_rabu']));
                                    $jam_pulang_kantor_str = $jam_result['jam_pulang_rabu'];
                                } elseif ($shift == 4) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_kamis']));
                                    $jam_pulang_kantor_str = $jam_result['jam_pulang_kamis'];
                                } elseif ($shift == 5) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_jumat']));
                                    $jam_pulang_kantor_str = $jam_result['jam_pulang_jumat'];
                                } else {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['jam_masuk_sabtu']));
                                    $jam_pulang_kantor_str = $jam_result['jam_pulang_sabtu'];
                                }

                                $rekap['jam_masuk_kantor'] = $jam_masuk_kantor;
                                $rekap['jam_pulang_kantor'] = $jam_pulang_kantor_str;

                                $timestamp_jam_masuk_real = strtotime($rekap['jam_masuk']);
                                $timestamp_jam_masuk_kantor = strtotime($jam_masuk_kantor);

                                $terlambat = max(0, $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor);
                                $total_terlambat += $terlambat;

                                // Calculate early departure
                                if (!empty($rekap['tanggal_keluar'])) {
                                    $timestamp_jam_pulang_real = strtotime($rekap['jam_keluar']);
                                    $timestamp_jam_pulang_kantor = strtotime($jam_pulang_kantor_str);

                                    // Adjust for overnight shifts
                                    if (strtotime($rekap['tanggal_keluar']) > strtotime($rekap['tanggal_masuk'])) {
                                        $timestamp_jam_pulang_real += 24 * 3600;
                                    }

                                    $awal = max(0, $timestamp_jam_pulang_kantor - $timestamp_jam_pulang_real);
                                    $total_awal += $awal;
                                }
                            } elseif ($rekap['role'] == 'sumber' || $rekap['role'] == 'tidar') {
                                // Calculate total late hours
                                $jam_presensi = $rekap['lokasi_presensi'];
                                $shift = $rekap['shift'];
                                $jam_query = "SELECT * FROM shift WHERE id = 1";
                                $jam_stmt = $connection->prepare($jam_query);
                                $jam_stmt->execute();
                                $jam_result = $jam_stmt->get_result()->fetch_assoc();

                                if ($shift == 'A') {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['masuk_a']));
                                    $jam_pulang_kantor_str = $jam_result['pulang_a'];
                                } elseif ($shift == 'B') {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['masuk_b']));
                                    $jam_pulang_kantor_str = $jam_result['pulang_b'];
                                } elseif ($shift == 'C') {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['masuk_c']));
                                    $jam_pulang_kantor_str = $jam_result['pulang_c'];
                                } elseif ($shift == 'D') {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['masuk_d']));
                                    $jam_pulang_kantor_str = $jam_result['pulang_d'];
                                } else {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($jam_result['masuk_e']));
                                    $jam_pulang_kantor_str = $jam_result['pulang_e'];
                                }

                                $rekap['jam_masuk_kantor'] = $jam_masuk_kantor;
                                $rekap['jam_pulang_kantor'] = $jam_pulang_kantor_str;

                                $timestamp_jam_masuk_real = strtotime($rekap['jam_masuk']);
                                $timestamp_jam_masuk_kantor = strtotime($jam_masuk_kantor);

                                $terlambat = max(0, $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor);
                                $total_terlambat += $terlambat;

                                // Calculate early departure
                                if (!empty($rekap['tanggal_keluar'])) {
                                    $timestamp_jam_pulang_real = strtotime($rekap['jam_keluar']);
                                    $timestamp_jam_pulang_kantor = strtotime($jam_pulang_kantor_str);

                                    // Adjust for overnight shifts
                                    if (strtotime($rekap['tanggal_keluar']) > strtotime($rekap['tanggal_masuk'])) {
                                        $timestamp_jam_pulang_real += 24 * 3600;
                                    }

                                    $awal = max(0, $timestamp_jam_pulang_kantor - $timestamp_jam_pulang_real);
                                    $total_awal += $awal;
                                }
                            }

                            // Determine the photo paths
                            $foto_nama_masuk = htmlspecialchars($rekap['foto_masuk']);
                            $foto_masuk_path_pegawai = $_SERVER['DOCUMENT_ROOT'] . "/pegawai/presensi/foto/" . $foto_nama_masuk;
                            $foto_masuk_path_shift = $_SERVER['DOCUMENT_ROOT'] . "/shift/presensi/foto/" . $foto_nama_masuk;

                            if (file_exists($foto_masuk_path_pegawai)) {
                                $foto_masuk = "/pegawai/presensi/foto/" . $foto_nama_masuk;
                            } elseif (file_exists($foto_masuk_path_shift)) {
                                $foto_masuk = "/shift/presensi/foto/" . $foto_nama_masuk;
                            } else {
                                $foto_masuk = "https://internal.pdamkotamagelang.com/pegawai/presensi/foto/" . $foto_nama_masuk;
                            }

                            $foto_nama_keluar = htmlspecialchars($rekap['foto_keluar']);
                            $foto_keluar_path_pegawai = $_SERVER['DOCUMENT_ROOT'] . "/pegawai/presensi/foto/" . $foto_nama_keluar;
                            $foto_keluar_path_shift = $_SERVER['DOCUMENT_ROOT'] . "/shift/presensi/foto/" . $foto_nama_keluar;

                            if (file_exists($foto_keluar_path_pegawai)) {
                                $foto_keluar = "/pegawai/presensi/foto/" . $foto_nama_keluar;
                            } elseif (file_exists($foto_keluar_path_shift)) {
                                $foto_keluar = "/shift/presensi/foto/" . $foto_nama_keluar;
                            } else {
                                $foto_keluar = "https://internal.pdamkotamagelang.com/pegawai/presensi/foto/" . $foto_nama_keluar;
                            }
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= $rekap['nama'] ?></td>
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
                                <td class="text-center">
                                    <?= !empty($rekap['tanggal_keluar']) ? ($total_jam_kerja <= 0 && $selisih_menit_kerja <= 0 && $selisih_detik_kerja <= 0 ? '<span class="badge bg-success">---</span>' : '<span style="color: green; font-weight: bold;">' . $total_jam_kerja . ' Jam ' . $selisih_menit_kerja . ' Menit ' . $selisih_detik_kerja . ' Detik</span>') : '' ?>
                                </td>
                                <!-- <td><?= htmlspecialchars($timestamp_jam_pulang_kantor . $timestamp_jam_pulang_real) ?></td> -->
                                <td><?= htmlspecialchars($rekap['lokasi_presensi']) ?></td>
                                <td>
                                    <?php
                                    if ($rekap['shift'] == 'A') {
                                        echo 'Pagi';
                                    } elseif ($rekap['shift'] == 'B') {
                                        echo 'Siang';
                                    } elseif ($rekap['shift'] == 'C') {
                                        echo 'Pagi';
                                    } elseif ($rekap['shift'] == 'D') {
                                        echo 'Siang';
                                    } elseif ($rekap['shift'] == 'E') {
                                        echo 'Malam';
                                    } else {
                                        echo htmlspecialchars($rekap['shift']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <img class="img-fluid" style="width: 100%; border-radius: 20px" src="<?= $foto_masuk ?>" alt="Foto Masuk">
                                </td>
                                <td>
                                    <img class="img-fluid" style="width: 100%; border-radius: 20px" src="<?= $foto_keluar ?>" alt="Foto Pulang">
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="text-center">
                        <td colspan="5"><strong>Total</strong></td>
                        <td><strong>
                                <?php
                                if ($total_terlambat > 0) {
                                    $hours = floor($total_terlambat / 3600);
                                    $minutes = floor(($total_terlambat % 3600) / 60);
                                    $seconds = $total_terlambat % 60;
                                    echo $hours . ' Jam ' . $minutes . ' Menit ' . $seconds . ' Detik';
                                } else {
                                    echo 'On Time';
                                }
                                ?>
                            </strong></td>
                        <td colspan="3"></td>
                        <td><strong>
                                <?php
                                if ($total_awal > 0) {
                                    $hours = floor($total_awal / 3600);
                                    $minutes = floor(($total_awal % 3600) / 60);
                                    $seconds = $total_awal % 60;
                                    echo $hours . ' Jam ' . $minutes . ' Menit ' . $seconds . ' Detik';
                                } else {
                                    echo 'On Time';
                                }
                                ?>
                            </strong></td>
                        <td><strong>
                                <?php
                                $hours = floor($total_kerja / 3600);
                                $minutes = floor(($total_kerja % 3600) / 60);
                                $seconds = $total_kerja % 60;
                                echo $hours . ' Jam ' . $minutes . ' Menit ' . $seconds . ' Detik';
                                ?>
                            </strong></td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="modal" id="exampleModal" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ekspor Excel Rekap Bulanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/admin/presensi/rekap_bulanan_excel.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Bulan</label>
                        <select name="filter_bulan" class="form-control">
                            <option value="">--Pilih Bulan--</option>
                            <?php for ($m = 1; $m <= 12; $m++) : ?>
                                <option value="<?= str_pad($m, 2, "0", STR_PAD_LEFT) ?>"><?= date("F", mktime(0, 0, 0, $m, 1)) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Tahun</label>
                        <select name="filter_tahun" class="form-control">
                            <option value="">--Pilih Tahun--</option>
                            <?php for ($y = date("Y"); $y <= date("Y") + 6; $y++) : ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Ekspor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../layout/footer.php'); ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.sort-btn');
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const column = button.dataset.column;
                const order = button.dataset.order === 'asc' ? 'desc' : 'asc';
                sortTable(column, order);
                button.dataset.order = order;
            });
        });
    });

    function sortTable(column, order) {
        const table = document.querySelector('table');
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const isNumeric = !isNaN(rows[0].querySelectorAll('td')[column].innerText);
        rows.sort((a, b) => {
            const aValue = isNumeric ? parseFloat(a.querySelectorAll('td')[column].innerText) : a.querySelectorAll('td')[column].innerText;
            const bValue = isNumeric ? parseFloat(b.querySelectorAll('td')[column].innerText) : b.querySelectorAll('td')[column].innerText;
            return order === 'asc' ? aValue > bValue ? 1 : -1 : aValue < bValue ? 1 : -1;
        });
        table.querySelector('tbody').innerHTML = '';
        rows.forEach(row => table.querySelector('tbody').appendChild(row));
    }
</script>