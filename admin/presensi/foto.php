<?php
session_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
    exit; // Ensure script stops execution after redirection
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
    exit; // Ensure script stops execution after redirection
}

$judul = "Foto Presensi";
include('../layout/header.php');
require_once('../../config.php');

$id = $_GET["id"];
$result = mysqli_query($connection, "SELECT foto_masuk, foto_keluar, jam_masuk, jam_keluar, tanggal_masuk, tanggal_keluar FROM presensi WHERE id = $id");

// Check if query execution was successful
if ($result === false) {
    die("Error fetching data: " . mysqli_error($connection));
}

// Fetch the row from the result set
$presensi = mysqli_fetch_assoc($result);

// Check if the row exists
if (!$presensi) {
    die("Presensi not found");
}

// Close the result set as it's not needed anymore
mysqli_free_result($result);
?>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <br>
                    <?php
                    $foto_nama_masuk = htmlspecialchars($presensi['foto_masuk']);
                    $foto_masuk_path_pegawai = $_SERVER['DOCUMENT_ROOT'] . "/pegawai/presensi/foto/" . $foto_nama_masuk;
                    $foto_masuk_path_shift = $_SERVER['DOCUMENT_ROOT'] . "/shift/presensi/foto/" . $foto_nama_masuk;

                    if (file_exists($foto_masuk_path_pegawai)) {
                        $foto_masuk = "/pegawai/presensi/foto/" . $foto_nama_masuk;
                    } elseif (file_exists($foto_masuk_path_shift)) {
                        $foto_masuk = "/shift/presensi/foto/" . $foto_nama_masuk;
                    } else {
                        $foto_masuk = "https://internal.pdamkotamagelang.com/pegawai/presensi/foto/" . $foto_nama_masuk;
                    }

                    $foto_nama_keluar = htmlspecialchars($presensi['foto_keluar']);
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
                    <h3 class="text-center">Foto Masuk<br> <?= $presensi['tanggal_masuk'] ?><br> <?= $presensi['jam_masuk'] ?></h3>
                    <img style="width: 100%; border-radius: 20px" src="<?= $foto_masuk ?>" alt="Foto Masuk">
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <br>
                    <h3 class="text-center">Foto Pulang<br> <?= $presensi['tanggal_masuk'] ?><br> <?= $presensi['jam_keluar'] ?></h3>
                    <img style="width: 100%; border-radius: 20px" src="<?= $foto_keluar ?>" alt="Foto Pulang">
                </div>
            </div>

        </div>

        <!-- Button to go back -->
        <div class="row mt-4">
            <div class="col">
                <a href="javascript:history.go(-1);" class="btn btn-primary">Kembali</a>
            </div>
        </div>
    </div>
</div>

<?php include('../layout/footer.php'); ?>