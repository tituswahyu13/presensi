<script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js" integrity="sha512-dQIiHSl2hr3NWKKLycPndtpbh5iaHLo6MwrXm7F0FM5e+kL2U16oE9uIwPHUl6fQBeCthiEuV/rzP3MiAB8Vfw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
    #map {
        height: 300px;
        margin-top: 20px;
    }

    .mirrored {
        transform: scaleX(-1);
    }

    .container-xl {
        margin-top: 0;
        padding-top: 0;
    }

    .btn-circle-container {
        margin-top: 10px;
        display: flex;
        justify-content: center;
    }

    .camera-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        overflow: hidden;
        margin-top: -40px;
    }

    .card-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: -20px;
        margin-top: -40px;
    }

    #my_camera {
        width: 100%;
        height: auto;
    }

    .text-points {
        text-align: left;
        margin-bottom: 10px;
    }

    .warning-text {
        color: red;
        font-weight: bold;
    }
</style>
<?php
ob_start();
session_start();

if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "tidar") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}
include('../layout/header.php');
include_once("../../config.php");

if (isset($_POST['tombol_masuk'])) {
    $latitude_pegawai = $_POST['latitude_pegawai'];
    $longitude_pegawai = $_POST['longitude_pegawai'];
    $latitude_kantor = $_POST['latitude_kantor'];
    $longitude_kantor = $_POST['longitude_kantor'];
    $radius = $_POST['radius'];
    $zona_waktu = $_POST['zona_waktu'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $jam_masuk = $_POST['jam_masuk'];
}

if (empty($latitude_pegawai) || empty($longitude_pegawai)) {
    $_SESSION['gagal'] = "Presensi gagal, GPS belum aktif";
    header("Location: ../home/tidar.php");
    exit;
}

if (empty($latitude_kantor) || empty($longitude_kantor)) {
    $_SESSION['gagal'] = "Presensi gagal, koordinat kantor belum diterapkan";
    header("Location: ../home/tidar.php");
    exit;
}

$perbedaan_koordinat = $longitude_pegawai - $longitude_kantor;
$jarak = sin(deg2rad($latitude_pegawai)) * sin(deg2rad($latitude_kantor)) + cos(deg2rad($latitude_pegawai)) * cos(deg2rad($latitude_kantor)) * cos(deg2rad($perbedaan_koordinat));
$jarak = acos($jarak);
$jarak = rad2deg($jarak);
$mil = $jarak * 60 * 1.1515;
$jarak_km = $mil * 1.609344;
$jarak_meter = $jarak_km * 1000;

?>

<?php if ($jarak_meter > $radius) { ?>
    <?=
    $_SESSION['gagal'] = "Anda berada di luar area kantor";
    header("Location: ../home/tidar.php");
    exit; ?>

<?php } else { ?>

    <div class="page-body">
        <div class="container-xl">
            <div class="col">
                <div class="col-md-6">
                    <div class="card text-center">
                        <div class="card-body camera-container">
                            <br /><br />
                            <div class="text-points">
                                <p>1. Pastikan wajah anda terlihat dengan jelas.</p>
                                <p>2. Pastikan latar belakang foto terlihat.</p>
                                <p class="warning-text">Apabila foto tidak memenuhi syarat, presensi dianggap tidak berlaku.</p>
                            </div>
                            <input type="hidden" id="id" value="<?= $_SESSION['id'] ?>">
                            <input type="hidden" id="tanggal_masuk" value="<?= $tanggal_masuk ?>">
                            <input type="hidden" id="jam_masuk" value="<?= $jam_masuk ?>">
                            <div id="my_camera" class="mirrored"></div>
                            <!-- <div id="my_result"></div> -->
                            <div><?= date('d F Y', strtotime($tanggal_masuk)) . ' - ' . $jam_masuk ?></div>
                            <br>
                            <div class="btn-circle-container"><button class="btn-circle" id="ambil-foto"><i class="fas fa-fingerprint"></i></button></div>
                        </div>
                    </div>
                </div>
                <div id="my_result"></div>
            </div>
        </div>
    </div>

    <script language="JavaScript">
        Webcam.set({
            width: 280,
            height: 360,
            dest_width: 280,
            dest_height: 360,
            image_format: 'jpeg',
            jpeg_quality: 100,
            force_flash: false
        });
        Webcam.attach('#my_camera');

        document.getElementById('ambil-foto').addEventListener('click', function() {

            let id = document.getElementById('id').value;
            let tanggal_masuk = document.getElementById('tanggal_masuk').value;
            let jam_masuk = document.getElementById('jam_masuk').value;

            Webcam.snap(function(data_uri) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    document.getElementById('my_result').innerHTML = '<img src="' + data_uri + '"/>';
                    if (xhttp.readyState == 4 && xhttp.status == 200) {
                        window.location.href = '../home/tidar.php';
                    }
                };
                xhttp.open("POST", "presensi_masuk_d_aksi.php", true);
                xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
                xhttp.send(
                    'photo=' + encodeURIComponent(data_uri) +
                    '&id=' + id +
                    '&tanggal_masuk=' + tanggal_masuk +
                    '&jam_masuk=' + jam_masuk
                );
            });
        });

        // map leaflet js

        let latitude_ktr = <?= $latitude_kantor ?>;
        let longitude_ktr = <?= $longitude_kantor ?>;
        let latitude_peg = <?= $latitude_pegawai ?>;
        let longitude_peg = <?= $longitude_pegawai ?>;

        let map = L.map('map').setView([latitude_ktr, longitude_ktr], 19);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var marker = L.marker([latitude_peg, longitude_peg]).addTo(map).bindPopup("Posisi anda saat ini").openPopup();;

        var circle = L.circle([latitude_ktr, longitude_ktr], {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.5,
            radius: 40
        }).addTo(map);
    </script>
    <!-- <a href="javascript:void(take_snapshot())">Take Snapshot</a> -->


<?php } ?>


<?php include('../layout/footer.php'); ?>