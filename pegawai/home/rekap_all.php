<?php
session_start();
ob_start();

// Check if the user is logged in and has the 'pegawai' role
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
    exit;
} elseif ($_SESSION["role"] == "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
    exit;
}

$judul = "Presensi";
include('../layout/header.php');
require_once('../../config.php');

// Check if the user has a name in the session
if (!isset($_SESSION["nama"])) {
    header("Location: ../../auth/login.php");
    exit;
}

$id_pengguna = $_SESSION["id"];

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
    /* Variabel warna */
    :root {
        --primary-color: #2563eb;
        --primary-hover: #1d4ed8;
        --secondary-color: #64748b;
        --secondary-hover: #475569;
        --success-color: #22c55e;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --background-light: #f8fafc;
        --border-color: #e2e8f0;
        --text-dark: #1e293b;
        --text-muted: #64748b;
    }

    .page-body {
        padding: 20px;
        background-color: var(--background-light);
    }
    
    .container-xl {
        background-color: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }

    .btn {
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: var(--primary-color);
        border: none;
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: var(--secondary-color);
        border: none;
    }

    .btn-secondary:hover {
        background: var(--secondary-hover);
    }

    .table {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgb(0 0 0 / 0.05);
    }

    .table thead th {
        background-color: var(--primary-color);
        color: white;
        font-weight: 500;
        border: none;
        padding: 12px;
    }

    /* Warna baris tabel */
    tr[style*="Kantor PDAM"] {
        background-color: #dbeafe !important; /* Biru muda */
    }

    tr[style*="background-color: #d1e7dd"] {
        background-color: #dcfce7 !important; /* Hijau muda */
    }

    td[style*="background-color: #f0f8ff"] {
        background-color: #f8fafc !important; /* Abu-abu sangat muda */
    }

    td[style*="background-color: #e6ffe6"] {
        background-color: #f0fdf4 !important; /* Hijau sangat muda */
    }

    td[style*="background-color: #ffe6e6"] {
        background-color: #fef2f2 !important; /* Merah sangat muda */
    }

    td[style*="background-color: #fff3e6"] {
        background-color: #fff7ed !important; /* Orange sangat muda */
    }

    /* Badge styles */
    .badge {
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: 500;
    }

    .badge.bg-success {
        background-color: var(--success-color) !important;
    }

    .badge.bg-primary {
        background-color: var(--primary-color) !important;
    }

    .badge.bg-secondary {
        background-color: var(--secondary-color) !important;
    }

    /* Status colors */
    span[style*="color: red"] {
        color: var(--danger-color) !important;
    }

    /* Input groups */
    .input-group {
        box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    .input-group input {
        border: 1px solid var(--border-color);
        padding: 10px;
    }

    .input-group input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    /* Modal styling */
    .modal-content {
        border-radius: 15px;
        border: none;
    }

    .modal-header {
        background-color: var(--primary-color);
        color: white;
        border-radius: 15px 15px 0 0;
        border-bottom: none;
    }

    .modal-body {
        padding: 20px;
    }

    /* Date display */
    .date-display {
        color: var(--text-muted);
        font-size: 0.95rem;
        margin: 15px 0;
    }

    /* Foto column */
    .foto-column img {
        border-radius: 10px;
        box-shadow: 0 2px 4px rgb(0 0 0 / 0.1);
        transition: transform 0.3s ease;
    }

    .foto-column img:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 6px rgb(0 0 0 / 0.1);
    }

    /* Action buttons container */
    .action-buttons {
        background-color: #f8fafc;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        flex-direction: column;
        align-items: stretch;
    }

    /* Filter buttons container */
    .filter-buttons {
        background-color: #f8fafc;
        padding: 10px;
        border-radius: 10px;
        display: flex;
        gap: 8px;
    }

    .foto-column img {
        transition: transform 0.3s ease;
        cursor: pointer;
    }

    .foto-column img:hover {
        transform: scale(1.1);
    }

    .badge {
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: 500;
    }

    .input-group {
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
    }

    .input-group input {
        border: 1px solid #dee2e6;
        padding: 10px;
    }

    .modal-content {
        border-radius: 15px;
    }

    .modal-header {
        background-color: #4361ee;
        color: white;
        border-radius: 15px 15px 0 0;
    }

    .modal-body {
        padding: 20px;
    }

    /* Tambahkan CSS baru untuk tombol */
    .action-buttons .btn {
        width: 100%;
        margin-bottom: 10px;
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
        margin-top: 15px;
    }

    .search-container .input-group {
        max-width: 100%;
    }

    .date-filter {
        flex-direction: column;
    }

    .date-filter input[type="date"] {
        width: 100%;
        margin-bottom: 10px;
    }

    .export-btn {
        width: 100%;
        margin-bottom: 15px;
    }

    .badge-pill {
        padding: 8px 12px;
        text-decoration: none;
        margin: 0 3px;
        font-size: 12px;
    }

    .badge-pill:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
