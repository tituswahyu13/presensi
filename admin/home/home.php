<?php
session_start();
if (!isset($_SESSION["login"])) {
  header("Location: ../../auth/login.php?pesan=belum_login");
  exit();
} elseif ($_SESSION["role"] != "admin") {
  header("Location: ../../auth/login.php?pesan=tolak_akses");
  exit();
}

$judul = "Home";
include('../layout/header.php');

// Connect to the database
require_once('../../config.php');

$pegawai_query = "SELECT pegawai.*, users.status 
                  FROM pegawai 
                  JOIN users ON pegawai.id = users.id_pegawai 
                  WHERE users.status = 'Aktif' AND users.role != 'admin'";
$pegawai_result = mysqli_query($connection, $pegawai_query);
$total_pegawai_aktif = mysqli_num_rows($pegawai_result);

$tanggal_hari_ini = date('Y-m-d');
$hadir_query = "SELECT presensi.* 
                FROM presensi 
                WHERE presensi.tanggal_masuk = '$tanggal_hari_ini'";
$hadir_result = mysqli_query($connection, $hadir_query);
$total_pegawai_hadir = mysqli_num_rows($hadir_result);
$total_pegawai_alpha = $total_pegawai_aktif - $total_pegawai_hadir;
?>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">
    <div class="row justify-content-center my-4">
      <div class="col-auto">
        <a href="tambah_presensi.php" class="btn btn-primary">Input Presensi</a>
      </div>
      <div class="col-auto">
        <a href="izin_cuti.php" class="btn btn-primary">Input Izin & Cuti</a>
      </div>
    </div>
    <div class="row row-deck row-cards">
      <div class="col-12">
        <div class="row row-cards">
          <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-auto">
                    <span class="bg-primary text-white avatar">
                      <!-- Icon for total employees -->
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users-group" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1" />
                        <path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M17 10h2a2 2 0 0 1 2 2v1" />
                        <path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M3 13v-1a2 2 0 0 1 2 -2h2" />
                      </svg>
                    </span>
                  </div>
                  <div class="col">
                    <div class="font-weight-medium">Jumlah Karyawan</div>
                    <div class="text-muted"><?= $total_pegawai_aktif . ' orang' ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Repeat similar structure for other statistics (Hadir, Alpa, Sakit/Izin/Cuti) -->
          <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-auto">
                    <span class="bg-green text-white avatar">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                        <path d="M15 19l2 2l4 -4" />
                      </svg>
                    </span>
                  </div>
                  <div class="col">
                    <div class="font-weight-medium">Jumlah Hadir</div>
                    <div class="text-muted"><?= $total_pegawai_hadir . ' orang' ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-auto">
                    <span class="bg-red text-white avatar">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-x" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                        <path d="M6 21v-2a4 4 0 0 1 4 -4h3.5" />
                        <path d="M22 22l-5 -5" />
                        <path d="M17 22l5 -5" />
                      </svg>
                    </span>
                  </div>
                  <div class="col">
                    <div class="font-weight-medium">Jumlah Alpa</div>
                    <div class="text-muted"><?= $total_pegawai_alpha . ' orang' ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-auto">
                    <span class="bg-pink text-white avatar">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-heart" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                        <path d="M6 21v-2a4 4 0 0 1 4 -4h.5" />
                        <path d="M18 22l3.35 -3.284a2.143 2.143 0 0 0 .005 -3.071a2.242 2.242 0 0 0 -3.129 -.006l-.224 .22l-.223 -.22a2.242 2.242 0 0 0 -3.128 -.006a2.143 2.143 0 0 0 -.006 3.071l3.355 3.296z" />
                      </svg>
                    </span>
                  </div>
                  <div class="col">
                    <div class="font-weight-medium">Jumlah Sakit/Izin/Cuti</div>
                    <div class="text-muted">0 orang</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('../layout/footer.php'); ?>