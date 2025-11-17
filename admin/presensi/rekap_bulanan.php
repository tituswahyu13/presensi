<?php
ob_start();
session_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}

$judul = "Rekap Presensi Bulanan";
include('../layout/header.php');
include_once('../../config.php');

if (empty($_GET["filter_bulan"])) {
    $bulan_sekarang = date("Y-m");
    $id = $_GET["id"];
    $result = mysqli_query($connection, "SELECT presensi.*, pegawai.nama, pegawai.lokasi_presensi 
    FROM presensi JOIN pegawai ON presensi.id_pegawai = pegawai.id 
    WHERE DATE_FORMAT(tanggal_masuk, '%Y-%m') = '$bulan_sekarang'
    ORDER BY tanggal_masuk DESC");
} else {
    $tahun_bulan = $_GET["filter_tahun"] . '-' . $_GET["filter_bulan"];
    $id = $_GET["id"];
    $result = mysqli_query($connection, "SELECT presensi.*, pegawai.nama, pegawai.lokasi_presensi 
    FROM presensi JOIN pegawai ON presensi.id_pegawai = pegawai.id 
    WHERE DATE_FORMAT(tanggal_masuk, '%Y-%m') LIKE '$tahun_bulan%'
    ORDER BY tanggal_masuk DESC");
}

if (empty($_GET['filter_bulan'])) {
    $bulan = date('Y-m');
} else {
    $bulan = $_GET['filter_tahun'] . '-' . $_GET['filter_bulan'];
}

// Modify the SQL query to include the search condition for name only
$search_nama = isset($_GET['search_nama']) ? $_GET['search_nama'] : '';
$query = "SELECT presensi.*, pegawai.nama, pegawai.lokasi_presensi 
          FROM presensi JOIN pegawai ON presensi.id_pegawai = pegawai.id 
          WHERE pegawai.nama LIKE '%$search_nama%'";

// Execute the modified query
$result = mysqli_query($connection, $query);

echo $bulan_sekarang;
echo $tahun_bulan;

?>

<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-md-12">
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="search_nama" class="form-control" placeholder="Search by Name">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-2">
                <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    Export Excel
                </button>
            </div>
            <div class="col-md-10">
                <form method="GET">
                    <div class="input-group">
                        <select name="filter_bulan" class="form-control">
                            <option value="">--Pilih Bulan--</option>
                            <option value="01">Januari</option>
                            <option value="02">Februari</option>
                            <option value="03">Maret</option>
                            <option value="04">April</option>
                            <option value="05">Mei</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">Agustus</option>
                            <option value="09">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                        <select name="filter_tahun" class="form-control">
                            <option value="">--Pilih Tahun--</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                            <option value="2028">2028</option>
                            <option value="2029">2029</option>
                            <option value="2030">2030</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <span>Rekap Presensi Bulan: <?= date('F Y', strtotime($bulan)) ?></span>
        <table class="table table-bordered mt-2">
            <thead>
                <tr class="text-center">
                    <!-- <th>No. </th> -->
                    <th>Nama <button class="sort-btn" data-column="1">▲▼</button></th>
                    <th>Tanggal <button class="sort-btn" data-column="2">▲▼</button></th>
                    <th>Jam Masuk <button class="sort-btn" data-column="3">▲▼</button></th>
                    <th>Jam Pulang <button class="sort-btn" data-column="4">▲▼</button></th>
                    <th>Total Jam <button class="sort-btn" data-column="5">▲▼</button></th>
                    <th>Total Terlambat <button class="sort-btn" data-column="6">▲▼</button></th>
                    <th>Foto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) === 0) { ?>
                    <tr>
                        <td colspan="6"> Belum ada data </td>
                    </tr>
                <?php } else { ?>

                    <?php $no = 1;
                    while ($rekap = mysqli_fetch_array($result)) :

                        // menghitung total jam kerja
                        $jam_tanggal_masuk = date('Y-m-d H:i:s', strtotime($rekap['tanggal_masuk'] . '' . $rekap['jam_masuk']));
                        $jam_tanggal_keluar = date('Y-m-d H:i:s', strtotime($rekap['tanggal_keluar'] . '' . $rekap['jam_keluar']));

                        $timestamp_masuk = strtotime($jam_tanggal_masuk);
                        $timestamp_keluar = strtotime($jam_tanggal_keluar);

                        $selisih = $timestamp_keluar - $timestamp_masuk;

                        $total_jam_kerja = floor($selisih / 3600);
                        $selisih -= $total_jam_kerja * 3600;
                        $selisih_menit_kerja = floor($selisih / 60);

                        // menghitung total terlambat

                        $lokasi_presensi = $rekap['lokasi_presensi'];
                        $lokasi = mysqli_query($connection, "SELECT * FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_presensi'");

                        while ($lokasi_result = mysqli_fetch_array($lokasi)) :
                            $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk']));
                        endwhile;

                        $jam_masuk = date('H:i:s', strtotime($rekap['jam_masuk']));
                        $timestamp_jam_masuk_real = strtotime($jam_masuk);
                        $timestamp_jam_masuk_kantor = strtotime($jam_masuk_kantor);

                        $terlambat = $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor;
                        $total_jam_terlambat = floor($terlambat / 3600);
                        $terlambat -= $total_jam_terlambat * 3600;
                        $selisih_menit_terlambat = floor($terlambat / 60);
                    ?>

                        <tr>
                            <!-- <td><?= $no++ ?></td> -->
                            <td><?= $rekap['nama'] ?></td>
                            <td><?= date('d F Y', strtotime($rekap['tanggal_masuk'])) ?></td>
                            <td class="text-center"><?= $rekap['jam_masuk'] ?></td>
                            <td class="text-center"><?= $rekap['jam_keluar'] ?></td>
                            <td class="text-center">
                                <?php if ($rekap['tanggal_keluar'] === '0000-00-00') : ?>
                                    <span>0 Jam 0 Menit</span>
                                <?php else : ?>
                                    <?= $total_jam_kerja . 'Jam' . $selisih_menit_kerja . 'Menit' ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($total_jam_terlambat < 0) : ?>
                                    <span class="badge bg-success">On Time</span>
                                <?php else : ?>
                                    <span style="color: red; font-weight: bold;">
                                        <?= $total_jam_terlambat . 'Jam' . $selisih_menit_terlambat . 'Menit' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="/absensi/admin/presensi/foto.php?id=<?= $rekap['id'] ?>" class="badge badge-pill bg-primary">Foto</a>
                                <a href="/absensi/admin/presensi/rekap.php?id=<?= $rekap['id_pegawai'] ?>" class="badge badge-pill bg-primary">Rekap</a>
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
                <h5 class="modal-title">Ekspor Excel Rekap Bulanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/absensi/admin/presensi/rekap_bulanan_excel.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="">Bulan</label>
                        <select name="filter_bulan" class="form-control">
                            <option value="">--Pilih Bulan--</option>
                            <option value="01">Januari</option>
                            <option value="02">Februari</option>
                            <option value="03">Maret</option>
                            <option value="04">April</option>
                            <option value="05">Mei</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">Agustus</option>
                            <option value="09">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="">Tahun</label>
                        <select name="filter_tahun" class="form-control">
                            <option value="">--Pilih Tahun--</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                            <option value="2028">2028</option>
                            <option value="2029">2029</option>
                            <option value="2030">2030</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn me-primary" data-bs-dismiss="modal">Ekspor</button>
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
        const rows = Array.from(table.querySelectorAll('tr'));
        const isNumeric = !isNaN(rows[1].querySelectorAll('td')[column].innerText);
        const sortedRows = rows.slice(1).sort((a, b) => {
            const aValue = isNumeric ? parseFloat(a.querySelectorAll('td')[column].innerText) : a.querySelectorAll('td')[column].innerText;
            const bValue = isNumeric ? parseFloat(b.querySelectorAll('td')[column].innerText) : b.querySelectorAll('td')[column].innerText;
            return order === 'asc' ? aValue > bValue ? 1 : -1 : aValue < bValue ? 1 : -1;
        });

        table.querySelector('tbody').innerHTML = '';
        sortedRows.forEach(row => {
            table.querySelector('tbody').appendChild(row);
        });
    }
</script>