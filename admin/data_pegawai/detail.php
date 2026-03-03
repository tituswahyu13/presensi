<?php
session_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}

$judul = "Detail Pegawai";
include('../layout/header.php');
require_once('../../config.php');

$id = $_GET["id"];
$result = mysqli_query($connection, "SELECT users.id_pegawai, users.username, users.password, users.status, users.role, pegawai.* FROM users JOIN pegawai ON users.id_pegawai = pegawai.id WHERE pegawai.id=$id");

// Fungsi untuk memisahkan teks setelah titik
function getTextAfterDot($text) {
    $parts = explode('.', $text);
    return trim(end($parts));
}
?>

<div class="page-body">
    <div class="container-xl">
        <?php while ($pegawai = mysqli_fetch_array($result)) : ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">Detail Informasi Pegawai</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <!-- Foto Pegawai -->
                                <div class="col-md-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center p-4">
                                            <div class="position-relative d-inline-block">
                                                <img style="width: 100%; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);" 
                                                     src="/assets/img/foto_pegawai/<?= $pegawai['foto'] ?>" 
                                                     alt="Employee Photo">
                                                <div class="position-absolute bottom-0 start-50 translate-middle-x mb-3">
                                                    <span class="badge bg-primary"><?= $pegawai['status'] ?></span>
                                                </div>
                                            </div>
                                            <h5 class="mt-3 mb-2"><?= $pegawai['nama'] ?></h5>
                                            <p class="text-muted mb-0"><?= $pegawai['jabatan'] ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Data Pribadi -->
                                <div class="col-md-8">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-header bg-light">
                                            <h4 class="card-title mb-0">Data Pribadi</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-borderless">
                                                    <tr>
                                                        <td style="width: 180px;">NIK</td>
                                                        <td style="width: 20px;">:</td>
                                                        <td><?= $pegawai['nik'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Alamat</td>
                                                        <td>:</td>
                                                        <td><?= $pegawai['alamat'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tanggal Lahir</td>
                                                        <td>:</td>
                                                        <td><?= $pegawai['lahir'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Agama</td>
                                                        <td>:</td>
                                                        <td><?= $pegawai['agama'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Golongan Darah</td>
                                                        <td>:</td>
                                                        <td><?= $pegawai['gol_dar'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Pendidikan</td>
                                                        <td>:</td>
                                                        <td><?= $pegawai['pendidikan'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Golongan</td>
                                                        <td>:</td>
                                                        <td><?= $pegawai['golongan'] ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4 g-4">
                                <!-- Data Kepegawaian -->
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-header bg-light">
                                            <h4 class="card-title mb-0">Data Kepegawaian</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-borderless">
                                                    <tr>
                                                        <td style="width: 180px;">Jabatan</td>
                                                        <td style="width: 20px;">:</td>
                                                        <td><?= $pegawai['jabatan'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Bagian/Sub Bagian</td>
                                                        <td>:</td>
                                                        <td><?= getTextAfterDot($pegawai['bagian']) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Awal Kerja</td>
                                                        <td>:</td>
                                                        <td><?= $pegawai['mulai_kerja'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tgl Pensiun</td>
                                                        <td>:</td>
                                                        <td><?= $pegawai['pensiun'] ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Data Akun -->
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-header bg-light">
                                            <h4 class="card-title mb-0">Data Akun</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-borderless">
                                                    <tr>
                                                        <td style="width: 180px;">Username</td>
                                                        <td style="width: 20px;">:</td>
                                                        <td><?= $pegawai['username'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Role</td>
                                                        <td>:</td>
                                                        <td><?= $pegawai['role'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Lokasi Presensi</td>
                                                        <td>:</td>
                                                        <td><?= $pegawai['lokasi_presensi'] ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include('../layout/footer.php'); ?>