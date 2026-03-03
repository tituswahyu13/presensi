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

$judul = "Rekap";
include('../layout/header.php');
require_once('../../config.php');

// Check if the user has a name in the session
if (!isset($_SESSION["nama"])) {
    header("Location: ../../auth/login.php");
    exit;
}

$id_pengguna = $_SESSION["id"];

// Determine date range for the query
$tanggal_dari = $_GET["tanggal_dari"] ?? date('Y-m-d');
$tanggal_sampai = $_GET["tanggal_sampai"] ?? date('Y-m-d');

// Use prepared statements to prevent SQL injection
$query = "SELECT presensi.*, pegawai.nama, pegawai.lokasi_presensi, users.role  
          FROM presensi 
          JOIN pegawai ON presensi.id_pegawai = pegawai.id
          JOIN users on users.id_pegawai = pegawai.id  
          WHERE presensi.tanggal_masuk BETWEEN ? AND ? 
          AND presensi.id_pegawai = ?
          ORDER BY tanggal_masuk DESC";
$stmt = $connection->prepare($query);
$stmt->bind_param('ssi', $tanggal_dari, $tanggal_sampai, $id_pengguna);
$stmt->execute();
$result = $stmt->get_result();

$bulan = $tanggal_dari . ' - ' . $tanggal_sampai;
$total_terlambat = 0;
$total_awal = 0; // Pastikan variabel ini diinisialisasi
$total_kerja = 0; // Pastikan variabel ini diinisialisasi
?>

<style>
    /* CSS untuk konsistensi tema gelap/neon */
    .table {
        /* Memastikan border collapse untuk kontrol border yang lebih baik */
        border-collapse: separate;
        border-spacing: 0;
        /* Border luar tabel */
        border: 1px solid var(--border-color);
        border-radius: 8px;
        /* Sudut membulat */
        overflow: hidden;
        /* Penting untuk menjaga border-radius */
        width: 100%;
        color: var(--text-color);
        background-color: var(--card-bg);
        /* Set default row background to card background */
    }

    .table thead th {
        /* Mengambil warna dari primary color yang didefinisikan di header.php */
        background-color: var(--primary-color);
        color: var(--bg-color);
        /* Ubah warna teks menjadi warna latar belakang dark mode */
        font-weight: 700;
        border: none;
        padding: 12px;
        /* Menambahkan border di sisi kanan untuk memisahkan kolom */
        border-right: 1px solid rgba(0, 0, 0, 0.2);
    }

    .table thead th:last-child {
        border-right: none;
        /* Hapus border di kolom terakhir header */
    }

    .table tbody tr:nth-child(even) {
        background-color: rgba(18, 18, 25, 0.5);
        /* Menggunakan warna dark transparan yang sedikit lebih pekat */
    }

    .table tbody tr:nth-child(odd) {
        background-color: rgba(18, 18, 25, 0.2);
        /* Menggunakan warna dark transparan yang lebih terang */
    }

    .table tbody td {
        /* Menambahkan border di sisi kanan untuk memisahkan kolom data */
        border-right: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
        padding: 8px 12px;
    }

    .table tbody tr:last-child td {
        border-bottom: none;
        /* Hapus border bawah di baris terakhir */
    }

    .table tbody td:last-child {
        border-right: none;
        /* Hapus border di kolom terakhir data */
    }

    /* Style untuk baris footer tabel */
    .table tfoot tr {
        background-color: var(--primary-color);
        /* Gunakan warna utama untuk footer */
        color: var(--bg-color);
        font-weight: bold;
    }

    .table tfoot td {
        border-right: 1px solid rgba(0, 0, 0, 0.2) !important;
        color: var(--bg-color) !important;
        font-weight: bold;
        padding: 12px 10px;
    }

    .table tfoot td:last-child {
        border-right: none !important;
    }

    .page-body {
        padding: 20px;
        background-color: var(--bg-color);
        /* Menggunakan warna latar belakang dari header */
        color: var(--text-color);
    }

    .container-xl {
        background-color: var(--card-bg);
        /* Menggunakan warna card/bg transparan dari header */
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 0 10px var(--glow-color);
        /* Efek glow konsisten */
    }

    .input-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
        background-color: rgba(0, 0, 0, 0.2);
        padding: 15px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
    }

    .form-group {
        flex-grow: 1;
        min-width: 150px;
    }

    .form-group label {
        color: var(--text-color);
        margin-bottom: 5px;
        display: block;
    }

    .form-control {
        background-color: rgba(0, 0, 0, 0.3);
        color: var(--text-color);
        border: 1px solid var(--border-color);
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem var(--glow-color);
    }

    .btn-primary {
        background: var(--primary-color);
        color: var(--bg-color);
        border: 1px solid var(--primary-color);
        box-shadow: 0 0 5px var(--glow-color);
        transition: all 0.3s ease;
        padding: 6px 12px;
        border-radius: 4px;
        width: 100%;
    }

    .btn-primary:hover {
        background: var(--secondary-color);
        box-shadow: 0 0 8px var(--secondary-color);
        transform: translateY(-2px);
    }

    /* Mengubah warna teks inline yang merah agar kontras di dark mode */
    .text-center span[style*="color: red"] {
        color: #ff6b6b !important;
        /* Merah terang */
    }

    /* Mengubah warna teks inline yang hijau agar konsisten dengan primary color */
    .text-center span[style*="color: green"] {
        color: var(--primary-color) !important;
        /* Hijau neon */
    }

    .date-display {
        color: var(--text-color);
        font-size: 0.95rem;
        margin: 15px 0;
    }

    .badge.bg-success {
        background-color: var(--primary-color) !important;
        color: var(--bg-color) !important;
        font-weight: bold;
    }
