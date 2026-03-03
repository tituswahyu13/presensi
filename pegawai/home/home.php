<?php

session_start();

// date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION["login"])) {

  header("Location: https://internal.pdamkotamagelang.com/absensi/auth/login.php?pesan=belum_login");

  exit();
} elseif ($_SESSION["role"] != "pegawai") {

  header("Location: https://internal.pdamkotamagelang.com/absensi/auth/login.php?pesan=tolak_akses");

  exit();
}



$judul = "Hai, " . $_SESSION["nama"]; // Ubah bagian ini untuk menampilkan nama pengguna



include('../layout/header.php');

include_once("../../config.php");



$username = isset($_SESSION["username"]) ? $_SESSION["username"] : '';

echo $username;



$lokasi_presensi = $_SESSION['lokasi_presensi'];

$result = mysqli_query($connection, "SELECT * FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_presensi'");

//$result = mysqli_query($connection, "SELECT * FROM lokasi_presensi WHERE nama_lokasi = 'Event'");



while ($lokasi = mysqli_fetch_array($result)) {

  $latitude_kantor = $lokasi["latitude"];

  $longitude_kantor = $lokasi["longitude"];

  $radius = $lokasi["radius"];

  $zona_waktu = $lokasi["zona_waktu"];

  $jam_pulang = $lokasi["jam_pulang"];
}



if (isset($zona_waktu)) {

  if ($zona_waktu == 'WIB') {

    date_default_timezone_set('Asia/Jakarta');
  } elseif ($zona_waktu == 'WITA') {

    date_default_timezone_set('Asia/Makasar');
  } elseif ($zona_waktu == 'WIT') {

    date_default_timezone_set('Asia/Jayapura');
  }
} else {

  // Handle the case where $zona_waktu is not defined (e.g., show an error message)

  echo "Error: Timezone information not available.";
}



?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

