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

$judul = "Rekap Izin Harian";
include('../layout/header.php');
include_once('../../config.php');

// Filter pencarian berdasarkan nama jika parameter pencarian diberikan
$nama_condition = isset($_GET['nama']) ? "AND pegawai.nama LIKE ?" : "";

if (empty($_GET["tanggal_dari"])) {
    $tanggal_hari_ini = date('Y-m-d');
    $query = "SELECT
                    pegawai.nama,
                    pegawai.lokasi_presensi,
                    presensi.*,
                    users.role 
                FROM
                    pegawai
                    INNER JOIN presensi ON pegawai.id = presensi.id_pegawai 
                    LEFT JOIN users ON users.id_pegawai = pegawai.id 
                WHERE
                    users.role != 'admin'
                    AND presensi.tanggal_masuk = ?
                    AND presensi.keterangan IS NOT NULL 
                    AND presensi.keterangan != ''
                    $nama_condition
                ORDER BY
                    presensi.tanggal_masuk ASC, pegawai.id;";
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
                    pegawai.nama,
                    pegawai.lokasi_presensi,
                    presensi.*,
                    users.role 
                FROM
                    pegawai
                    INNER JOIN presensi ON pegawai.id = presensi.id_pegawai 
                    LEFT JOIN users ON users.id_pegawai = pegawai.id 
                WHERE
                    users.role != 'admin'
                    AND presensi.tanggal_masuk BETWEEN ? AND ?
                    AND presensi.keterangan IS NOT NULL 
                    AND presensi.keterangan != ''
                    $nama_condition
                ORDER BY
                    presensi.tanggal_masuk ASC;";
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

    /* Modern Card Design */
    .card {
        background: var(--background-light);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
        color: white;
        padding: 15px 20px;
        border-radius: 12px 12px 0 0;
        border: none;
        font-weight: 600;
    }

    .card-body {
        padding: 20px;
    }

    /* Modern Button Styling */
    .btn {
        border-radius: 8px;
        font-weight: 500;
        padding: 8px 16px;
        transition: all 0.2s ease;
        border: none;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: var(--secondary-color);
        color: white;
    }

    .btn-secondary:hover {
        background: var(--secondary-hover);
        transform: translateY(-1px);
    }

    .btn-success {
        background: var(--success-color);
        color: white;
    }

    .btn-danger {
        background: var(--danger-color);
        color: white;
    }

    .btn-warning {
        background: var(--warning-color);
        color: white;
    }

    /* Export & Filter Section */
    .export-section {
        background: var(--background-light);
        padding: 20px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        margin-bottom: 20px;
    }

    .export-section h5 {
        color: var(--text-dark);
        margin-bottom: 15px;
        font-weight: 600;
    }

    /* Filter Buttons */
    .filter-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .filter-buttons .btn {
        min-width: 120px;
        font-size: 14px;
    }

    /* Search Form */
    .search-form {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-form .form-control {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 8px 12px;
        transition: border-color 0.2s ease;
    }

    .search-form .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }

    /* Date Display */
    .date-display {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        padding: 15px 20px;
        border-radius: 10px;
        border-left: 4px solid var(--primary-color);
        margin-bottom: 20px;
        font-weight: 500;
        color: var(--text-dark);
    }

    /* Table Styling */
    .table-container {
        background: var(--background-light);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
    }

    .table-responsive {
        border-radius: 12px;
        overflow-x: auto;
    }

    .table {
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table thead th {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
        color: white;
        font-weight: 600;
        padding: 15px 12px;
        border: none;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table thead th:first-child {
        position: sticky;
        left: 0;
        z-index: 11;
    }

    .table tbody td {
        padding: 12px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }

    .table tbody td:first-child {
        position: sticky;
        left: 0;
        background: var(--background-light);
        z-index: 9;
        font-weight: 500;
        border-right: 1px solid var(--border-color);
    }

    .table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Photo Column */
    .foto-column {
        width: 80px;
        text-align: center;
    }

    .foto-column img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid var(--border-color);
    }

    /* Badge Styling */
    .badge {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        margin: 2px;
    }

    .badge-pill {
        border-radius: 50px;
    }

    .bg-primary {
        background-color: var(--primary-color) !important;
        color: white;
    }

    .bg-secondary {
        background-color: var(--secondary-color) !important;
        color: white;
    }

    .bg-success {
        background-color: var(--success-color) !important;
        color: white;
    }

    .bg-danger {
        background-color: var(--danger-color) !important;
        color: white;
    }

    /* Sort Button */
    .sort-btn {
        background: none;
        border: none;
        color: white;
        font-size: 12px;
        margin-left: 5px;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }

    .sort-btn:hover {
        opacity: 1;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .filter-buttons {
            justify-content: center;
        }

        .filter-buttons .btn {
            min-width: 100px;
            font-size: 12px;
        }

        .search-form {
            justify-content: center;
        }

        .table thead th,
        .table tbody td {
            padding: 8px 6px;
            font-size: 12px;
        }

        .foto-column img {
            width: 40px;
            height: 40px;
        }
    }

    /* Modal Styling */
    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
        color: white;
        border-radius: 12px 12px 0 0;
        border: none;
    }

    .modal-body {
        padding: 25px;
    }

    .form-label {
        font-weight: 500;
        color: var(--text-dark);
        margin-bottom: 8px;
    }

    .form-control {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 10px 12px;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }
</style>

<div class="page-body">
    <!-- Export & Filter Section -->
    <div class="export-section">
        <h5>📊 Export & Filter Data</h5>
        <div class="row">
            <div class="col-md-6">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    📤 Export Excel
                </button>
            </div>
            <div class="col-md-6">
                <div class="search-form">
                    <form method="GET" class="d-flex gap-2 w-100">
                        <input type="date" class="form-control" name="tanggal_dari" placeholder="Tanggal Dari" value="<?= $_GET['tanggal_dari'] ?? '' ?>">
                        <input type="date" class="form-control" name="tanggal_sampai" placeholder="Tanggal Sampai" value="<?= $_GET['tanggal_sampai'] ?? '' ?>">
                        <input type="text" class="form-control" name="nama" placeholder="Cari nama..." value="<?= $_GET['nama'] ?? '' ?>">
                        <button type="submit" class="btn btn-primary">🔍 Cari</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Buttons -->
    <div class="filter-buttons">
        <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian.php'">📊 Semua</button>
        <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_kantor.php'">🏢 Kantor</button>
        <button class="btn btn-secondary">👮 Satpam</button>
        <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_sumber.php'">💧 Sumber</button>
        <button class="btn btn-primary" onclick="location.href='../presensi/rekap_harian_absen.php'">📝 Izin</button>
        <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_event.php'">🎉 Event</button>
        <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_all.php'">📈 Rekap</button>
        <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_izin.php'">📈 Izin/Cuti</button>
    </div>

    <!-- Date Display -->
    <div class="date-display">
        📅 
        <?php if (empty($_GET['tanggal_dari'])) : ?>
            Rekap Izin Tanggal: <?= date('d F Y') ?>
        <?php else : ?>
            Rekap Izin Tanggal: <?= date('d F Y', strtotime($_GET['tanggal_dari'])) . ' sampai ' . date('d F Y', strtotime($_GET['tanggal_sampai'])) ?>
        <?php endif; ?>
    </div>

    <!-- Table Container -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr class="text-center">
                        <th>Nama</th>
                        <th>Tanggal Masuk/Izin <button class="sort-btn" data-column="2">▲▼</button></th>
                        <th>Jam Masuk/Izin</th>
                        <th>Tanggal Pulang</th>
                        <th>Jam Pulang</th>
                        <th>Total Terlambat</th>
                        <th>Pulang Awal</th>
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
                            <td colspan="13" class="text-center"> Belum ada data </td>
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
                                $lokasi_presensi = $rekap['lokasi_presensi'];
                                $lokasi_query = "SELECT * FROM lokasi_presensi WHERE nama_lokasi = ?";
                                $lokasi_stmt = $connection->prepare($lokasi_query);
                                $lokasi_stmt->bind_param("s", $lokasi_presensi);
                                $lokasi_stmt->execute();
                                $lokasi_result = $lokasi_stmt->get_result()->fetch_assoc();

                                // Extract day of the week from tanggal_masuk
                                $current_day_masuk = date('N', strtotime($rekap['tanggal_masuk']));

                                // $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk']));

                                if ($current_day_masuk == 1) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk_senin']));
                                } elseif ($current_day_masuk == 2) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk_selasa']));
                                } elseif ($current_day_masuk == 3) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk_rabu']));
                                } elseif ($current_day_masuk == 4) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk_kamis']));
                                } elseif ($current_day_masuk == 5) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk_jumat']));
                                } elseif ($current_day_masuk == 6) {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk_sabtu']));
                                } else {
                                    $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk_minggu']));
                                }

                                $jam_masuk = date('H:i:s', strtotime($rekap['jam_masuk']));
                                $timestamp_jam_masuk_real = strtotime($jam_masuk);
                                $timestamp_jam_masuk_kantor = strtotime($jam_masuk_kantor);

                                $terlambat = $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor;
                                $total_jam_terlambat = floor($terlambat / 3600);
                                $terlambat -= $total_jam_terlambat * 3600;
                                $selisih_menit_terlambat = floor($terlambat / 60);

                                // Extract day of the week from tanggal_keluar
                                $current_day_pulang = date('N', strtotime($rekap['tanggal_keluar']));

                                // Set the office end time
                                if ($current_day_pulang == 1) {
                                    $jam_pulang_kantor = strtotime($lokasi_result['jam_pulang_senin']);
                                } elseif ($current_day_pulang == 2) {
                                    $jam_pulang_kantor = strtotime($lokasi_result['jam_pulang_selasa']);
                                } elseif ($current_day_pulang == 3) {
                                    $jam_pulang_kantor = strtotime($lokasi_result['jam_pulang_rabu']);
                                } elseif ($current_day_pulang == 4) {
                                    $jam_pulang_kantor = strtotime($lokasi_result['jam_pulang_kamis']);
                                } elseif ($current_day_pulang == 5) {
                                    $jam_pulang_kantor = strtotime($lokasi_result['jam_pulang_jumat']);
                                } elseif ($current_day_pulang == 6) {
                                    $jam_pulang_kantor = strtotime($lokasi_result['jam_pulang_sabtu']);
                                } else {
                                    $jam_pulang_kantor = strtotime($lokasi_result['jam_pulang_minggu']);
                                }

                                $jam_pulang = strtotime($rekap['jam_keluar']);

                                // Calculate the early leaving time in seconds
                                $awal = $jam_pulang_kantor - $jam_pulang;

                                // Convert early leaving time from seconds to hours and minutes
                                $total_jam_awal = floor($awal / 3600);
                                $awal -= $total_jam_awal * 3600;
                                $selisih_menit_awal = floor($awal / 60);
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
                                    <img class="img-fluid" src="<?= $foto_masuk ?>">
                                </td>
                                <td class="foto-column">
                                    <img class="img-fluid" src="<?= $foto_keluar ?>">
                                </td>
                                <td><?= ($rekap['keterangan']) ?></td>
                                <td><?= ($rekap['jam_absen']) ?></td>
                                <td class="text-center">
                                    <a href="/absensi/admin/presensi/rekap.php?id=<?= htmlspecialchars($rekap['id_pegawai']) ?>" class="badge badge-pill bg-primary">Rekap</a>
                                    <a href="/absensi/admin/presensi/edit.php?id=<?= htmlspecialchars($rekap['id']) ?>" class="badge badge-pill bg-secondary">Edit</a>
                                    <a href="/absensi/admin/presensi/hapus.php?id=<?= htmlspecialchars($rekap['id']) ?>" class="badge badge-pill bg-danger tombol-hapus">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📤 Ekspor Excel Rekap Izin</h5>
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
                    <button type="submit" class="btn btn-primary">📥 Export Excel</button>
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