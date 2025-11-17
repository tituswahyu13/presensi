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

$judul = "Konfirmasi Izin Bagian HubLang";
include('../layout/header.php');
require_once('../../config.php');

// Check if the user has a name in the session
if (!isset($_SESSION["nama"])) {
    header("Location: ../../auth/login.php");
    exit;
}

$id_pengguna = $_SESSION["id"];

// Penanganan update status jika tombol Tolak diklik
if (isset($_POST['tolak_id']) && isset($_POST['tolak_tanggal'])) {
    require_once('../../config.php'); // pastikan koneksi sudah ada
    $tolak_id = intval($_POST['tolak_id']);
    $tolak_tanggal = $_POST['tolak_tanggal'];
    $update = $connection->prepare("UPDATE absensi SET status = 0 WHERE id_pegawai = ? AND tanggal_absen = ?");
    $update->bind_param("is", $tolak_id, $tolak_tanggal);
    $update->execute();
    // Refresh halaman agar data terbaru muncul
    echo '<meta http-equiv="refresh" content="0">';
    exit;
}
// Penanganan update status jika tombol Terima diklik
if (isset($_POST['terima_id']) && isset($_POST['terima_tanggal'])) {
    require_once('../../config.php');
    $terima_id = intval($_POST['terima_id']);
    $terima_tanggal = $_POST['terima_tanggal'];
    // Update status absensi
    $update = $connection->prepare("UPDATE absensi SET status = 1 WHERE id_pegawai = ? AND tanggal_absen = ?");
    $update->bind_param("is", $terima_id, $terima_tanggal);
    $update->execute();

    // Ambil data absensi terkait
    $absensi_stmt = $connection->prepare("SELECT * FROM absensi WHERE id_pegawai = ? AND tanggal_absen = ?");
    $absensi_stmt->bind_param("is", $terima_id, $terima_tanggal);
    $absensi_stmt->execute();
    $absensi_result = $absensi_stmt->get_result();
    $absensi_row = $absensi_result->fetch_assoc();

    if ($absensi_row) {
        // Cek apakah sudah ada data presensi
        $cek_stmt = $connection->prepare("SELECT id FROM presensi WHERE id_pegawai = ? AND tanggal_masuk = ?");
        $cek_stmt->bind_param("is", $terima_id, $terima_tanggal);
        $cek_stmt->execute();
        $cek_result = $cek_stmt->get_result();
        if ($cek_result->num_rows > 0) {
            // Jika ada, update tanggal_keluar, jam_keluar, keterangan
            $update_presensi = $connection->prepare("UPDATE presensi SET tanggal_keluar = ?, jam_keluar = ?, keterangan = ? WHERE id_pegawai = ? AND tanggal_masuk = ?");
            $update_presensi->bind_param("sssis", $absensi_row['tanggal_absen'], $absensi_row['jam_absen'], $absensi_row['keterangan'], $terima_id, $terima_tanggal);
            $update_presensi->execute();
        } else {
            // Jika belum ada, insert baris baru
            $insert_presensi = $connection->prepare("INSERT INTO presensi (id_pegawai, tanggal_masuk, jam_masuk, keterangan) VALUES (?, ?, ?, ?)");
            $insert_presensi->bind_param("isss", $absensi_row['id_pegawai'], $absensi_row['tanggal_absen'], $absensi_row['jam_absen'], $absensi_row['keterangan']);
            $insert_presensi->execute();
        }
    }
    echo '<meta http-equiv="refresh" content="0">';
    exit;
}

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
                absensi.*,
                users.role 
            FROM
                pegawai
                INNER JOIN absensi ON pegawai.id = absensi.id_pegawai 
                AND absensi.tanggal_absen = ?
                LEFT JOIN users ON users.id_pegawai = pegawai.id 
            WHERE
                users.role != 'admin'
                AND users.status = 'aktif'
                AND pegawai.jabatan = 'Asisten Manajer'
                AND pegawai.bagian LIKE '2%'
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
                absensi.tanggal_absen ASC, 
                absensi.jam_absen ASC;";

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
                absensi.*,
                users.role 
            FROM
                pegawai
                INNER JOIN absensi ON pegawai.id = absensi.id_pegawai 
                AND absensi.tanggal_absen BETWEEN ? AND ?
                LEFT JOIN users ON users.id_pegawai = pegawai.id 
            WHERE
                users.role != 'admin'
                AND users.status = 'aktif'
                AND pegawai.jabatan = 'Asisten Manajer'
                AND pegawai.bagian LIKE '2%'
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
                absensi.tanggal_absen ASC, 
                absensi.jam_absen ASC;";

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
        --primary-color: #4a90e2;
        /* Warna biru yang lebih cerah */
        --primary-hover: #357ab8;
        /* Warna biru gelap saat hover */
        --secondary-color: #7f8c8d;
        /* Warna abu-abu */
        --success-color: #2ecc71;
        /* Warna hijau */
        --danger-color: #e74c3c;
        /* Warna merah */
        --warning-color: #f39c12;
        /* Warna oranye */
        --background-light: #ffffff;
        /* Warna latar belakang putih */
        --border-color: #bdc3c7;
        /* Warna border */
        --text-dark: #2c3e50;
        /* Warna teks gelap */
        --text-muted: #95a5a6;
        /* Warna teks pudar */
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
        box-shadow: none;
        /* Menghapus bayangan untuk tampilan minimalis */
    }

    .table thead th {
        background-color: var(--primary-color);
        color: white;
        font-weight: 500;
        border: none;
        padding: 12px;
    }

    .table tbody tr {
        background-color: transparent;
        /* Menghapus warna latar belakang baris tabel */
    }

    .table tbody td {
        background-color: transparent;
        /* Menghapus warna latar belakang sel tabel */
        border: none;
        /* Menghapus border untuk tampilan lebih bersih */
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
        box-shadow: none;
        /* Menghapus bayangan untuk tampilan minimalis */
        transition: transform 0.3s ease;
    }

    .foto-column img:hover {
        transform: scale(1.05);
        box-shadow: none;
        /* Menghapus bayangan saat hover */
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
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
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

    /* Tombol aksi checklist dan silang */
    .btn-aksi {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        font-size: 1.3rem;
        border: none;
        border-radius: 50%;
        margin: 0 4px;
        transition: background 0.2s, transform 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.07);
        cursor: pointer;
    }
    .btn-aksi.check {
        background: #2ecc71;
        color: #fff;
    }
    .btn-aksi.check:hover {
        background: #27ae60;
        transform: scale(1.08);
    }
    .btn-aksi.cross {
        background: #e74c3c;
        color: #fff;
    }
    .btn-aksi.cross:hover {
        background: #c0392b;
        transform: scale(1.08);
    }
    .btn-aksi.check span {
        color: #009e2f;
        font-weight: bold;
        font-size: 1.4rem;
    }
    .btn-aksi.cross span {
        color: #d90429;
        font-weight: bold;
        font-size: 1.4rem;
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
                <span class="text-muted">Pengajuan Izin Tanggal: <?= date('d F Y') ?></span>
            <?php else : ?>
                <span class="text-muted">Pengajuan Izin Tanggal: <?= date('d F Y', strtotime($_GET['tanggal_dari'])) . ' sampai ' . date('d F Y', strtotime($_GET['tanggal_sampai'])) ?></span>
            <?php endif; ?>
        </div>

        <table class="table table-bordered mt-2">
            <thead>
                <tr class="text-center">
                    <th>No</th>
                    <th>Nama</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Keterangan</th>
                    <th>Status</th>
                    <th>Aksi</th> <!-- Tambahkan kolom Aksi -->
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = $result->fetch_assoc()) {
                    echo '<tr class="text-center">';
                    echo '<td>' . $no++ . '</td>';
                    echo '<td>' . htmlspecialchars($row['nama']) . '</td>';
                    echo '<td>' . date('d-m-Y', strtotime($row['tanggal_absen'])) . '</td>';
                    // Format jam_absen
                    $jam = $row['jam_absen'];
                    $jam_formatted = $jam ? date('H:i', strtotime($jam)) : '-';
                    echo '<td>' . $jam_formatted . '</td>';
                    echo '<td>' . htmlspecialchars($row['keterangan']) . '</td>';
                    // Status
                    $status = $row['status'];
                    if (is_null($status)) {
                        echo '<td><span class="badge" style="background-color: orange;">Pending</span></td>';
                    } elseif ($status == '0') {
                        echo '<td><span class="badge" style="background-color: #dc3545;">Ditolak</span></td>';
                    } elseif ($status == '1') {
                        echo '<td><span class="badge bg-success">Diterima</span></td>';
                            } else {
                        echo '<td>' . htmlspecialchars($status) . '</td>';
                    }
                    // Kolom Aksi
                    echo '<td>';
                    if (is_null($status)) {
                        // Tombol Terima dalam form agar bisa POST
                        echo '<form method="POST" style="display:inline;">';
                        echo '<input type="hidden" name="terima_id" value="' . htmlspecialchars($row['pegawai_id']) . '">';
                        echo '<input type="hidden" name="terima_tanggal" value="' . htmlspecialchars($row['tanggal_absen']) . '">';
                        echo '<button type="submit" class="btn-aksi check" title="Terima">'
                            . '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">'
                            . '<path d="M6 12.5L10 16.5L16 7.5" stroke="#009e2f" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>'
                            . '</svg>'
                            . '</button>';
                        echo '</form>';
                        // Tombol Tolak dalam form agar bisa POST
                        echo '<form method="POST" style="display:inline;">';
                        echo '<input type="hidden" name="tolak_id" value="' . htmlspecialchars($row['pegawai_id']) . '">';
                        echo '<input type="hidden" name="tolak_tanggal" value="' . htmlspecialchars($row['tanggal_absen']) . '">';
                        echo '<button type="submit" class="btn-aksi cross" title="Tolak">'
                            . '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">'
                            . '<line x1="6" y1="6" x2="16" y2="16" stroke="#d90429" stroke-width="3" stroke-linecap="round"/>'
                            . '<line x1="16" y1="6" x2="6" y2="16" stroke="#d90429" stroke-width="3" stroke-linecap="round"/>'
                            . '</svg>'
                            . '</button>';
                        echo '</form>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                if ($result->num_rows == 0) {
                    echo '<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>';
                }
                ?>
            </tbody>
        </table>
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