<style>
  :root {

    --primary-color: #00e0b3;

    /* Hijau neon */

    --secondary-color: #00a4d4;

    /* Biru elektrik */

    --bg-color: #0a0a0d;

    --card-bg: rgba(18, 18, 25, 0.7);

    --text-color: #e0e0e0;

    --border-color: rgba(0, 224, 179, 0.3);

    --glow-color: rgba(0, 224, 179, 0.5);

    --dark-text: #333;

    /* Untuk modal agar teksnya terlihat */

  }



  body {

    background: var(--bg-color);

    color: var(--text-color);

    font-family: 'Inter', sans-serif;

    min-height: 100vh;

    overflow-x: hidden;

    position: relative;

    font-feature-settings: "cv03", "cv04", "cv11";

  }



  body::before {

    content: '';

    position: absolute;

    top: 0;

    left: 0;

    width: 100%;

    height: 100%;

    background-image: radial-gradient(circle, #1e1e2d 1px, transparent 1px);

    background-size: 20px 20px;

    opacity: 0.2;

    z-index: -1;

    animation: pan-grid 60s linear infinite;

  }



  @keyframes pan-grid {

    from {

      background-position: 0 0;

    }



    to {

      background-position: -2000px 2000px;

    }

  }



  .futuristic-overlay {

    position: fixed;

    top: 0;

    left: 0;

    width: 100%;

    height: 100%;

    background:

      radial-gradient(circle at 10% 20%, rgba(0, 224, 179, 0.1) 0%, transparent 50%),

      radial-gradient(circle at 90% 80%, rgba(0, 164, 212, 0.1) 0%, transparent 50%);

    pointer-events: none;

    z-index: -2;

  }



  .card {

    background: var(--card-bg);

    backdrop-filter: blur(10px);

    border: 1px solid var(--border-color);

    border-radius: 20px;

    box-shadow: 0 0 25px var(--glow-color);

    transition: transform 0.3s ease-in-out;

  }



  .card:hover {

    transform: translateY(-5px);

  }



  .card-body {

    display: flex;

    flex-direction: column;

    align-items: center;

    padding: 30px;

    color: var(--text-color);

    /* Memastikan teks di card body terlihat */

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



  .parent_date,

  .parent_clock {

    font-family: 'Orbitron', sans-serif;

    /* text-shadow: 0 0 5px var(--primary-color); */

  }



  .parent_date {

    display: flex;

    font-size: 20px;

    justify-content: center;

    color: var(--primary-color);

    /* Warna yang jelas */

    margin-top: 10px;

  }



  .parent_date div+div {

    margin-left: 10px;

  }



  .parent_clock {

    display: flex;

    font-size: 35px;

    justify-content: center;

    font-weight: bold;

    color: var(--secondary-color);

    /* Warna yang jelas */

    margin-top: 5px;

  }



  .parent_clock div+div {

    margin-left: 5px;

  }



  #latitude_pegawai,

  #longitude_pegawai {

    width: 180px;

    padding: 8px;

    margin-bottom: 10px;

    text-align: center;

    background: rgba(255, 255, 255, 0.05);

    border: 1px solid var(--border-color);

    color: #fff;

    border-radius: 10px;

  }



  .btn {

    border: none;

    border-radius: 10px;

    padding: 15px 25px;

    font-weight: bold;

    letter-spacing: 1px;

    transition: all 0.3s ease;

  }



  .btn-green {

    background: linear-gradient(45deg, #00e0b3, #00a4d4);

    box-shadow: 0 4px 15px rgba(0, 224, 179, 0.4);

    color: #fff;

  }



  .btn-green:hover {

    background: linear-gradient(45deg, #00a4d4, #00e0b3);

    transform: translateY(-3px);

    box-shadow: 0 6px 20px rgba(0, 224, 179, 0.6);

  }



  .btn-pink {

    background: linear-gradient(45deg, #ff5050, #ff8080);

    box-shadow: 0 4px 15px rgba(255, 80, 80, 0.4);

    color: #fff;

  }



  .btn-pink:hover {

    background: linear-gradient(45deg, #ff8080, #ff5050);

    transform: translateY(-3px);

    box-shadow: 0 6px 20px rgba(255, 80, 80, 0.6);

  }



  .btn-sm {

    padding: 10px 15px;

    font-size: 14px;

    height: 90px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    border-radius: 15px;

    color: #fff;

    /* Teks tombol terlihat jelas */

  }



  .btn-sm i {

    font-size: 24px;

    margin-bottom: 8px;

    color: #fff;

    /* Ikon tombol terlihat jelas */

  }



  .btn-warning {

    background: linear-gradient(45deg, #ffc107, #ff9800);

    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);

  }



  .btn-warning:hover {

    background: linear-gradient(45deg, #ff9800, #ffc107);

    transform: translateY(-3px);

    box-shadow: 0 6px 20px rgba(255, 193, 7, 0.6);

  }



  .btn-danger {

    background: linear-gradient(45deg, #dc3545, #b40a1b);

    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);

  }



  .btn-danger:hover {

    background: linear-gradient(45deg, #b40a1b, #dc3545);

    transform: translateY(-3px);

    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.6);

  }



  .btn-info {

    background: linear-gradient(45deg, #17a2b8, #007bff);

    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);

  }



  .btn-info:hover {

    background: linear-gradient(45deg, #007bff, #17a2b8);

    transform: translateY(-3px);

    box-shadow: 0 6px 20px rgba(23, 162, 184, 0.6);

  }



  #map {
    height: 300px !important;
    width: 100% !important;
    min-height: 300px !important;
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
  
  .location-info {
    text-align: left;
    padding: 8px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    margin-bottom: 8px;
  }
  
  .location-info small {
    font-size: 0.75rem;
    opacity: 0.8;
  }
  
  .location-info .fw-bold {
    color: var(--primary-color);
    font-family: 'Orbitron', monospace;
    font-size: 0.9rem;
  }
  
  #gps-status.fa-check-circle {
    color: var(--primary-color) !important;
  }
  
  #gps-status.fa-times-circle {
    color: #ff5050 !important;
  }
  
  #distance-info.inside-radius {
    color: var(--primary-color) !important;
  }
  
  #distance-info.outside-radius {
    color: #ff5050 !important;
  }
  
  #presensi-status.allowed {
    color: var(--primary-color) !important;
  }
  
  #presensi-status.not-allowed {
    color: #ff5050 !important;
  }
  
  .status-info-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid var(--border-color);
    backdrop-filter: blur(10px);
  }
  
  .status-item {
    text-align: center;
    padding: 10px;
    background: rgba(0, 224, 179, 0.1);
    border-radius: 10px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
  }
  
  .status-item:hover {
    background: rgba(0, 224, 179, 0.2);
    transform: translateY(-2px);
  }
  
  .status-item small {
    font-size: 0.8rem;
    margin-bottom: 5px;
  }
  
  .status-item .fw-bold {
    font-size: 1rem;
    color: var(--primary-color);
  }
  
  .status-item-compact {
    text-align: center;
    padding: 8px 5px;
    background: rgba(0, 224, 179, 0.1);
    border-radius: 8px;
    transition: all 0.3s ease;
    height: 100%;
  }
  
  .status-item-compact:hover {
    background: rgba(0, 224, 179, 0.2);
    transform: translateY(-1px);
  }
  
  .status-item-compact small {
    font-size: 0.7rem;
    margin-bottom: 3px;
  }
  
  .status-item-compact .fw-bold {
    font-size: 0.85rem;
    color: var(--primary-color);
  }
  
  /* Modern Status Design */
  .profile-img {
    /* border-radius: 15px; */
    /* box-shadow: 0 0 20px rgba(0, 224, 179, 0.3); */
    transition: all 0.3s ease;
    float: left;
    margin-right: 15px;
    max-width: 100%;
  }
  
  .profile-img:hover {
    transform: scale(1.05);
    box-shadow: 0 0 30px rgba(0, 224, 179, 0.5);
  }
  
  .status-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
  }
  
  .status-item-modern {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 15px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
  }
  
  .status-item-modern:hover {
    background: rgba(0, 224, 179, 0.1);
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 224, 179, 0.2);
  }
  
  .status-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
  }
  
  .status-icon i {
    color: white;
    font-size: 1.2rem;
  }
  
  .status-content {
    flex-grow: 1;
  }
  
  .status-label {
    display: block;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 3px;
  }
  
  .status-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-color);
  }
  
  .status-value i {
    margin-right: 5px;
  }
  
  /* Compact Status List */
  .status-compact-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 15px;
    /* max-width: 200px; */
  }
  
  .status-compact-item {
    display: flex;
    align-items: center;
    background: rgba(0, 224, 179, 0.1);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 8px 12px;
    transition: all 0.3s ease;
    font-size: 0.85rem;
  }
  
  .status-compact-item:hover {
    background: rgba(0, 224, 179, 0.2);
    border-color: var(--primary-color);
    transform: translateY(-1px);
  }
  
  .status-compact-item i {
    color: var(--primary-color);
    margin-right: 8px;
    font-size: 0.9rem;
    width: 16px;
    text-align: center;
  }
  
  .status-compact-item span {
    color: var(--text-color);
    font-weight: 500;
  }
  
  /* Mobile Optimization */
  @media (max-width: 768px) {
    .status-info-card {
      padding: 15px;
      margin-top: 15px;
    }
    
    .status-item {
      padding: 8px;
      margin-bottom: 8px;
    }
    
    .status-item small {
      font-size: 0.75rem;
    }
    
    .status-item .fw-bold {
      font-size: 0.9rem;
    }
    
    #map {
      height: 300px !important;
      max-width: 100% !important;
    }
  }
  
  /* Stack on very small screens */
  @media (max-width: 576px) {
    .status-info-card .row {
      flex-direction: column;
    }
    
    .status-info-card .col-6 {
      width: 100%;
      margin-bottom: 10px;
    }
    
    .status-item-compact {
      padding: 10px;
      margin-bottom: 5px;
    }
    
    .status-item-compact small {
      font-size: 0.8rem;
    }
    
    .status-item-compact .fw-bold {
      font-size: 0.95rem;
    }
    
    /* Modern Design Mobile */
    .profile-img {
      height: 150px;
    }
    
    .status-grid {
      gap: 10px;
    }
    
    .status-item-modern {
      padding: 12px;
    }
    
    .status-icon {
      width: 35px;
      height: 35px;
      margin-right: 12px;
    }
    
    .status-icon i {
      font-size: 1rem;
    }
    
    .status-label {
      font-size: 0.75rem;
    }
    
    .status-value {
      font-size: 0.9rem;
    }
    
    /* Compact Status Mobile */
    .status-compact-list {
      gap: 6px;
      margin-top: 10px;
    }
    
    .status-compact-item {
      padding: 6px 10px;
      font-size: 0.8rem;
    }
    
    .status-compact-item i {
      font-size: 0.8rem;
      width: 14px;
      margin-right: 6px;
    }
  }



  .fa-circle-check {

    color: var(--primary-color) !important;

    text-shadow: 0 0 10px var(--primary-color);

  }



  .fa-circle-xmark {

    color: #ff5050 !important;

    text-shadow: 0 0 10px rgba(255, 80, 80, 0.7);

  }



  .text-success {

    color: var(--primary-color) !important;

  }



  .text-danger {

    color: #ff5050 !important;

    text-shadow: 0 0 5px rgba(255, 80, 80, 0.5);

  }



  /* .foto-pegawai {

width: 150px;

height: 150px;

border-radius: 50%;

object-fit: cover;

border: 3px solid var(--primary-color);

box-shadow: 0 0 15px rgba(0, 224, 179, 0.7);

margin-bottom: 20px;

} */



  .modal-content {

    background: var(--card-bg) !important;

    border: 1px solid var(--border-color) !important;

    color: var(--text-color) !important;

  }



  .modal-content h5 {

    color: #fff !important;

    text-shadow: 0 0 3px var(--primary-color);

  }



  .modal-content p {

    color: var(--text-color);

  }



  .modal-content textarea#keterangan_izin {

    background: rgba(255, 255, 255, 0.08);

    border: 1px solid var(--primary-color);

    color: #fff;

    padding: 10px;

    border-radius: 8px;

    width: 100%;

    resize: vertical;

  }



  .modal-content .btn-success {

    background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)) !important;

    border: none !important;

    box-shadow: 0 2px 10px rgba(0, 224, 179, 0.4);

    color: #fff;

    padding: 10px 20px;

    border-radius: 8px;

    font-weight: bold;

    transition: all 0.2s ease-in-out;

  }



  .modal-content .btn-success:hover {

    background: linear-gradient(45deg, var(--secondary-color), var(--primary-color)) !important;

    transform: translateY(-2px);

    box-shadow: 0 4px 15px rgba(0, 224, 179, 0.6);

  }



  .modal-content .btn-secondary {

    background-color: #555 !important;

    border: none !important;

    color: #fff;

    padding: 10px 20px;

    border-radius: 8px;

    font-weight: bold;

    transition: all 0.2s ease-in-out;

  }



  .modal-content .btn-secondary:hover {

    background-color: #777 !important;

    transform: translateY(-2px);

  }