</style>

<div class="page-body">
    <div class="container-xl">
        <!-- Bagian Export dan Filter Tanggal -->
        <div class="action-buttons">
            <div class="col-md-12">
                <form method="GET" class="date-filter">
                    <label for="tanggal_dari">Tanggal Awal</label>
                    <input type="date" class="form-control" name="tanggal_dari" required>
                    <label for="tanggal_sampai">Tanggal Akhir</label>
                    <input type="date" class="form-control" name="tanggal_sampai" required>
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </form>
            </div>
        </div>

        <!-- Bagian Filter dan Pencarian -->
        <div class="row align-items-center">
            <div class="col-md-12">
                <form method="GET" class="search-container">
                    <div class="input-group">
                        <input type="text" class="form-control" name="nama" placeholder="Cari berdasarkan nama">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tampilan tanggal -->
        <div class="date-display mb-3">
            <?php if (empty($_GET['tanggal_dari'])) : ?>
                <span class="text-muted">Rekap Presensi Tanggal: <?= date('d F Y') ?></span>
            <?php else : ?>
                <span class="text-muted">Rekap Presensi Tanggal: <?= date('d F Y', strtotime($_GET['tanggal_dari'])) . ' sampai ' . date('d F Y', strtotime($_GET['tanggal_sampai'])) ?></span>
            <?php endif; ?>
        </div>

        <table class="table table-bordered mt-2">
            <thead>
                <tr class="text-center">
                    <th>Nama</th>
                    <th>Tanggal Masuk </th>
                    <th>Jam Masuk </th>
                    <th>Tanggal Pulang</th>
                    <th>Jam Pulang </th>
                    <th>Total Terlambat</th>
                    <th>Pulang Awal</th>
                    <th>Lokasi</th>
                    <th style="width: 200px;">Foto Masuk</th>
                    <th style="width: 200px;">Foto Pulang</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0) { ?>
                    <tr>
                        <td colspan="10"> Belum ada data </td>
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
                            <td style="background-color: #ffe6e6;"><?= !empty($rekap['tanggal_keluar']) ? date('d F Y', strtotime($rekap['tanggal_keluar'])) : '' ?></td>
                            <td style="background-color: #fff3e6;" class="text-center"><?= htmlspecialchars($rekap['jam_keluar']) ?></td>
                            <td class="text-center">
                                <?= ($total_jam_terlambat < 0 && !empty($rekap['jam_masuk'])) ? '<span class="badge bg-success">On Time</span>' : (!empty($rekap['jam_masuk']) ? '<span style="color: red; font-weight: bold;">' . $total_jam_terlambat . ' Jam ' . $selisih_menit_terlambat . ' Menit</span>' : '') ?>
                            </td>
                            <td class="text-center">
                                <?= !empty($rekap['tanggal_keluar']) ? ($total_jam_awal < 0 ? '<span class="badge bg-success">On Time</span>' : '<span style="color: red; font-weight: bold;">' . $total_jam_awal . ' Jam ' . $selisih_menit_awal . ' Menit</span>') : '' ?>
                            </td>
                            <td><?= ($rekap['lokasi_presensi']) ?></td>
                            <td class="foto-column">
                                <img class="img-fluid" style="width: 100%; border-radius: 20px" src="<?= $foto_masuk ?>">
                            </td>
                            <td class="foto-column">
                                <img class="img-fluid" style="width: 100%; border-radius: 20px" src="<?= $foto_keluar ?>">
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php } ?>
            </tbody>
        </table>
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
                const column = button.dataset.column;
                const order = button.dataset.order === 'asc' ? 'desc' : 'asc';
                sortTable(column, order);
                button.dataset.order = order;
            });
        });
    });

    function sortTable(column, order) {
        const table = document.querySelector('table');
        const rows = Array.from(table.querySelectorAll('tr')).slice(1); // Exclude the header row
        const isNumeric = !isNaN(rows[0].querySelectorAll('td')[column].innerText);
        const sortedRows = rows.sort((a, b) => {
            const aValue = isNumeric ? parseFloat(a.querySelectorAll('td')[column].innerText) : a.querySelectorAll('td')[column].innerText;
            const bValue = isNumeric ? parseFloat(b.querySelectorAll('td')[column].innerText) : b.querySelectorAll('td')[column].innerText;
            return order === 'asc' ? aValue > bValue ? 1 : -1 : aValue < bValue ? 1 : -1;
        });

        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';
        sortedRows.forEach(row => tbody.appendChild(row));
    }
</script>