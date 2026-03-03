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

  .btn-sm {
    padding: 8px 12px;
    font-size: 12px;
    height: 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  .btn-sm i {
    font-size: 16px;
    margin-bottom: 4px;
  }
</style>

<?php
session_start();
// date_default_timezone_set('Asia/Jakarta');
if (!isset($_SESSION["login"])) {
  header("Location: ../../auth/login.php?pesan=belum_login");
  exit();
} elseif ($_SESSION["role"] != "satpam") {
  header("Location: ../../auth/login.php?pesan=tolak_akses");
  exit();
}

$judul = "Hai, " . $_SESSION["nama"]; // Ubah bagian ini untuk menampilkan nama pengguna

include('../layout/header.php');
include_once("../../config.php");

$username = isset($_SESSION["username"]) ? $_SESSION["username"] : '';

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

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">
    <div class="col">
      <div class="col-md-2"></div>
      <img src="/assets/img/foto_pegawai/<?= $_SESSION['foto'] ?>" alt="Employee Photo" height="150">
      <div class="col-md-4">
        <div class="card text-center h-50">
          <div class="card-header">Presensi Masuk</div>
          <div class="card-body">

            <?php
            $id_pegawai = $_SESSION['id'];
            $tanggal_hari_ini = date("Y-m-d");

            $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_keluar IS NULL");
            // $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_masuk = '$tanggal_hari_ini'"); //atau ganti tanggal_keluar IS NULL kalau shift beda hari
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
              <form method="POST" action="<?= '../../pegawai/presensi/presensi_masuk_s.php' ?>">
                <input type="hidden" name="latitude_pegawai" id="latitude_pegawai" readonly>
                <input type="hidden" name="longitude_pegawai" id="longitude_pegawai" readonly>
                <input type="hidden" value="<?= $latitude_kantor ?>" name="latitude_kantor" readonly>
                <input type="hidden" value="<?= $longitude_kantor ?>" name="longitude_kantor" readonly>
                <input type="hidden" value="<?= $radius ?>" name="radius">
                <input type="hidden" value="<?= $zona_waktu ?>" name="zona_waktu">
                <input type="hidden" value="<?= date('Y-m-d') ?>" name="tanggal_masuk">
                <input type="hidden" value="<?= date('H:i:s') ?>" name="jam_masuk">
                <!-- <div class="form-group">
                  <label for="shift">Pilih Shift:</label>
                  <select name="shift" id="shift" class="form-control">
                    <option value="pagi">Shift Pagi</option>
                    <option value="sore">Shift Sore</option>
                  </select>
                </div> -->
                <div style="display: flex; gap: 10px;">
                  <!-- Button for "Pagi" -->
                  <button type="submit" name="tombol_masuk" class="btn btn-blue mt-3" formaction="../presensi/presensi_masuk_e.php">Pagi</button>

                  <!-- Button for "Sore" -->
                  <button type="submit" name="tombol_masuk" class="btn btn-orange mt-3" formaction="../presensi/presensi_masuk_f.php">Sore</button>

                  <!-- Button for "Malam" -->
                  <button type="submit" name="tombol_masuk" class="btn btn-purple mt-3" formaction="../presensi/presensi_masuk_g.php">Malam</button>
                </div>

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
            // $ambil_data_presensi = mysqli_query($connection, "SELECT * FROM presensi WHERE id_pegawai = '$id_pegawai' AND tanggal_masuk = '$tanggal_hari_ini'"); //atau ganti tanggal_keluar IS NULL kalau shift beda hari
            ?>

            <?php $waktu_sekarang = date("H:i:s");

            if (strtotime($waktu_sekarang) >= strtotime($jam_pulang) && mysqli_num_rows($ambil_data_presensi) == 0) { ?>
              <i class="fa-regular fa-circle-xmark fa-4x text-danger"></i>
              <h4 class="my-3">Anda belum melakukan<br>Presensi MASUK</h4>

            <?php } else { ?>

              <?php while ($cek_presensi_keluar = mysqli_fetch_array($ambil_data_presensi)) { ?>
                <?php if (($cek_presensi_keluar['tanggal_masuk']) && $cek_presensi_keluar['tanggal_keluar'] === NULL) { ?>

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

                <?php } ?>

              <?php } ?>

            <?php } ?>
          </div>
        </div>
      </div>
      <br />

      <!-- Tombol Ijin, Sakit, Dinas Luar -->
      <div class="col-md-4">
        <div class="card text-center h-50">
          <div class="card-header">Absensi</div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
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
                <!-- Modal Keterangan Izin -->
                <div class="modal" id="modalKeteranganIzin" tabindex="-1" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
                  <div style="background:#fff; margin:10% auto; padding:20px; border-radius:8px; width:300px; position:relative;">
                    <h5>Keterangan Izin</h5>
                    <textarea id="keterangan_izin" name="keterangan_izin" rows="3" style="width:100%;"></textarea>
                    <div style="margin-top:10px; text-align:right;">
                      <button type="button" onclick="submitIzin()" class="btn btn-success">Kirim</button>
                      <button type="button" onclick="closeModalIzin()" class="btn btn-secondary">Batal</button>
                    </div>
                  </div>
                </div>
                <!-- Modal Konfirmasi Sakit/Dinas -->
                <div class="modal" id="modalKonfirmasi" tabindex="-1" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
                  <div style="background:#fff; margin:15% auto; padding:20px; border-radius:8px; width:300px; position:relative; text-align:center;">
                    <h5>Konfirmasi</h5>
                    <p>Apakah Anda yakin ingin mengajukan?</p>
                    <div style="margin-top:10px;">
                      <button type="button" class="btn btn-success" onclick="submitKonfirmasi()">Ya</button>
                      <button type="button" class="btn btn-secondary" onclick="closeKonfirmasi()">Batal</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
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
              <div class="col-md-4">
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
    }
    closeKonfirmasi();
  }
</script>

<?php include('../layout/footer.php'); ?>