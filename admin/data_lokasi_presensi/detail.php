<?php
session_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}

$judul = "Detail Lokasi Presensi";
include('../layout/header.php');
require_once('../../config.php');

$id = $_GET["id"];
$result = mysqli_query($connection, "SELECT * FROM lokasi_presensi WHERE id=$id");
?>

<?php while ($lokasi = mysqli_fetch_array($result)) : ?>

    <div class="page-body">
        <div class="container-xl">

            <div class="row-mt-4">
                <div class="row-md-6">
                    <div class="card">
                        <div class="card-body">
                            <table>
                                <tr>
                                    <td>Nama Lokasi</td>
                                    <td>: <?= $lokasi['nama_lokasi'] ?></td>
                                </tr>
                                <tr>
                                    <td>Alamat Lokasi</td>
                                    <td>: <?= $lokasi['alamat_lokasi'] ?></td>
                                </tr>
                                <tr>
                                    <td>Tipe Lokasi</td>
                                    <td>: <?= $lokasi['tipe_lokasi'] ?></td>
                                </tr>
                                <tr>
                                    <td>Lat</td>
                                    <td>: <?= $lokasi['latitude'] ?></td>
                                </tr>
                                <tr>
                                    <td>Long</td>
                                    <td>: <?= $lokasi['longitude'] ?></td>
                                </tr>
                                <tr>
                                    <td>Radius</td>
                                    <td>: <?= $lokasi['radius'] ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row-md-6">
                    <div class="card">
                        <div class="card-body">

                            <!-- <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d39682.97243081697!2d<?= $lokasi['longitude'] ?>!3d<?= $lokasi['latitude'] ?>!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sid!2sid!4v1709207530491!5m2!1sid!2sid" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe> -->

                            <div id="map" style="width: 100%; height: 400px;"></div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-center">
                    <a href="javascript:history.back()" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        var latitude = <?= $lokasi['latitude'] ?>;
        var longitude = <?= $lokasi['longitude'] ?>;

        var map = L.map('map').setView([latitude, longitude], 16);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        L.marker([latitude, longitude]).addTo(map)
            .bindPopup('<?= $lokasi['nama_lokasi'] ?>')
            .openPopup();

        // Konversi radius ke meter jika perlu dan validasi
        var radiusValue = <?= $lokasi['radius'] ?>;
        var radiusInMeters = radiusValue; // Asumsikan radius sudah dalam meter
        
        // Validasi radius minimum 10 meter dan maksimum 1000 meter
        if (radiusInMeters < 10) radiusInMeters = 10;
        if (radiusInMeters > 1000) radiusInMeters = 1000;

        L.circle([latitude, longitude], {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.3,
            radius: radiusInMeters
        }).addTo(map);

        // Tambahkan kontrol scale untuk referensi jarak
        L.control.scale().addTo(map);
    </script>

<?php endwhile; ?>