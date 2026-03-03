<?php
session_start();
ob_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}

$judul = "Tambah Lokasi Presensi";
include('../layout/header.php');
require_once('../../config.php');

if (isset($_POST['submit'])) {
    $nama_lokasi = htmlspecialchars($_POST['nama_lokasi']);
    $alamat_lokasi = htmlspecialchars($_POST['alamat_lokasi']);
    $tipe_lokasi = htmlspecialchars($_POST['tipe_lokasi']);
    $latitude = htmlspecialchars($_POST['latitude']);
    $longitude = htmlspecialchars($_POST['longitude']);
    $radius = htmlspecialchars($_POST['radius']);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (empty($nama_lokasi)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Nama lokasi wajib diisi";
        }
        if (empty($alamat_lokasi)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Alamat lokasi wajib diisi";
        }
        // if (empty($tipe_lokasi)) {
        //     $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Tipe lokasi wajib diisi";
        // }
        if (empty($latitude)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Latitude wajib diisi";
        }
        if (empty($longitude)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Longitude wajib diisi";
        }
        if (empty($radius)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Radius wajib diisi";
        }

        // Check if there are any validation errors
        if (!empty($pesan_kesalahan)) {
            $_SESSION['validasi'] = implode("<br>", $pesan_kesalahan);
        } else {
            // Perform the insertion into the database
            $result = mysqli_query(
                $connection,
                "INSERT INTO lokasi_presensi (nama_lokasi, alamat_lokasi, tipe_lokasi, latitude, longitude, radius)
        VALUES ('$nama_lokasi', '$alamat_lokasi', '$tipe_lokasi', '$latitude', '$longitude', '$radius')"
            );

            if (!$result) {
                // If there is an error with the query, display the error message
                echo "Error: " . mysqli_error($connection);
            } else {
                // If the query is successful, redirect the user or perform any other action
                $_SESSION['berhasil'] = 'Data berhasil disimpan';
                header("Location: lokasi_presensi.php");
                exit;
            }
        }
    }
}

?>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">

        <div class="card col md-6">
            <div class="card-body">
                <form action="/admin/data_lokasi_presensi/tambah.php" method="POST">
                    <div class="mb-3">
                        <label for="">Nama Lokasi</label>
                        <input type="text" class="form-control" name="nama_lokasi" value="<?php if (isset($_POST['nama_lokasi'])) echo $_POST['nama_lokasi'] ?>">
                    </div>

                    <div class="mb-3">
                        <label for="">Alamat Lokasi</label>
                        <input type="text" class="form-control" name="alamat_lokasi" value="<?php if (isset($_POST['alamat_lokasi'])) echo $_POST['alamat_lokasi'] ?>">
                    </div>

                    <!-- <div class="mb-3">
                        <label for="">Tipe Lokasi</label>
                        <select name="tipe_lokasi" class="form-control">
                            <option value="">--Pilih Tipe Lokasi--</option>
                            <option <?php if (isset($_POST['tipe_lokasi']) && $_POST['tipe_lokasi'] == 'Pusat') {
                                        echo 'selected';
                                    } ?> value="Pusat">Pusat</option>
                            <option <?php if (isset($_POST['tipe_lokasi']) && $_POST['tipe_lokasi'] == 'Sumber') {
                                        echo 'selected';
                                    } ?>value="Sumber">Sumber</option>
                        </select>
                    </div> -->

                    <div class="mb-3">
                        <label for="">Latitude</label>
                        <input type="text" class="form-control" name="latitude" value="<?php if (isset($_POST['latitude'])) echo $_POST['latitude'] ?>">
                    </div>

                    <div class="mb-3">
                        <label for="">Longitude</label>
                        <input type="text" class="form-control" name="longitude" value="<?php if (isset($_POST['longitude'])) echo $_POST['longitude'] ?>">
                    </div>

                    <div class="mb-3">
                        <label for="">Radius</label>
                        <input type="number" class="form-control" name="radius" value="<?php if (isset($_POST['radius'])) echo $_POST['radius'] ?>">
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary" name="submit">Simpan</button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php include('../layout/footer.php'); ?>