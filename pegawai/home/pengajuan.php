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

$judul = "Pengajuan Izin";
include('../layout/header.php');
require_once('../../config.php');

// Check if the user has a name in the session
if (!isset($_SESSION["nama"])) {
    header("Location: ../../auth/login.php");
    exit;
}

$id_pengguna = $_SESSION["id"];

// Hapus filter tanggal pada query dan form input tanggal
$query = "SELECT absensi.*, pegawai.nama, users.role  
          FROM absensi 
          JOIN pegawai ON absensi.id_pegawai = pegawai.id
          JOIN users on users.id_pegawai = pegawai.id  
          WHERE absensi.id_pegawai = ?
          ORDER BY tanggal_absen DESC";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $id_pengguna);
$stmt->execute();
$result = $stmt->get_result();

$bulan = $tanggal_dari . ' - ' . $tanggal_sampai;
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
                <!-- Filter tanggal dihapus -->
            </form>

            <!-- <span>Rekap Absensi</span> -->

            <table class="table table-bordered mt-2">
                <thead>
                    <tr class="text-center">
                        <th>No</th>
                        <th>Nama</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Keterangan</th>
                        <th>Status</th>
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
                    echo '</tr>';
                }
                if ($result->num_rows == 0) {
                    echo '<tr><td colspan="6" class="text-center">Tidak ada data</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include('../layout/footer.php'); ?>
</body>

</html>