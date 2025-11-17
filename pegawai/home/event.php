<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
  #map {
    height: 300px;
  }
</style>

<style>
  .card-body {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .card-header {
    text-align: center;
    margin: auto;
    font-size: 20px;
    font-weight: bold;
  }

  .parent_date {
    display: grid;
    grid-template-columns: auto auto auto auto auto;
    font-size: 25px;
    text-align: center;
    justify-content: center;
  }

  .parent_clock {
    display: grid;
    grid-template-columns: auto auto auto auto auto;
    font-size: 40px;
    text-align: center;
    font-weight: bold;
    justify-content: center;
  }

  #latitude_pegawai,
  #longitude_pegawai {
    width: 180px;
    padding: 8px;
    margin-bottom: 10px;
    text-align: center;
  }
</style>

<?php
session_start();
// date_default_timezone_set('Asia/Jakarta');
if (!isset($_SESSION["login"])) {
  header("Location: https://internal.pdamkotamagelang.com/absensi/auth/login.php?pesan=belum_login");
  exit();
} elseif ($_SESSION["role"] == "admin") {
  header("Location: https://internal.pdamkotamagelang.com/absensi/auth/login.php?pesan=tolak_akses");
  exit();
}

$judul = "Hai, " . $_SESSION["nama"]; // Ubah bagian ini untuk menampilkan nama pengguna

include('../layout/header.php');
include_once("../../config.php");

$username = isset($_SESSION["username"]) ? $_SESSION["username"] : '';
echo $username;

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

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">
    <div class="col">
      <div class="col-md-2"></div>
      <img src="/absensi/assets/img/foto_pegawai/<?= $_SESSION['foto'] ?>" alt="Employee Photo" width="100" height="150">
      <div class="col-md-4">
        <div class="card text-center h-50">
          <div class="card-header">Daftar Hadir <br> <?php echo $alamat_lokasi; ?></div>
          <div class="card-body">

            <?php
            $id_pegawai = $_SESSION['id'];
            $tanggal_hari_ini = date("Y-m-d");

            $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi.event WHERE id_pegawai = '$id_pegawai' AND tanggal IS NULL");
            // $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi.event WHERE id_pegawai = '$id_pegawai' AND tanggal_masuk = '$tanggal_hari_ini'"); //atau ganti tanggal_keluar IS NULL kalau shift beda hari
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
      
      <div class="col-md-2"></div>
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
    document.getElementById("jam_masuk").innerHTML = waktu.getHours();
    document.getElementById("menit_masuk").innerHTML = waktu.getMinutes();
    document.getElementById("detik_masuk").innerHTML = waktu.getSeconds();
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