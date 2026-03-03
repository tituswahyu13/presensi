<?php
session_start();
ob_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
    exit;
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
    exit;
}

$judul = "Edit Data Lokasi Presensi";
include('../layout/header.php');
require_once('../../config.php');

if (isset($_POST["update"])) {
    $id = $_POST['id'];
    // $nama_lokasi = htmlspecialchars($_POST['nama_lokasi']);
    $alamat_lokasi = htmlspecialchars($_POST['alamat_lokasi']);
    $latitude = htmlspecialchars($_POST['latitude']);
    $longitude = htmlspecialchars($_POST['longitude']);
    $radius = htmlspecialchars($_POST['radius']);

    $pesan_kesalahan = [];

    // if (empty($nama_lokasi)) {
    //     $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Nama lokasi wajib diisi";
    // }
    if (empty($alamat_lokasi)) {
        $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Alamat lokasi wajib diisi";
    }
    if (empty($latitude)) {
        $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Latitude wajib diisi";
    }
    if (empty($longitude)) {
        $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Longitude wajib diisi";
    }
    if (empty($radius)) {
        $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Radius wajib diisi";
    }

    if (!empty($pesan_kesalahan)) {
        $_SESSION['validasi'] = implode("<br>", $pesan_kesalahan);
    } else {
        $query = "UPDATE lokasi_presensi SET 
                    alamat_lokasi='$alamat_lokasi',
                    latitude='$latitude',
                    longitude='$longitude',
                    radius='$radius'
                  WHERE id=$id";

        $result = mysqli_query($connection, $query);

        if ($result) {
            $_SESSION['berhasil'] = "Data berhasil diupdate";
            header("Location: lokasi_presensi.php");
            exit;
        } else {
            $_SESSION['validasi'] = "Data gagal diupdate: " . mysqli_error($connection);
        }
    }
}

$id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
$result = mysqli_query($connection, "SELECT * FROM lokasi_presensi WHERE id=$id");

if ($lokasi = mysqli_fetch_array($result)) {
    // $nama_lokasi = $lokasi['nama_lokasi'];
    $alamat_lokasi = $lokasi['alamat_lokasi'];
    $latitude = $lokasi['latitude'];
    $longitude = $lokasi['longitude'];
    $radius = $lokasi['radius'];
}
?>
<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <form action="/admin/data_lokasi_presensi/edit.php" method="POST">
            <div class="row">
                <div class="card col-md-6">
                    <div class="card-body">
                        <!-- <div class="mb-3">
                            <label for="">Nama Lokasi <span style="font-style: italic; opacity: 0.7">(jangan diubah)</span></label>
                            <input type="text" class="form-control" name="nama_lokasi" value="<?= htmlspecialchars($nama_lokasi) ?>">
                        </div> -->
                        <div class="mb-3">
                            <label for="">Alamat Lokasi <span style="font-style: italic; opacity: 0.7">(nama event)</span></label>
                            <input type="text" class="form-control" name="alamat_lokasi" value="<?= htmlspecialchars($alamat_lokasi) ?>">
                        </div>
                    </div>
                </div>
                <div class="card col-md-6">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="">Latitude</label>
                            <input type="text" class="form-control" name="latitude" value="<?= htmlspecialchars($latitude) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Longitude</label>
                            <input type="text" class="form-control" name="longitude" value="<?= htmlspecialchars($longitude) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Radius</label>
                            <input type="text" class="form-control" name="radius" value="<?= htmlspecialchars($radius) ?>">
                        </div>
                        <input type="hidden" value="<?= htmlspecialchars($id) ?>" name="id">
                        <button type="submit" name="update" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </div>
        </form>


    </div>
</div>
<?php include('../layout/footer.php'); ?>