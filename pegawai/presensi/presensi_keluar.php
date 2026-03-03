<?php
ob_start();
session_start();

if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] == "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}
include('../layout/header.php');
include_once("../../config.php");

if (isset($_POST['tombol_keluar'])) {
    $id = $_POST['id'];
    $latitude_pegawai = $_POST['latitude_pegawai'];
    $longitude_pegawai = $_POST['longitude_pegawai'];
    $latitude_kantor = $_POST['latitude_kantor'];
    $longitude_kantor = $_POST['longitude_kantor'];
    $radius = $_POST['radius'];
    $zona_waktu = $_POST['zona_waktu'];
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $jam_keluar = $_POST['jam_keluar'];
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

    /* Ensure inner webcam media fits and inherits rounded shape */
    #my_camera video,
    #my_camera canvas {
        display: block;
        width: 100% !important;
        height: auto !important;
        border-radius: 12px; /* slightly inside due to padding */
    }

    /* Mirror the media, not the container, to avoid layout side-effects */
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
        height: 350px !important;
        width: 100% !important;
        min-height: 350px !important;
        margin-top: 30px;
        border-radius: 15px;
        border: 1px solid var(--border-color);
        z-index: 1 !important;
        background: #1a1a1a !important;
        position: relative !important;
    }
    
    .leaflet-container {
        height: 100% !important;
        width: 100% !important;
        background: #1a1a1a !important;
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
                        <input type="hidden" id="id" value="<?= $id ?>">
                        <input type="hidden" id="tanggal_keluar" value="<?= $tanggal_keluar ?>">
                        <input type="hidden" id="jam_keluar" value="<?= $jam_keluar ?>">
                        <div id="my_camera" class="mirrored"></div>
                        <br>
                        <div class="text-center" style="color: var(--primary-color); font-weight: bold;"><?= date('d F Y', strtotime($tanggal_keluar)) . ' - ' . $jam_keluar ?></div>
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
                        <div id="map" style="height: 350px; width: 100%; min-height: 350px; z-index: 1;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script language="JavaScript">
        // Inisialisasi webcam
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

        // Event listener untuk tombol foto
        document.getElementById('ambil-foto').addEventListener('click', function() {
            let id = document.getElementById('id').value;
            let tanggal_keluar = document.getElementById('tanggal_keluar').value;
            let jam_keluar = document.getElementById('jam_keluar').value;

            Webcam.snap(function(data_uri) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (xhttp.readyState == 4 && xhttp.status == 200) {
                        window.location.href = '../home/home.php';
                    }
                };
                xhttp.open("POST", "presensi_keluar_aksi.php", true);
                xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
                xhttp.send(
                    'photo=' + encodeURIComponent(data_uri) +
                    '&id=' + id +
                    '&tanggal_keluar=' + tanggal_keluar +
                    '&jam_keluar=' + jam_keluar
                );
            });
        });

        // Inisialisasi peta langsung setelah webcam
        console.log('Initializing map...');
        
        // map leaflet js
        let latitude_ktr = <?= $latitude_kantor ?>;
        let longitude_ktr = <?= $longitude_kantor ?>;
        let latitude_peg = <?= $latitude_pegawai ?>;
        let longitude_peg = <?= $longitude_pegawai ?>;
        let radius_ktr = <?= $radius ?>;

        console.log('Coordinates:', latitude_ktr, longitude_ktr, latitude_peg, longitude_peg, radius_ktr);

        // Tunggu sedikit agar DOM siap
        setTimeout(function() {
            try {
                let map = L.map('map').setView([latitude_ktr, longitude_ktr], 16);
                console.log('Map object created successfully');
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Marker lokasi kantor dengan radius
        var marker_kantor = L.marker([latitude_ktr, longitude_ktr]).addTo(map)
            .bindPopup("Lokasi Kantor<br>Radius: " + radius_ktr + " meter")
            .openPopup();

        var circle = L.circle([latitude_ktr, longitude_ktr], {
            color: '#00e0b3',
            fillColor: '#00e0b3',
            fillOpacity: 0.2,
            radius: radius_ktr
        }).addTo(map);

        // Marker lokasi user saat ini
        var marker_user = L.marker([latitude_peg, longitude_peg], {
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        }).addTo(map)
            .bindPopup("Posisi Anda<br>Jarak: " + <?= $jarak_meter ?> + " meter")
            .openPopup();

        // Tambahkan kontrol scale
        L.control.scale().addTo(map);

        // Force map to redraw after container is ready
        setTimeout(function() {
            map.invalidateSize();
            console.log('Map size invalidated');
        }, 100);

        // Tambahkan kontrol untuk fokus ke lokasi pengguna (opsional)
        map.locate({setView: false, watch: false});
        function onLocationFound(e) {
            // Jika lokasi GPS lebih akurat, update marker
            var currentDistance = calculateDistance(
                latitude_peg, longitude_peg, 
                e.latlng.lat, e.latlng.lng
            );
            
            if (currentDistance < 50) { // Jika perbedaan kurang dari 50m
                marker_user.setLatLng(e.latlng);
                marker_user.bindPopup("Lokasi GPS Akurat<br>Jarak: " + 
                    calculateDistance(latitude_ktr, longitude_ktr, e.latlng.lat, e.latlng.lng) + " meter").openPopup();
            }
        }
        map.on('locationfound', onLocationFound);

        // Fungsi untuk menghitung jarak
        function calculateDistance(lat1, lon1, lat2, lon2) {
            var R = 6371; // Radius bumi dalam km
            var dLat = (lat2 - lat1) * Math.PI / 180;
            var dLon = (lon2 - lon1) * Math.PI / 180;
            var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            var d = R * c;
            return Math.round(d * 1000); // Konversi ke meter
        }
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        }, 500); // Tunggu 500ms
    </script>

<?php } ?>

<?php include('../layout/footer.php'); ?>