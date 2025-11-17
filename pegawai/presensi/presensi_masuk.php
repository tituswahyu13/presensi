<?php
ob_start();
session_start();

if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "pegawai") {
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
    header("Location: ../home/home.php");
    exit;
}

if (empty($latitude_kantor) || empty($longitude_kantor)) {
    $_SESSION['gagal'] = "Presensi gagal, koordinat kantor belum diterapkan";
    header("Location: ../home/home.php");
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js" integrity="sha512-dQIiHSl2hr3NWKKLycPndtpbh5iaHLo6MwrXm7F0FM5e+kL2U16oE9uIwPHUl6fQBeCthiEuV/rzP3MiAB8Vfw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">


<style>
    :root {
        --primary-color: #00e0b3; /* Hijau neon */
        --secondary-color: #00a4d4; /* Biru elektrik */
        --bg-color: #0a0a0d;
        --card-bg: rgba(18, 18, 25, 0.7);
        --text-color: #e0e0e0;
        --border-color: rgba(0, 224, 179, 0.3);
        --glow-color: rgba(0, 224, 179, 0.5);
    }
    
    body {
      background-color: var(--bg-color);
      color: var(--text-color);
      font-family: 'Inter', sans-serif;
    }

    .page-wrapper, .page {
        background-color: var(--bg-color) !important;
    }

    .card {
      background: var(--card-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      box-shadow: 0 0 25px var(--glow-color);
      transition: transform 0.3s ease-in-out;
    }

    .card-body {
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .card-header {
      text-align: center;
      font-size: 20px;
      font-weight: 700;
      background: transparent;
      border-bottom: 1px solid var(--border-color);
      padding: 20px;
      color: #fff;
      text-shadow: 0 0 5px var(--primary-color);
    }

    #my_camera {
        width: 100%;
        max-width: 320px;
        border-radius: 15px;
        border: 2px solid var(--primary-color);
        box-shadow: 0 0 15px var(--primary-color);
        margin: 20px 0;
        overflow: hidden; /* Clip inner video to rounded corners */
        padding: 6px; /* Space so video doesn't touch the border */
        background: rgba(0, 0, 0, 0.35);
    }

    /* Make the inner webcam elements respect the container shape */
    #my_camera video,
    #my_camera canvas {
        display: block;
        width: 100% !important;
        height: auto !important;
        border-radius: 12px; /* Slightly less due to padding */
    }

    /* Apply mirror to the media, not the container, to avoid layout side-effects */
    #my_camera.mirrored video,
    #my_camera.mirrored canvas {
        transform: scaleX(-1);
        -webkit-transform: scaleX(-1);
    }
    
    #my_result img {
      width: 100%;
      max-width: 320px;
      border-radius: 15px;
      border: 2px solid var(--primary-color);
      box-shadow: 0 0 15px var(--primary-color);
      margin-top: 20px;
    }
    
    .text-points {
        text-align: center;
        margin-bottom: 20px;
        padding: 0 10px;
    }
    
    .text-points p {
        margin-bottom: 5px;
        color: var(--text-color);
        font-size: 1rem;
    }

    .warning-text {
        color: #ff5050;
        font-weight: bold;
        text-shadow: 0 0 5px rgba(255, 80, 80, 0.5);
    }
    
    .btn-circle-container {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    
    .btn-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        border: none;
        color: var(--bg-color);
        font-size: 2rem;
        box-shadow: 0 0 20px var(--glow-color);
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-circle:hover {
        transform: scale(1.1);
        box-shadow: 0 0 30px rgba(0, 224, 179, 0.8);
    }
    
    #map {
        height: 350px;
        margin-top: 30px;
        border-radius: 15px;
        border: 1px solid var(--border-color);
    }
    
    /* Modifikasi untuk pop-up Leaflet */
    .leaflet-popup-content-wrapper, .leaflet-popup-tip {
        background: var(--card-bg) !important;
        color: var(--text-color) !important;
        border: 1px solid var(--border-color);
        box-shadow: 0 0 10px rgba(0, 224, 179, 0.2);
    }
    
    .leaflet-popup-content-wrapper {
        border-radius: 10px;
    }
    
</style>

<?php if ($jarak_meter > $radius) { ?>
    <?=
    $_SESSION['gagal'] = "Anda berada di luar area kantor";
    header("Location: ../home/home.php");
    exit; ?>

<?php } else { ?>

    <div class="page-body">
        <div class="container-xl">
            <div class="col-12">
                <div class="card text-center">
                    <div class="card-header">Ambil Foto Presensi</div>
                    <div class="card-body">
                        <div class="text-points">
                            <p>1. Pastikan wajah anda terlihat dengan jelas.</p>
                            <p>2. Pastikan latar belakang foto terlihat.</p>
                            <p class="warning-text">Apabila foto tidak memenuhi syarat, presensi dianggap tidak berlaku.</p>
                        </div>
                        <input type="hidden" id="id" value="<?= $_SESSION['id'] ?>">
                        <input type="hidden" id="tanggal_masuk" value="<?= $tanggal_masuk ?>">
                        <input type="hidden" id="jam_masuk" value="<?= $jam_masuk ?>">
                        <div id="my_camera" class="mirrored"></div>
                        <br>
                        <div class="text-center" style="color: var(--primary-color); font-weight: bold;"><?= date('d F Y', strtotime($tanggal_masuk)) . ' - ' . $jam_masuk ?></div>
                        <br>
                        <div class="btn-circle-container">
                            <button class="btn-circle" id="ambil-foto">
                                <i class="fas fa-fingerprint"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container-xl">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-center">Lokasi Presensi</div>
                    <div class="card-body">
                        <div id="map"></div>
                    </div>
                </div>
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
                    if (xhttp.readyState == 4 && xhttp.status == 200) {
                        window.location.href = '../home/home.php';
                    }
                };
                xhttp.open("POST", "presensi_masuk_aksi.php", true);
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
        let radius_ktr = <?= $radius ?>;

        let map = L.map('map').setView([latitude_ktr, longitude_ktr], 16);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var marker = L.marker([latitude_peg, longitude_peg]).addTo(map).bindPopup("Posisi anda saat ini").openPopup();

        var circle = L.circle([latitude_ktr, longitude_ktr], {
            color: 'var(--primary-color)',
            fillColor: 'var(--primary-color)',
            fillOpacity: 0.2,
            radius: radius_ktr
        }).addTo(map);

        // Tambahkan kontrol untuk fokus ke lokasi pengguna
        map.locate({setView: true, maxZoom: 16});
        function onLocationFound(e) {
            L.marker(e.latlng).addTo(map)
                .bindPopup("Lokasi Akurat Anda").openPopup();
            L.circle(e.latlng, e.accuracy).addTo(map);
        }
        map.on('locationfound', onLocationFound);
    </script>

<?php } ?>

<?php include('../layout/footer.php'); ?>