</style>



<div class="page-body">

  <div class="futuristic-overlay"></div>

  <div class="container-xl">

    <div class="row row-deck g-4">

      <div class="col-lg-12">
        <div class="row g-4 align-items-start">
          <!-- Foto Kiri -->
          <div class="col-md-5 text-start">
            <img src="/absensi/assets/img/foto_pegawai/<?= $_SESSION['foto'] ?>" alt="Employee Photo" height="120" class="profile-img">
            
            <!-- Status Ringkas di Bawah Foto -->
            <div class="status-compact-list mt-3">
              <div class="status-compact-item">
                <i class="fa-solid fa-satellite-dish"></i>
                <span id="gps-status">
                  <i class="fa-solid fa-spinner fa-spin"></i> GPS
                </span>
              </div>
              <div class="status-compact-item">
                <i class="fa-solid fa-map-marker-alt"></i>
                <span id="presensi-status">-</span>
              </div>
            </div>
            <div class="clearfix"></div>
          </div>
          
          <!-- Presensi Masuk Tengah -->

      <div class="col-md-6 col-lg-4">

        <div class="card text-center h-100">

          <div class="card-header">Presensi Masuk</div>

          <div class="card-body">



            <?php

            $id_pegawai = $_SESSION['id'];

            $tanggal_hari_ini = date("Y-m-d");



            // $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_keluar IS NULL");

            $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_masuk = '$tanggal_hari_ini'"); //atau ganti tanggal_keluar IS NULL kalau shift beda hari

            ?>



            <?php if (mysqli_num_rows($cek_presensi_masuk) === 0) { ?>



              <div class="parent_date">

                <div id="tanggal_masuk"></div>

                <div class="ms-2"></div>

                <div id="bulan_masuk"></div>

                <div class="ms-2"></div>

                <div id="tahun_masuk"></div>

              </div>

              <div class="parent_clock">

                <div id="jam_masuk"></div>

                <div class="ms-1">:</div>

                <div id="menit_masuk"></div>

                <div class="ms-1">:</div>

                <div id="detik_masuk"></div>

              </div>

              <form method="POST" action="<?= '../../pegawai/presensi/presensi_masuk.php' ?>">

                <input type="hidden" name="latitude_pegawai" id="latitude_pegawai" readonly>

                <input type="hidden" name="longitude_pegawai" id="longitude_pegawai" readonly>

                <input type="hidden" value="<?= $latitude_kantor ?>" name="latitude_kantor" readonly>

                <input type="hidden" value="<?= $longitude_kantor ?>" name="longitude_kantor" readonly>

                <input type="hidden" value="<?= $radius ?>" name="radius">

                <input type="hidden" value="<?= $zona_waktu ?>" name="zona_waktu">

                <input type="hidden" value="<?= date('Y-m-d') ?>" name="tanggal_masuk">

                <input type="hidden" value="<?= date('H:i:s') ?>" name="jam_masuk">

                <button type="submit" name="tombol_masuk" class="btn btn-green mt-3">Masuk</button>

              </form>

            <?php } else { ?>

              <i class="fa-solid fa-circle-check fa-4x text-success"></i>

              <h4 class="my-3" style="color: var(--text-color);">Anda telah melakukan <br> presensi MASUK</h4>

            <?php } ?>

          </div>



        </div>

      </div>

      <div class="col-md-6 col-lg-4">

        <div class="card text-center h-100">

          <div class="card-header">Presensi Keluar</div>

          <div class="card-body">



            <?php

            // $ambil_data_presensi = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_keluar IS NULL");

            $ambil_data_presensi = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_masuk = '$tanggal_hari_ini'"); //atau ganti tanggal_keluar IS NULL kalau shift beda hari

            ?>



            <?php $waktu_sekarang = date("H:i:s");



            if (strtotime($waktu_sekarang) >= strtotime($jam_pulang) && mysqli_num_rows($ambil_data_presensi) == 0) { ?>

              <i class="fa-regular fa-circle-xmark fa-4x text-danger"></i>

              <h4 class="my-3" style="color: var(--text-color);">Anda belum melakukan<br>Presensi MASUK</h4>



            <?php } else { ?>



              <?php while ($cek_presensi_keluar = mysqli_fetch_array($ambil_data_presensi)) { ?>

                <?php if (($cek_presensi_keluar['tanggal_masuk']) && $cek_presensi_keluar['tanggal_keluar'] === NULL) { ?>



                  <div class="parent_date">

                    <div id="tanggal_keluar"></div>

                    <div></div>

                    <div id="bulan_keluar"></div>

                    <div></div>

                    <div id="tahun_keluar"></div>

                  </div>

                  <div class="parent_clock">

                    <div id="jam_keluar"></div>

                    <div>:</div>

                    <div id="menit_keluar"></div>

                    <div>:</div>

                    <div id="detik_keluar"></div>

                  </div>

                  <form method="POST" action="<?= '../../pegawai/presensi/presensi_keluar.php' ?>">

                    <input type="hidden" name="id" value="<?= $cek_presensi_keluar['id'] ?>">

                    <input type="hidden" name="latitude_pegawai" id="latitude_pegawai" readonly>

                    <input type="hidden" name="longitude_pegawai" id="longitude_pegawai" readonly>

                    <input type="hidden" value="<?= $latitude_kantor ?>" name="latitude_kantor" readonly>

                    <input type="hidden" value="<?= $longitude_kantor ?>" name="longitude_kantor" readonly>

                    <input type="hidden" value="<?= $radius ?>" name="radius">

                    <input type="hidden" value="<?= $zona_waktu ?>" name="zona_waktu">

                    <input type="hidden" value="<?= date('Y-m-d') ?>" name="tanggal_keluar">

                    <input type="hidden" value="<?= date('H:i:s') ?>" name="jam_keluar">



                    <button name="tombol_keluar" type="submit" class="btn btn-pink mt-3">Keluar</button>

                  </form>



                <?php } else { ?>

                  <i class="fa-solid fa-circle-check fa-4x text-success"></i>

                  <h4 class="my-3" style="color: var(--text-color);">Anda telah melakukan <br> presensi KELUAR</h4>



                <?php } ?>



              <?php } ?>



            <?php } ?>

          </div>

        </div>

      </div>

      <div class="col-lg-4">

        <div class="card text-center h-100">

          <div class="card-header">Absensi</div>

          <div class="card-body d-flex flex-column justify-content-center">

            <div class="row g-2">

              <div class="col-4">

                <form id="formIzin" method="POST" action="<?= '../../pegawai/presensi/pengajuan_ijin.php' ?>">

                  <input type="hidden" value="<?= date('Y-m-d') ?>" name="tanggal_pengajuan">

                  <input type="hidden" value="<?= date('H:i:s') ?>" name="jam_pengajuan">

                  <input type="hidden" value="Izin Pribadi" name="jenis_pengajuan">

                  <input type="hidden" name="keterangan_izin" id="input_keterangan_izin">

                  <input type="hidden" name="tombol_ijin" value="1">

                  <button type="button" name="tombol_ijin" class="btn btn-warning btn-sm w-100" onclick="openModalIzin()">

                    <i class="fa-solid fa-calendar-day"></i><br>

                    Izin

                  </button>

                </form>

                <div class="modal" id="modalKeteranganIzin" tabindex="-1" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">

                  <div style="background:var(--card-bg); margin:10% auto; padding:20px; border-radius:15px; width:90%; max-width:400px; position:relative;">

                    <h5 style="color:#fff;">Keterangan Izin</h5>

                    <textarea id="keterangan_izin" name="keterangan_izin" rows="3" style="width:100%;"></textarea>

                    <div style="margin-top:20px; text-align:right;">

                      <button type="button" onclick="submitIzin()" class="btn btn-success">Kirim</button>

                      <button type="button" onclick="closeModalIzin()" class="btn btn-secondary">Batal</button>

                    </div>

                  </div>

                </div>

                <div class="modal" id="modalKonfirmasi" tabindex="-1" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">

                  <div style="background:var(--card-bg); margin:15% auto; padding:20px; border-radius:15px; width:90%; max-width:400px; position:relative; text-align:center;">

                    <h5 style="color:#fff;">Konfirmasi</h5>

                    <p style="color: var(--text-color);">Apakah Anda yakin ingin mengajukan?</p>

                    <div style="margin-top:20px;">

                      <button type="button" class="btn btn-success" onclick="submitKonfirmasi()">Ya</button>

                      <button type="button" class="btn btn-secondary" onclick="closeKonfirmasi()">Batal</button>

                    </div>

                  </div>

                </div>

              </div>

              <div class="col-4">

                <form id="formSakit" method="POST" action="<?= '../../pegawai/presensi/pengajuan_sakit.php' ?>">

                  <input type="hidden" value="<?= date('Y-m-d') ?>" name="tanggal_pengajuan">

                  <input type="hidden" value="<?= date('H:i:s') ?>" name="jam_pengajuan">

                  <input type="hidden" value="Sakit" name="jenis_pengajuan">

                  <input type="hidden" name="tombol_sakit" value="1">

                  <button type="button" class="btn btn-danger btn-sm w-100" onclick="openKonfirmasi('formSakit')">

                    <i class="fa-solid fa-user-injured"></i><br>

                    Sakit

                  </button>

                </form>

              </div>

              <div class="col-4">

                <form id="formDinas" method="POST" action="<?= '../../pegawai/presensi/pengajuan_dinas.php' ?>">

                  <input type="hidden" value="<?= date('Y-m-d') ?>" name="tanggal_pengajuan">

                  <input type="hidden" value="<?= date('H:i:s') ?>" name="jam_pengajuan">

                  <input type="hidden" value="Dinas Luar" name="jenis_pengajuan">

                  <input type="hidden" name="tombol_dinas" value="1">

                  <button type="button" class="btn btn-info btn-sm w-100" onclick="openKonfirmasi('formDinas')">

                    <i class="fa-solid fa-car"></i><br>

                    Dinas Luar

                  </button>

                </form>

              </div>

            </div>

          </div>

        </div>

      </div>

      <div class="col-lg-12 mb-4">
        <div class="card">
          <div class="card-header text-center">Peta Lokasi</div>
          <div class="card-body text-center">
            <div id="map" style="height: 350px; width: 100%; max-width: 500px; margin: 0 auto; aspect-ratio: 1/1; z-index: 1;"></div>
          </div>
        </div>
      </div>

    </div>

  </div>

