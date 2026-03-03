<?php
session_start();
ob_start();

// Check if the user is logged in and has the 'pegawai' role
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
    exit;
} elseif ($_SESSION["role"] != "shift") {
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $judul ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .page-body {
            padding: 20px;
        }

        .container-xl {
            max-width: 1200px;
            margin: 0 auto;
        }

        .input-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .input-group .form-group {
            flex-grow: 1;
            min-width: 150px;
        }

        .input-group .form-group label {
            margin-bottom: 5px;
        }

        .input-group .form-group .btn {
            align-self: flex-start;
            margin-top: 26px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .table th {
            background-color: #f4f4f4;
        }

        @media (max-width: 768px) {
            .input-group {
                flex-direction: column;
            }

            .input-group .form-group {
                margin-bottom: 10px;
            }
        }

        .modal-content {
            max-width: 100%;
            margin: auto;
            padding: 20px;
        }

        .modal-header,
        .modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            cursor: pointer;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            color: #fff;
            background-color: #007bff;
            border-radius: 5px;
        }

        .bg-success {
            background-color: #28a745;
        }

        .text-center {
            text-align: center;
        }

        .mt-2 {
            margin-top: 20px;
        }
    </style>
</head>

<body>
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

            <span>Rekap Presensi Tanggal: <?= date('d F Y', strtotime($tanggal_dari)) . ' sampai ' . date('d F Y', strtotime($tanggal_sampai)) ?></span>

            <table class="table table-bordered mt-2">
                <thead>
                    <tr class="text-center">
                        <th>Tanggal<br>Masuk</th>
                        <th>Jam<br>Masuk</th>
                        <th>Tanggal<br>Pulang</th>
                        <th>Jam<br>Pulang</th>
                        <th>Terlambat</th>
                        <th>Pulang<br>Awal</th>
                        <th>Foto<br>Masuk</th>
                        <th>Foto<br>Pulang</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0) { ?>
                        <tr>
                            <td colspan="6">Belum ada data</td>
                        </tr>
                    <?php } else { ?>
                        <?php while ($rekap = $result->fetch_assoc()) : ?>
                            <?php
                            // Calculate total working hours
                            $jam_tanggal_masuk = strtotime($rekap['tanggal_masuk'] . ' ' . $rekap['jam_masuk']);
                            $jam_tanggal_keluar = strtotime($rekap['tanggal_keluar'] . ' ' . $rekap['jam_keluar']);
                            $selisih = $jam_tanggal_keluar - $jam_tanggal_masuk;
                            $total_jam_kerja = floor($selisih / 3600);
                            $selisih_menit_kerja = floor(($selisih % 3600) / 60);

                            // Calculate total late time
                            // $lokasi_presensi = $rekap['lokasi_presensi'];
                            // $lokasi_query = "SELECT * FROM lokasi_presensi WHERE nama_lokasi = ?";
                            // $lokasi_stmt = $connection->prepare($lokasi_query);
                            // $lokasi_stmt->bind_param('s', $lokasi_presensi);
                            // $lokasi_stmt->execute();
                            // $lokasi_result = $lokasi_stmt->get_result()->fetch_assoc();
                            // $jam_masuk_kantor = strtotime($lokasi_result['jam_masuk']);
                            // $jam_masuk_real = strtotime($rekap['jam_masuk']);
                            // $terlambat = $jam_masuk_real - $jam_masuk_kantor;
                            // $total_jam_terlambat = floor($terlambat / 3600);
                            // $selisih_menit_terlambat = floor(($terlambat % 3600) / 60);

                            if ($rekap['role'] != 'shift') {
                                // Calculate total late hours
                                $lokasi_presensi = $rekap['lokasi_presensi'];
                                $lokasi_query = "SELECT * FROM lokasi_presensi WHERE nama_lokasi = ?";
                                $lokasi_stmt = $connection->prepare($lokasi_query);
                                $lokasi_stmt->bind_param("s", $lokasi_presensi);
                                $lokasi_stmt->execute();
                                $lokasi_result = $lokasi_stmt->get_result()->fetch_assoc();
                                $jam_masuk_kantor = date('H:i:s', strtotime($lokasi_result['jam_masuk']));

                                $jam_masuk = date('H:i:s', strtotime($rekap['jam_masuk']));
                                $timestamp_jam_masuk_real = strtotime($jam_masuk);
                                $timestamp_jam_masuk_kantor = strtotime($jam_masuk_kantor);

                                $terlambat = $timestamp_jam_masuk_real - $timestamp_jam_masuk_kantor;
                                $total_jam_terlambat = floor($terlambat / 3600);
                                $terlambat -= $total_jam_terlambat * 3600;
                                $selisih_menit_terlambat = floor($terlambat / 60);

                                // Calculate total early hours
                                $jam_pulang_kantor = date('H:i:s', strtotime($lokasi_result['jam_pulang']));

                                $jam_pulang = date('H:i:s', strtotime($rekap['jam_keluar']));
                                $timestamp_jam_pulang_real = strtotime($jam_pulang);
                                $timestamp_jam_pulang_kantor = strtotime($jam_pulang_kantor);

                                $awal = $timestamp_jam_pulang_kantor - $timestamp_jam_pulang_real;
                                $total_jam_awal = floor($awal / 3600);
                                $awal -= $total_jam_awal * 3600;
                                $selisih_menit_awal = floor($awal / 60);
                            }

                            // Accumulate total late time
                            // if ($terlambat > 0) {
                            //     $total_terlambat += $terlambat;
                            // }

                            if ($total_jam_terlambat > 0 || $selisih_menit_terlambat > 0) {
                                $total_terlambat += $total_jam_terlambat * 3600 + $selisih_menit_terlambat * 60; // Accumulate total late time
                            }

                            if ($total_jam_awal > 0 || $selisih_menit_awal > 0) {
                                $total_awal += $total_jam_awal * 3600 + $selisih_menit_awal * 60; // Accumulate total early time
                            }

                            // Determine the photo paths
                            $foto_nama_masuk = htmlspecialchars($rekap['foto_masuk']);
                            $foto_masuk_path_shift = $_SERVER['DOCUMENT_ROOT'] . "/shift/presensi/foto/" . $foto_nama_masuk;
                            $foto_masuk_path_pegawai = $_SERVER['DOCUMENT_ROOT'] . "/pegawai/presensi/foto/" . $foto_nama_masuk;

                            if (file_exists($foto_masuk_path_shift)) {
                                $foto_masuk = "/shift/presensi/foto/" . $foto_nama_masuk;
                            } elseif (file_exists($foto_masuk_path_pegawai)) {
                                $foto_masuk = "/pegawai/presensi/foto/" . $foto_nama_masuk;
                            } else {
                                $foto_masuk = "https://internal.pdamkotamagelang.com/shift/presensi/foto/" . $foto_nama_masuk;
                            }

                            $foto_nama_keluar = htmlspecialchars($rekap['foto_keluar']);
                            $foto_keluar_path_shift = $_SERVER['DOCUMENT_ROOT'] . "/shift/presensi/foto/" . $foto_nama_keluar;
                            $foto_keluar_path_pegawai = $_SERVER['DOCUMENT_ROOT'] . "/pegawai/presensi/foto/" . $foto_nama_keluar;

                            if (file_exists($foto_keluar_path_shift)) {
                                $foto_keluar = "/shift/presensi/foto/" . $foto_nama_keluar;
                            } elseif (file_exists($foto_keluar_path_pegawai)) {
                                $foto_keluar = "/pegawai/presensi/foto/" . $foto_nama_keluar;
                            } else {
                                $foto_keluar = "https://internal.pdamkotamagelang.com/shift/presensi/foto/" . $foto_nama_keluar;
                            }

                            ?>
                            <tr>
                                <td><?= date('d F Y', strtotime($rekap['tanggal_masuk'])) ?></td>
                                <td class="text-center"><?= htmlspecialchars($rekap['jam_masuk']) ?></td>
                                <td><?= htmlspecialchars($rekap['tanggal_keluar']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($rekap['jam_keluar']) ?></td>
                                <td class="text-center">
                                    <?= $total_jam_terlambat < 0 ? '<span class="badge bg-success">On Time</span>' : '<span style="color: red; font-weight: bold;">' . $total_jam_terlambat . ' Jam ' . $selisih_menit_terlambat . ' Menit</span>' ?>
                                </td>
                                <td class="text-center">
                                    <?= $total_jam_awal < 0 ? '<span class="badge bg-success">On Time</span>' : '<span style="color: red; font-weight: bold;">' . $total_jam_awal . ' Jam ' . $selisih_menit_awal . ' Menit</span>' ?>
                                </td>
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
                                if ($total_terlambat > 0) {
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
                                if ($total_awal > 0) {
                                    $hours = floor($total_awal / 3600);
                                    $minutes = floor(($total_awal % 3600) / 60);
                                    echo $hours . ' Jam ' . $minutes . ' Menit';
                                } else {
                                    echo 'On Time';
                                }
                                ?>
                            </strong></td>
                        <td colspan="2"></td>
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
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            const isNumeric = !isNaN(parseFloat(rows[0].querySelectorAll('td')[column].innerText));

            rows.sort((a, b) => {
                const aValue = isNumeric ? parseFloat(a.querySelectorAll('td')[column].innerText) : a.querySelectorAll('td')[column].innerText;
                const bValue = isNumeric ? parseFloat(b.querySelectorAll('td')[column].innerText) : b.querySelectorAll('td')[column].innerText;
                return order === 'asc' ? aValue - bValue : bValue - aValue;
            });

            tbody.innerHTML = '';

            rows.forEach(row => {
                tbody.appendChild(row);
            });
        }
    </script>
</body>

</html>