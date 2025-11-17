<?php
session_start();
if (!isset($_SESSION["login"])) {
  header("Location: https://internal.pdamkotamagelang.com/absensi/auth/login.php?pesan=belum_login");
  exit();
} elseif ($_SESSION["role"] != "pegawai") {
  header("Location: https://internal.pdamkotamagelang.com/absensi/auth/login.php?pesan=tolak_akses");
  exit();
}

include('../layout/header.php');
include_once("../../config.php");

$username = isset($_SESSION["username"]) ? $_SESSION["username"] : '';
echo $username;

$lokasi_presensi = $_SESSION['lokasi_presensi'];
$result = mysqli_query($connection, "SELECT * FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_presensi'");

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
  echo "Error: Timezone information not available.";
}

$id_pegawai = $_SESSION['id'];
$tanggal_hari_ini = date("Y-m-d");
$waktu_sekarang = date("H:i:s");

// Page Body berdasarkan lokasi_presensi.nama_lokasi
if ($lokasi_presensi == "Kantor PDAM") {
  ?>
  <div class="page-body">
    <div class="container-xl">
      <div class="col">
        <div class="col-md-2"></div>
        <img src="/absensi/assets/img/foto_pegawai/<?= $_SESSION['foto'] ?>" alt="Employee Photo" width="100" height="175">
        <div class="col-md-4">
          <div class="card text-center h-50">
            <div class="card-header">Presensi Masuk</div>
            <div class="card-body">

              <?php
              $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_masuk = '$tanggal_hari_ini'");

              if (mysqli_num_rows($cek_presensi_masuk) === 0) {
              ?>

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
                <i class="fa-regular fa-circle-check fa-4x text-success"></i>
                <h4 class="my-3">Anda telah melakukan <br> presensi MASUK</h4>
              <?php } ?>
            </div>
          </div>
        </div>
        <br />
        <div class="col-md-4">
          <div class="card text-center h-50">
            <div class="card-header">Presensi Keluar</div>
            <div class="card-body">

              <?php
              $ambil_data_presensi = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_masuk = '$tanggal_hari_ini'");

              if (strtotime($waktu_sekarang) >= strtotime($jam_pulang) && mysqli_num_rows($ambil_data_presensi) == 0) {
              ?>
                <i class="fa-regular fa-circle-xmark fa-4x text-danger"></i>
                <h4 class="my-3">Anda belum melakukan<br>Presensi MASUK</h4>
              <?php } else {
                while ($cek_presensi_keluar = mysqli_fetch_array($ambil_data_presensi)) {
                  if (($cek_presensi_keluar['tanggal_masuk']) && $cek_presensi_keluar['tanggal_keluar'] === NULL) {
              ?>
                    <div class="parent_date">
                      <div id="tanggal_keluar"></div>
                      <div class="ms-2"></div>
                      <div id="bulan_keluar"></div>
                      <div class="ms-2"></div>
                      <div id="tahun_keluar"></div>
                    </div>
                    <div class="parent_clock">
                      <div id="jam_keluar"></div>
                      <div class="ms-1">:</div>
                      <div id="menit_keluar"></div>
                      <div class="ms-1">:</div>
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
                    <i class="fa-regular fa-circle-check fa-4x text-success"></i>
                    <h4 class="my-3">Anda telah melakukan <br> presensi KELUAR</h4>
              <?php }
                }
              } ?>
            </div>
          </div>
        </div>
        <div class="col-md-2"></div>
      </div>
    </div>
  </div>
  <?php
} elseif ($lokasi_presensi == "Satpam") {
  ?>
  <div class="page-body">
    <div class="container-xl">
      <div class="col">
        <h2>Halaman Presensi untuk Satpam</h2>
        <div class="col-md-4">
          <div class="card text-center h-50">
            <div class="card-header">Presensi Masuk</div>
            <div class="card-body">

              <?php
              $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_keluar IS NULL");

              if (mysqli_num_rows($cek_presensi_masuk) === 0) {
              ?>
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
                  <div class="ms              jam_masuk"></div>
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
                <i class="fa-regular fa-circle-check fa-4x text-success"></i>
                <h4 class="my-3">Anda telah melakukan <br> presensi MASUK</h4>
              <?php } ?>
            </div>
          </div>
        </div>
        <br />
        <div class="col-md-4">
          <div class="card text-center h-50">
            <div class="card-header">Presensi Keluar</div>
            <div class="card-body">

              <?php
              $ambil_data_presensi = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_keluar IS NULL");

              if (strtotime($waktu_sekarang) >= strtotime($jam_pulang) && mysqli_num_rows($ambil_data_presensi) == 0) {
              ?>
                <i class="fa-regular fa-circle-xmark fa-4x text-danger"></i>
                <h4 class="my-3">Anda belum melakukan<br>Presensi MASUK</h4>
              <?php } else {
                while ($cek_presensi_keluar = mysqli_fetch_array($ambil_data_presensi)) {
                  if (($cek_presensi_keluar['tanggal_masuk']) && $cek_presensi_keluar['tanggal_keluar'] === NULL) {
              ?>
                    <div class="parent_date">
                      <div id="tanggal_keluar"></div>
                      <div class="ms-2"></div>
                      <div id="bulan_keluar"></div>
                      <div class="ms-2"></div>
                      <div id="tahun_keluar"></div>
                    </div>
                    <div class="parent_clock">
                      <div id="jam_keluar"></div>
                      <div class="ms-1">:</div>
                      <div id="menit_keluar"></div>
                      <div class="ms-1">:</div>
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
                    <i class="fa-regular fa-circle-check fa-4x text-success"></i>
                    <h4 class="my-3">Anda telah melakukan <br> presensi KELUAR</h4>
              <?php }
                }
              } ?>
            </div>
          </div>
        </div>
        <div class="col-md-2"></div>
      </div>
    </div>
  </div>
  <?php
} else {
  // Handle kondisi di mana nama_lokasi tidak dikenali
  echo "Lokasi tidak dikenali.";
}

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

  window.setTimeout("waktuKeluar()", 1000);
  namaBulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

  function waktuKeluar() {
    const waktu = new Date();
    setTimeout("waktuKeluar()", 1000);
    document.getElementById("tanggal_keluar").innerHTML = waktu.getDate();
    document.getElementById("bulan_keluar").innerHTML = namaBulan[waktu.getMonth()];
    document.getElementById("tahun_keluar").innerHTML = waktu.getFullYear();
    document.getElementById("jam_keluar").innerHTML = waktu.getHours();
    document.getElementById("menit_keluar").innerHTML = waktu.getMinutes();
    document.getElementById("detik_keluar").innerHTML = waktu.getSeconds();
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

include('../layout/footer.php');
?>