</div>



<script>
  window.setTimeout("waktuMasuk()", 1000);

  namaBulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];



  function waktuMasuk() {

    const waktu = new Date();

    setTimeout("waktuMasuk()", 1000);

    document.getElementById("tanggal_masuk").innerHTML = waktu.getDate();

    document.getElementById("bulan_masuk").innerHTML = namaBulan[waktu.getMonth()];

    document.getElementById("tahun_masuk").innerHTML = waktu.getFullYear();

    document.getElementById("jam_masuk").innerHTML = String(waktu.getHours()).padStart(2, '0');

    document.getElementById("menit_masuk").innerHTML = String(waktu.getMinutes()).padStart(2, '0');

    document.getElementById("detik_masuk").innerHTML = String(waktu.getSeconds()).padStart(2, '0');

  }



  window.setTimeout("waktuKeluar()", 1000);

  namaBulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];



  function waktuKeluar() {

    const waktu = new Date();

    setTimeout("waktuKeluar()", 1000);

    document.getElementById("tanggal_keluar").innerHTML = waktu.getDate();

    document.getElementById("bulan_keluar").innerHTML = namaBulan[waktu.getMonth()];

    document.getElementById("tahun_keluar").innerHTML = waktu.getFullYear();

    document.getElementById("jam_keluar").innerHTML = String(waktu.getHours()).padStart(2, '0');

    document.getElementById("menit_keluar").innerHTML = String(waktu.getMinutes()).padStart(2, '0');

    document.getElementById("detik_keluar").innerHTML = String(waktu.getSeconds()).padStart(2, '0');

  }



  getLocation();



  function getLocation() {

    if (navigator.geolocation) {

      navigator.geolocation.getCurrentPosition(showPosition, handleGeolocationError);

    } else {

      alert("Geolocation is not supported by this browser");

    }

  }



  function showPosition(position) {

    $('#latitude_pegawai').val(position.coords.latitude);

    $('#longitude_pegawai').val(position.coords.longitude);

    // Update lokasi info card
    $('#current-lat').text(position.coords.latitude.toFixed(8));
    $('#current-lng').text(position.coords.longitude.toFixed(8));
    $('#gps-status').html('<i class="fa-solid fa-check-circle"></i> GPS Aktif');

    // Update marker user di peta jika peta sudah ada
    if (window.homeMap && window.userMarker) {
        // Hapus marker lama
        window.homeMap.removeLayer(window.userMarker);
        
        // Tambah marker baru dengan posisi terbaru
        window.userMarker = L.marker([position.coords.latitude, position.coords.longitude], {
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        }).addTo(window.homeMap)
            .bindPopup("Posisi Anda<br>Jarak: " + calculateDistance(position.coords.latitude, position.coords.longitude, latitude_ktr, longitude_ktr) + " meter")
            .openPopup();
            
        console.log('User marker updated to new position');
    }

    // Hitung jarak ke kantor
    let latitude_ktr = <?= $latitude_kantor ?>;
    let longitude_ktr = <?= $longitude_kantor ?>;
    let radius_ktr = <?= $radius ?>;
    
    let distance = calculateDistance(position.coords.latitude, position.coords.longitude, latitude_ktr, longitude_ktr);
    
    $('#distance-info').text(distance + ' meter');
    
    if (distance <= radius_ktr) {
      $('#distance-info').removeClass('outside-radius').addClass('inside-radius');
      $('#presensi-status').removeClass('not-allowed').addClass('allowed');
      $('#presensi-status').html('<i class="fa-solid fa-check-circle"></i> Dalam Area');
    } else {
      $('#distance-info').removeClass('inside-radius').addClass('outside-radius');
      $('#presensi-status').removeClass('allowed').addClass('not-allowed');
      $('#presensi-status').html('<i class="fa-solid fa-times-circle"></i> Luar Area');
    }



    // Mengisi koordinat untuk form pengajuan

    $('#latitude_pegawai_ijin').val(position.coords.latitude);

    $('#longitude_pegawai_ijin').val(position.coords.longitude);

    $('#latitude_pegawai_sakit').val(position.coords.latitude);

    $('#longitude_pegawai_sakit').val(position.coords.longitude);

    $('#latitude_pegawai_dinas').val(position.coords.latitude);

    $('#longitude_pegawai_dinas').val(position.coords.longitude);

  }



  function handleGeolocationError(error) {
    $('#current-lat').text('Tidak tersedia');
    $('#current-lng').text('Tidak tersedia');
    $('#gps-status').html('<i class="fa-solid fa-times-circle"></i> GPS Error');
    $('#distance-info').text('-');
    $('#presensi-status').html('<i class="fa-solid fa-exclamation-triangle"></i> Error');

    switch (error.code) {
      case error.PERMISSION_DENIED:
        $('#gps-status').html('<i class="fa-solid fa-times-circle"></i> GPS Ditolak');
        break;
      case error.POSITION_UNAVAILABLE:
        $('#gps-status').html('<i class="fa-solid fa-times-circle"></i> Lokasi Tidak Ada');
        break;
      case error.TIMEOUT:
        $('#gps-status').html('<i class="fa-solid fa-times-circle"></i> GPS Timeout');
        break;
      case error.UNKNOWN_ERROR:
        $('#gps-status').html('<i class="fa-solid fa-times-circle"></i> Error GPS');
        break;
    }
  }

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



  function openModalIzin() {

    document.getElementById('modalKeteranganIzin').style.display = 'block';

  }



  function closeModalIzin() {

    document.getElementById('modalKeteranganIzin').style.display = 'none';

  }



  function submitIzin() {

    var keterangan = document.getElementById('keterangan_izin').value;

    document.getElementById('input_keterangan_izin').value = keterangan;

    document.getElementById('formIzin').submit();

  }
