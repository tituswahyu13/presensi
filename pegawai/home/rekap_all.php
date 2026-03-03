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
    /* Menggunakan variabel dari header.php (tema dark/neon) */
    .page-body {
        padding: 20px;
        background-color: var(--bg-color);
        /* Warna latar belakang dari header */
        color: var(--text-color);
    }

    .container-xl {
        background-color: var(--card-bg);
        /* Warna card/bg transparan dari header */
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 0 10px var(--glow-color);
        /* Efek glow konsisten */
    }

    .action-buttons,
    .search-container {
        background-color: rgba(0, 0, 0, 0.2);
        /* Sedikit gelap untuk kontras */
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        border: 1px solid var(--border-color);
    }

    .btn-primary {
        background: var(--primary-color);
        color: var(--bg-color);
        border: 1px solid var(--primary-color);
        box-shadow: 0 0 5px var(--glow-color);
    }

    .btn-primary:hover {
        background: var(--secondary-color);
        box-shadow: 0 0 8px var(--secondary-color);
    }

    /* Pengaturan Border dan Warna Tabel */
    .table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        color: var(--text-color);
        background-color: transparent !important;
    }

    .table thead th {
        background-color: var(--primary-color);
        color: var(--bg-color);
        font-weight: 700;
        border: none;
        padding: 12px;
        border-right: 1px solid rgba(0, 0, 0, 0.2);
    }

    .table thead th:last-child {
        border-right: none;
    }

    .table tbody tr:nth-child(even) {
        background-color: rgba(18, 18, 25, 0.5);
    }

    .table tbody tr:nth-child(odd) {
        background-color: rgba(18, 18, 25, 0.2);
    }

    .table tbody td {
        border-right: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
        padding: 8px 12px;
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    .table tbody td:last-child {
        border-right: none;
    }

    /* Warna Status Merah */
    .text-danger-custom {
        color: #ff6b6b !important;
        /* Merah terang untuk kontras di dark mode */
    }

    /* Style untuk baris khusus (menggantikan inline style) */
    .row-pdam {
        background-color: rgba(0, 164, 212, 0.2) !important;
        /* Biru semi-transparan */
    }

    .row-other {
        background-color: rgba(0, 224, 179, 0.1) !important;
        /* Hijau semi-transparan */
    }

    /* Membersihkan sisa styling */
    .date-display {
        color: var(--text-color);
        font-size: 0.95rem;
        margin: 15px 0;
    }

    .foto-column img {
        border-radius: 10px;
    }
</style>

<div class="page-body">
    <div class="container-xl">
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
                        <td colspan="10" class="text-center"> Belum ada data </td>
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
                        <tr class="<?= $rekap['lokasi_presensi'] == 'Kantor PDAM' ? 'row-pdam' : 'row-other'; ?>">
                            <td><?= htmlspecialchars($rekap['nama']) ?></td>
                            <td class="text-center"><?= !empty($rekap['tanggal_masuk']) ? date('d F Y', strtotime($rekap['tanggal_masuk'])) : '<span class="text-danger-custom">Belum presensi</span>' ?></td>
                            <td class="text-center"><?= htmlspecialchars($rekap['jam_masuk']) ?></td>
                            <td><?= !empty($rekap['tanggal_keluar']) ? date('d F Y', strtotime($rekap['tanggal_keluar'])) : '' ?></td>
                            <td class="text-center"><?= htmlspecialchars($rekap['jam_keluar']) ?></td>
                            <td class="text-center">
                                <?= ($total_jam_terlambat < 0 && !empty($rekap['jam_masuk'])) ? '<span class="badge bg-success">On Time</span>' : (!empty($rekap['jam_masuk']) ? '<span class="text-danger-custom fw-bold">' . $total_jam_terlambat . ' Jam ' . $selisih_menit_terlambat . ' Menit</span>' : '') ?>
                            </td>
                            <td class="text-center">
                                <?= !empty($rekap['tanggal_keluar']) ? ($total_jam_awal < 0 ? '<span class="badge bg-success">On Time</span>' : '<span class="text-danger-custom fw-bold">' . $total_jam_awal . ' Jam ' . $selisih_menit_awal . ' Menit</span>') : '' ?>
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