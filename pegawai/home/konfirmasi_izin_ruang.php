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

$judul = "Konfirmasi Izin";
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
                AND pegawai.jabatan = 'Staf'
                AND pegawai.bagian = (SELECT bagian FROM pegawai WHERE id = ?)
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
        $stmt->bind_param("ssi", $tanggal_hari_ini, $id_pengguna, $nama);
    } else {
        $stmt->bind_param("si", $tanggal_hari_ini, $id_pengguna);
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
                AND pegawai.jabatan = 'Staf'
                AND pegawai.bagian = (SELECT bagian FROM pegawai WHERE id = ?)
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
        $stmt->bind_param("ssis", $tanggal_dari, $tanggal_sampai, $id_pengguna, $nama);
    } else {
        $stmt->bind_param("ssi", $tanggal_dari, $tanggal_sampai, $id_pengguna);
    }
}
$stmt->execute();
$result = $stmt->get_result();

$bulan = empty($_GET['tanggal_dari']) ? date('Y-m-d') : $_GET['tanggal_dari'] . '-' . $_GET['tanggal_sampai'];
?>

<style>
    /* Tambahkan CSS yang diperlukan agar konsisten dengan tema header.php */
    .table {
        /* Memastikan border collapse untuk kontrol border yang lebih baik */
        border-collapse: separate; 
        border-spacing: 0;
        /* Border luar tabel */
        border: 1px solid var(--border-color); 
        border-radius: 8px; /* Sudut membulat */
        overflow: hidden; /* Penting untuk menjaga border-radius */
        width: 100%;
        color: var(--text-color);
    }
    
    .table thead th {
        /* Mengambil warna dari primary color yang didefinisikan di header.php */
        background-color: var(--primary-color); 
        color: var(--bg-color); /* Ubah warna teks menjadi warna latar belakang dark mode */
        font-weight: 700;
        border: none;
        padding: 12px;
        /* Menambahkan border di sisi kanan untuk memisahkan kolom */
        border-right: 1px solid rgba(0, 0, 0, 0.2); 
    }
    
    .table thead th:last-child {
        border-right: none; /* Hapus border di kolom terakhir header */
    }

    .table tbody tr:nth-child(even) {
        background-color: rgba(18, 18, 25, 0.5); /* Menggunakan warna dark transparan yang sedikit lebih pekat */
    }

    .table tbody tr:nth-child(odd) {
        background-color: rgba(18, 18, 25, 0.2); /* Menggunakan warna dark transparan yang lebih terang */
    }
    
    .table tbody td {
        /* Menambahkan border di sisi kanan untuk memisahkan kolom data */
        border-right: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
        padding: 8px 12px;
    }

    .table tbody tr:last-child td {
        border-bottom: none; /* Hapus border bawah di baris terakhir */
    }

    .table tbody td:last-child {
        border-right: none; /* Hapus border di kolom terakhir data */
    }


    /* Mengganti styling yang dihapus dari blok style sebelumnya */
    .page-body {
        padding: 20px;
        background-color: var(--bg-color); /* Menggunakan warna latar belakang dari header */
        color: var(--text-color);
    }
    .container-xl {
        background-color: var(--card-bg); /* Menggunakan warna card/bg transparan dari header */
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 0 10px var(--glow-color); /* Efek glow konsisten */
    }
    .action-buttons, .search-container {
        background-color: rgba(0, 0, 0, 0.2); /* Sedikit gelap untuk kontras */
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        border: 1px solid var(--border-color);
    }
    /* Mengubah warna tombol agar sesuai dengan tema header */
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
    /* Mengubah warna teks merah agar kontras di dark mode */
    .text-center span[style*="color: red"] {
        color: #ff6b6b !important; /* Merah terang */
    }
    
    .date-display {
        color: var(--text-color);
        font-size: 0.95rem;
        margin: 15px 0;
    }
    /* Memastikan input-group tetap terlihat bagus */
    .input-group input, .date-filter input[type="date"] {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid var(--border-color);
        color: var(--text-color);
    }

    /* Penyesuaian Badge Status untuk Dark Theme (menggunakan inline style selector yang sudah ada) */
    .badge[style*="orange"] {
        background-color: #ffc107 !important; /* Kuning/Orange terang */
        color: var(--bg-color) !important;
        font-weight: 700;
    }
    .badge[style*="#dc3545"] {
        background-color: #e74c3c !important; /* Merah terang/Danger */
        color: #fff !important;
        font-weight: 700;
    }
    .badge.bg-success {
        background-color: var(--primary-color) !important; /* Menggunakan primary-color (neon) untuk success */
        color: var(--bg-color) !important;
        font-weight: 700;
    }

    /* Styling tombol aksi (checklist dan silang) */
    .btn-aksi {
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.3) !important;
    }
    .btn-aksi.check {
        background: var(--primary-color) !important;
        color: var(--bg-color) !important;
    }
    .btn-aksi.check:hover {
        background: var(--secondary-color) !important;
    }
    .btn-aksi.cross {
        background: var(--danger-color) !important;
        color: #fff !important;
    }
    .btn-aksi.cross:hover {
        background: #c0392b !important;
    }

    /* Mengubah warna stroke SVG untuk menyesuaikan tema */
    .btn-aksi.check svg path {
        /* Menggunakan warna latar belakang tombol untuk stroke */
        stroke: var(--bg-color) !important; 
    }
    .btn-aksi.cross svg line {
        stroke: #fff !important;
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
                    <th>Aksi</th> </tr>
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
                        // Menggunakan inline style untuk dicocokkan oleh CSS override
                        echo '<td><span class="badge" style="background-color: orange;">Pending</span></td>'; 
                    } elseif ($status == '0') {
                        // Menggunakan inline style untuk dicocokkan oleh CSS override
                        echo '<td><span class="badge" style="background-color: #dc3545;">Ditolak</span></td>'; 
                    } elseif ($status == '1') {
                        // Menggunakan class bg-success yang sudah dimapping ke primary-color (neon)
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
                            . '<path d="M6 12.5L10 16.5L16 7.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>'
                            . '</svg>'
                            . '</button>';
                        echo '</form>';
                        // Tombol Tolak dalam form agar bisa POST
                        echo '<form method="POST" style="display:inline;">';
                        echo '<input type="hidden" name="tolak_id" value="' . htmlspecialchars($row['pegawai_id']) . '">';
                        echo '<input type="hidden" name="tolak_tanggal" value="' . htmlspecialchars($row['tanggal_absen']) . '">';
                        echo '<button type="submit" class="btn-aksi cross" title="Tolak">'
                            . '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">'
                            . '<line x1="6" y1="6" x2="16" y2="16" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>'
                            . '<line x1="16" y1="6" x2="6" y2="16" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>'
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