</script>



<script>
  // Script konfirmasi Sakit/Dinas pastikan di bawah semua elemen HTML/modal

  var formToSubmit = null;



  function openKonfirmasi(formId) {

    formToSubmit = document.getElementById(formId);

    document.getElementById('modalKonfirmasi').style.display = 'block';

  }



  function closeKonfirmasi() {

    document.getElementById('modalKonfirmasi').style.display = 'none';

    formToSubmit = null;

  }



  function submitKonfirmasi() {

    if (formToSubmit) {

      formToSubmit.submit();

      formToSubmit = null;

    } else {

      alert('Form tidak ditemukan. Silakan refresh halaman.');

    }

    closeKonfirmasi();

  }
</script>



<script>
// Inisialisasi peta lokasi presensi
console.log('Initializing home map...');

// Tunggu GPS selesai mendapatkan lokasi
setTimeout(function() {
    let latitude_peg = $('#latitude_pegawai').val();
    let longitude_peg = $('#longitude_pegawai').val();
    let latitude_ktr = <?= $latitude_kantor ?>;
    let longitude_ktr = <?= $longitude_kantor ?>;
    let radius_ktr = <?= $radius ?>;

    console.log('Home coordinates:', latitude_peg, longitude_peg, latitude_ktr, longitude_ktr, radius_ktr);

    if (latitude_ktr && longitude_ktr) {
        try {
            window.homeMap = L.map('map').setView([latitude_ktr, longitude_ktr], 16);
            console.log('Home map initialized');

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(window.homeMap);

            // Marker lokasi kantor
            var marker_kantor = L.marker([latitude_ktr, longitude_ktr]).addTo(window.homeMap)
                .bindPopup("Lokasi Kantor<br>Radius: " + radius_ktr + " meter")
                .openPopup();

            // Area radius kantor
            var circle = L.circle([latitude_ktr, longitude_ktr], {
                color: '#00e0b3',
                fillColor: '#00e0b3',
                fillOpacity: 0.2,
                radius: radius_ktr
            }).addTo(window.homeMap);

            // Marker lokasi user dengan retry mechanism
            var marker_user = null;
            
            function addUserMarker() {
                let current_lat = $('#latitude_pegawai').val();
                let current_lng = $('#longitude_pegawai').val();
                
                if (current_lat && current_lng && current_lat !== '' && current_lng !== '') {
                    // Hapus marker lama jika ada
                    if (window.userMarker) {
                        window.homeMap.removeLayer(window.userMarker);
                    }
                    
                    // Tambah marker baru
                    window.userMarker = L.marker([current_lat, current_lng], {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        })
                    }).addTo(window.homeMap)
                        .bindPopup("Posisi Anda<br>Jarak: " + calculateDistance(current_lat, current_lng, latitude_ktr, longitude_ktr) + " meter")
                        .openPopup();
                    
                    console.log('User marker added at:', current_lat, current_lng);
                    return true;
                } else {
                    console.log('User coordinates not available yet');
                    return false;
                }
            }
            
            // Coba tambahkan marker sekarang
            if (!addUserMarker()) {
                // Retry setiap 2 detik sampai marker berhasil ditambahkan
                var retryInterval = setInterval(function() {
                    if (addUserMarker()) {
                        clearInterval(retryInterval);
                    }
                }, 2000);
                
                // Stop retry setelah 10 detik
                setTimeout(function() {
                    clearInterval(retryInterval);
                }, 10000);
            }

            // Tambahkan kontrol scale
            L.control.scale().addTo(window.homeMap);

            // Force map to redraw
            setTimeout(function() {
                window.homeMap.invalidateSize();
                console.log('Home map size invalidated');
            }, 100);

        } catch (error) {
            console.error('Error initializing home map:', error);
        }
    } else {
        console.error('Kantor coordinates not available');
    }
}, 1000); // Tunggu 1 detik untuk GPS
</script>

<?php include('../layout/footer.php'); ?>