</style>

<div class="page-body">
    <div class="container-xl">
        <form method="GET">
            <div class="input-group">
                <div class="form-group">
                    <label for="tanggal_dari">Tanggal <br> Dari:</label>
                    <input type="date" class="form-control" id="tanggal_dari" name="tanggal_dari" value="<?= htmlspecialchars($tanggal_dari) ?>">
                </div>
                <div class="form-group">
                    <label for="tanggal_sampai">Tanggal <br> Sampai:</label>
                    <input type="date" class="form-control" name="tanggal_sampai" value="<?= htmlspecialchars($tanggal_sampai) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </div>
        </form>

        <span class="date-display">Rekap Presensi Tanggal: <?= date('d F Y', strtotime($tanggal_dari)) . ' sampai ' . date('d F Y', strtotime($tanggal_sampai)) ?></span>

        <table class="table table-bordered mt-2">
            <thead>
                <tr class="text-center">
                    <th>Tanggal<br>Masuk</th>
                    <th>Jam<br>Masuk</th>
                    <th>Tanggal<br>Pulang</th>
                    <th>Jam<br>Pulang</th>
                    <th>Terlambat</th>
                    <th>Pulang<br>Awal</th>
                    <th>Jam<br>Kerja</th>
                    <th>Shift</th>
                    <th style="width: 200px;">Foto<br>Masuk</th>
                    <th style="width: 200px;">Foto<br>Pulang</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0) { ?>
                    <tr>
                        <td colspan="10" class="text-center">Belum ada data</td>
                    </tr>
                <?php } else { ?>
                    <?php while ($rekap = $result->fetch_assoc()) : ?>
                        <?php
                        // menghitung total jam kerja
                        $jam_tanggal_masuk = date('Y-m-d H:i:s', strtotime($rekap['tanggal_masuk'] . '' . $rekap['jam_masuk']));
                        $jam_tanggal_keluar = date('Y-m-d H:i:s', strtotime($rekap['tanggal_keluar'] . '' . $rekap['jam_keluar']));

                        $timestamp_masuk = strtotime($jam_tanggal_masuk);
                        $timestamp_keluar = strtotime($jam_tanggal_keluar);

                        $selisih = $timestamp_keluar - $timestamp_masuk;

                        $total_jam_kerja = floor($selisih / 3600);
                        $selisih -= $total_jam_kerja * 3600;
                        $selisih_menit_kerja = floor($selisih / 60);

                        $total_kerja += max(0, $timestamp_keluar - $timestamp_masuk);

                        if ($rekap['role'] == 'pegawai') {
                            // Calculate total late hours
                            $jam_presensi = $rekap['lokasi_presensi'];
                            $jam_query = "SELECT * FROM jam_kerja WHERE id = 1";
                            $jam_stmt = $connection->prepare($jam_query);
                            // NOTE: Removed redundant bind_param and execute here as it seems to be placeholder logic
                            $jam_stmt->execute();
                            $jam_result = $jam_stmt->get_result()->fetch_assoc();

                            // Extract day of the week from tanggal_masuk
                            $shift = date('N', strtotime($rekap['tanggal_masuk']));

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
                            $total_terlambat += max(0, $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor);

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
                            // NOTE: Removed redundant bind_param and execute here
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
                            $total_terlambat += max(0, $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor);

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
                            // Accumulate the early time to the total only if $timestamp_jam_pulang_real is positive
                            if (!empty($rekap['tanggal_keluar']) && $timestamp_jam_pulang_real > 0) {
                                $total_awal += max(0, $timestamp_jam_pulang_kantor - $timestamp_jam_pulang_real);
                            }
                        }


                        // Determine the photo paths
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
                            <td class="text-center"><?= date('d F Y', strtotime($rekap['tanggal_masuk'])) ?></td>
                            <td class="text-center"><?= htmlspecialchars($rekap['jam_masuk']) ?></td>
                            <td><?= !empty($rekap['tanggal_keluar']) ? date('d F Y', strtotime($rekap['tanggal_keluar'])) : '' ?></td>
                            <td class="text-center"><?= htmlspecialchars($rekap['jam_keluar']) ?></td>
                            <td class="text-center">
                                <?= (isset($total_jam_terlambat) && $total_jam_terlambat <= 0 && !empty($rekap['jam_masuk'])) ? '<span class="badge bg-success">On Time</span>' : (!empty($rekap['jam_masuk']) ? '<span style="color: red; font-weight: bold;">' . $total_jam_terlambat . ' Jam ' . $selisih_menit_terlambat . ' Menit</span>' : '') ?>
                            </td>
                            <td class="text-center">
                                <?= !empty($rekap['tanggal_keluar']) ? ((isset($total_jam_awal) && $total_jam_awal <= 0) ? '<span class="badge bg-success">On Time</span>' : '<span style="color: red; font-weight: bold;">' . $total_jam_awal . ' Jam ' . $selisih_menit_awal . ' Menit</span>') : '' ?>
                            </td>
                            <td class="text-center">
                                <?= !empty($rekap['tanggal_keluar']) ? (($total_jam_kerja <= 0 && $selisih_menit_kerja <= 0) ? '<span class="badge bg-success">---</span>' : '<span style="color: green; font-weight: bold;">' . $total_jam_kerja . ' Jam ' . $selisih_menit_kerja . ' Menit</span>') : '' ?>
                            </td>
                            <td><?= htmlspecialchars($rekap['lokasi_presensi']) ?></td>
                            <td>
                                <img class="img-fluid" style="width: 100%; border-radius: 20px" src="<?= $foto_masuk ?>" alt="Foto Masuk">
                            </td>
                            <td>
                                <img class="img-fluid" style="width: 100%; border-radius: 20px" src="<?= $foto_keluar ?>" alt="Foto Pulang">
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr class="text-center">
                    <td colspan="4"><strong>Total</strong></td>
                    <td><strong>
                            <?php
                            if (isset($total_terlambat) && $total_terlambat > 0) {
                                $hours = floor($total_terlambat / 3600);
                                $minutes = floor(($total_terlambat % 3600) / 60);
                                echo $hours . ' Jam ' . $minutes . ' Menit';
                            } else {
                                echo 'On Time';
                            }
                            ?>
                        </strong></td>
                    <td><strong>
                            <?php
                            if (isset($total_awal) && $total_awal > 0) {
                                $hours = floor($total_awal / 3600);
                                $minutes = floor(($total_awal % 3600) / 60);
                                echo $hours . ' Jam ' . $minutes . ' Menit';
                            } else {
                                echo 'On Time';
                            }
                            ?>
                        </strong></td>
                    <td><strong>
                            <?php
                            $hours = floor($total_kerja / 3600);
                            $minutes = floor(($total_kerja % 3600) / 60);
                            echo $hours . ' Jam ' . $minutes . ' Menit';
                            ?>
                        </strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
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
            <form method="POST" action="/admin/presensi/rekap_harian_excel.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="">Tanggal Awal</label>
                        <input type="date" class="form-control" name="tanggal_dari">
                    </div>
                    <div class="mb-3">
                        <label for="">Tanggal Akhir</label>
                        <input type="date" class="form-control" name="tanggal_sampai">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" data-bs-dismiss="modal">Ekspor</button>
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
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        const isNumeric = !isNaN(parseFloat(rows[0].querySelectorAll('td')[column].innerText));

        rows.sort((a, b) => {
            const aValue = isNumeric ? parseFloat(a.querySelectorAll('td')[column].innerText) : a.querySelectorAll('td')[column].innerText;
            const bValue = isNumeric ? parseFloat(b.querySelectorAll('td')[column].innerText) : b.querySelectorAll('td')[column].innerText;
            return order === 'asc' ? aValue > bValue ? 1 : -1 : aValue < bValue ? 1 : -1;
        });

        tbody.innerHTML = '';

        rows.forEach(row => {
            tbody.appendChild(row);
        });
    }
</script>