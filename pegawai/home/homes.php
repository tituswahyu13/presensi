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
    height: 300px;
    border-radius: 15px;
    border: 1px solid var(--border-color);
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
      <div class="col-lg-12 text-center">
        <img src="/absensi/assets/img/foto_pegawai/<?= $_SESSION['foto'] ?>" alt="Employee Photo" height="150">
      </div>
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
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header text-center">Lokasi</div>
          <div class="card-body">
            <div id="map" style="width: 100%; height: 400px; border-radius: 15px; border: 1px solid var(--border-color);"></div>
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

    // Mengisi koordinat untuk form pengajuan
    $('#latitude_pegawai_ijin').val(position.coords.latitude);
    $('#longitude_pegawai_ijin').val(position.coords.longitude);
    $('#latitude_pegawai_sakit').val(position.coords.latitude);
    $('#longitude_pegawai_sakit').val(position.coords.longitude);
    $('#latitude_pegawai_dinas').val(position.coords.latitude);
    $('#longitude_pegawai_dinas').val(position.coords.longitude);
  }

  function handleGeolocationError(error) {
    switch (error.code) {
      case error.PERMISSION_DENIED:
        alert("User denied the request for geolocation.");
        break;
      case error.POSITION_UNAVAILABLE:
        alert("Location information is unavailable.");
        break;
      case error.TIMEOUT:
        alert("The request to get user location timed out.");
        break;
      case error.UNKNOWN_ERROR:
        alert("An unknown error occurred.");
        break;
    }
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

<?php include('../layout/footer.php'); ?>