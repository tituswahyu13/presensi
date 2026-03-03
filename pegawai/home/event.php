<?php
session_start();
// date_default_timezone_set('Asia/Jakarta');
if (!isset($_SESSION["login"])) {
  header("Location: ../../auth/login.php?pesan=belum_login");
  exit();
} elseif ($_SESSION["role"] == "admin") {
  header("Location: ../../auth/login.php?pesan=tolak_akses");
  exit();
}

$judul = "Hai, " . $_SESSION["nama"]; // Ubah bagian ini untuk menampilkan nama pengguna

include('../layout/header.php');
include_once("../../config.php");

$username = isset($_SESSION["username"]) ? $_SESSION["username"] : '';

$lokasi_presensi = $_SESSION['lokasi_presensi'];
$result = mysqli_query($connection, "SELECT * FROM lokasi_presensi WHERE nama_lokasi = 'Event'");

while ($lokasi = mysqli_fetch_array($result)) {
  $latitude_kantor = $lokasi["latitude"];
  $longitude_kantor = $lokasi["longitude"];
  $radius = $lokasi["radius"];
  $zona_waktu = $lokasi["zona_waktu"];
  $jam_pulang = $lokasi["jam_pulang"];
  $alamat_lokasi = $lokasi["alamat_lokasi"]; // Tambahkan baris ini
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
  /* Menggunakan variabel warna dari header.php */
  :root {
    --primary-color: #00e0b3; /* Hijau neon */
    --secondary-color: #00a4d4; /* Biru elektrik */
    --bg-color: #0a0a0d;
    --card-bg: rgba(18, 18, 25, 0.7);
    --text-color: #e0e0e0;
    --border-color: rgba(0, 224, 179, 0.3);
    --glow-color: rgba(0, 224, 179, 0.5);
    --dark-text: #333;
  }

  /* Efek Latar Belakang */
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

  /* Card Styling */
  .card {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    box-shadow: 0 0 25px var(--glow-color);
    transition: transform 0.3s ease-in-out;
    height: 100%;
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
  }

  .card-header {
    /* Perbaikan Utama: Tambahkan display: flex, justify-content: center, dan align-items: center */
    display: flex;
    flex-direction: column; /* Konten di header akan disusun vertikal (karena ada <br>) */
    justify-content: center; /* Rata tengah vertikal */
    align-items: center; /* Rata tengah horizontal */
    
    /* Styling dari kode sebelumnya */
    text-align: center;
    font-size: 20px;
    font-weight: 700;
    background: transparent;
    border-bottom: 1px solid var(--border-color);
    padding: 20px;
    color: #fff;
    text-shadow: 0 0 5px var(--primary-color);
  }
  
  /* Hilangkan margin/padding yang mungkin mengganggu perataan vertikal pada elemen di dalam header (jika ada) */
  .card-header > * {
    margin: 0;
    padding: 0;
  }


  /* Time/Date Styling */
  .parent_date,
  .parent_clock {
    font-family: 'Orbitron', sans-serif;
  }

  .parent_date {
    display: flex;
    font-size: 20px;
    justify-content: center;
    color: var(--primary-color);
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
    margin-top: 5px;
  }

  .parent_clock div+div {
    margin-left: 5px;
  }

  /* Input Fields */
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

  /* Button Styling (Hadir/Masuk) */
  .btn {
    border: none;
    border-radius: 10px;
    padding: 15px 25px;
    font-weight: bold;
    letter-spacing: 1px;
    transition: all 0.3s ease;
  }

  .btn-green {
    background: linear-gradient(45deg, #00e0b3, #00a4d4) !important;
    box-shadow: 0 4px 15px rgba(0, 224, 179, 0.4);
    color: #fff;
  }

  .btn-green:hover {
    background: linear-gradient(45deg, #00a4d4, #00e0b3) !important;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 224, 179, 0.6);
  }

  /* Status Icon & Text */
  .fa-circle-check {
    color: var(--primary-color) !important;
    text-shadow: 0 0 10px var(--primary-color);
  }
  
  .text-success {
    color: var(--primary-color) !important;
  }
  
  h4 {
    color: var(--text-color);
  }

  #map {
    height: 300px;
    border-radius: 15px;
    border: 1px solid var(--border-color);
  }
</style>

<div class="page-body">
  <div class="futuristic-overlay"></div>
  <div class="container-xl">
    <div class="row justify-content-center">
      
      <div class="col-12 mb-4 d-flex justify-content-start">
        <img src="/assets/img/foto_pegawai/<?= $_SESSION['foto'] ?>" alt="Employee Photo" height="150">
      </div>

      <div class="col-12 col-md-8 col-lg-6"> 
        <div class="card text-center h-100">
          <div class="card-header">Daftar Hadir <br> <?php echo $alamat_lokasi; ?></div>
          <div class="card-body">

            <?php
            $id_pegawai = $_SESSION['id'];
            $tanggal_hari_ini = date("Y-m-d");

            $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi.event WHERE id_pegawai = '$id_pegawai' AND tanggal IS NULL");
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
              <form method="POST" action="<?= '../../pegawai/presensi/hadir.php' ?>">
                <input type="hidden" name="latitude_pegawai" id="latitude_pegawai" readonly>
                <input type="hidden" name="longitude_pegawai" id="longitude_pegawai" readonly>
                <input type="hidden" value="<?= $latitude_kantor ?>" name="latitude_kantor" readonly>
                <input type="hidden" value="<?= $longitude_kantor ?>" name="longitude_kantor" readonly>
                <input type="hidden" value="<?= $radius ?>" name="radius">
                <input type="hidden" value="<?= $zona_waktu ?>" name="zona_waktu">
                <input type="hidden" value="<?= date('Y-m-d') ?>" name="tanggal_masuk">
                <input type="hidden" value="<?= date('H:i:s') ?>" name="jam_masuk">
                <button type="submit" name="tombol_masuk" class="btn btn-green mt-3">Hadir</button>
              </form>
            <?php } else { ?>
              <i class="fa-regular fa-circle-check fa-4x text-success"></i>
              <h4 class="my-3">Anda telah mengisi <br> Daftar Hadir</h4>
            <?php } ?>
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
    document.getElementById("tanggal_masuk").innerHTML = String(waktu.getDate()).padStart(2, '0');
    document.getElementById("bulan_masuk").innerHTML = namaBulan[waktu.getMonth()];
    document.getElementById("tahun_masuk").innerHTML = waktu.getFullYear();
    document.getElementById("jam_masuk").innerHTML = String(waktu.getHours()).padStart(2, '0');
    document.getElementById("menit_masuk").innerHTML = String(waktu.getMinutes()).padStart(2, '0');
    document.getElementById("detik_masuk").innerHTML = String(waktu.getSeconds()).padStart(2, '0');
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
</script>

<?php include('../layout/footer.php'); ?>