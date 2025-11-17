<?php
session_start();
ob_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] == "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}

$judul = "Profil";
include('../layout/header.php');
require_once('../../config.php');

function getTextAfterDot($text) {
    $parts = explode('.', $text);
    return isset($parts[1]) ? $parts[1] : '';
}

function getRoleText($role) {
    switch ($role) {
        case 'pegawai':
            return 'Kantor';
        case 'sumber':
            return 'Sumber';
        case 'tidar':
            return 'Tidar';
        case 'satpam':
            return 'Satpam';
        default:
            return $role; // Mengembalikan nilai asli jika tidak ada yang cocok
    }
}

// Ambil id_pengguna dari session
$id_pengguna = $_SESSION["id"];
$result = mysqli_query($connection, "SELECT users.id_pegawai, users.username, users.password, users.status, users.role, pegawai.* FROM users JOIN pegawai ON users.id_pegawai = pegawai.id WHERE pegawai.id=$id_pengguna");
?>

<!-- Tambahkan link ke Bootstrap CSS di header.php -->
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

<div class="page-body">
    <div class="container-xl">
        <?php while ($pegawai = mysqli_fetch_array($result)) : ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <span class="avatar avatar-xl rounded-circle border" style="
                                background-image: url('/absensi/assets/img/foto_pegawai/<?= $pegawai['foto'] ?>');
                                background-position: top center;
                                background-size: cover;
                                background-repeat: no-repeat;
                                width: 100px;
                                height: 100px;
                            "></span>
                        </div>
                        <div class="col">
                            <h2 class="mb-0"><?= $pegawai['nama'] ?></h2>
                            <p class="text-muted"><?= $pegawai['jabatan'] ?> - <?= getTextAfterDot($pegawai['bagian']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title">Informasi Pribadi</h3>
                        </div>
                        <div class="card-body">
                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">NIK</div>
                                    <div class="datagrid-content"><?= $pegawai['nik'] ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Alamat</div>
                                    <div class="datagrid-content"><?= $pegawai['alamat'] ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Tanggal Lahir</div>
                                    <div class="datagrid-content"><?= $pegawai['lahir'] ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Agama</div>
                                    <div class="datagrid-content"><?= $pegawai['agama'] ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Pendidikan</div>
                                    <div class="datagrid-content"><?= $pegawai['pendidikan'] ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Golongan Darah</div>
                                    <div class="datagrid-content"><?= $pegawai['gol_dar'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title">Informasi Pekerjaan</h3>
                        </div>
                        <div class="card-body">
                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Username</div>
                                    <div class="datagrid-content"><?= $pegawai['username'] ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Jam Kerja</div>
                                    <div class="datagrid-content"><?= getRoleText($pegawai['role']) ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Lokasi Presensi</div>
                                    <div class="datagrid-content"><?= $pegawai['lokasi_presensi'] ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Status</div>
                                    <div class="datagrid-content"><?= $pegawai['status'] ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Awal Kerja</div>
                                    <div class="datagrid-content"><?= $pegawai['mulai_kerja'] ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Golongan</div>
                                    <div class="datagrid-content"><?= $pegawai['golongan'] ?></div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Tgl Perkiraan Pensiun</div>
                                    <div class="datagrid-content"><?= $pegawai['pensiun'] ?></div>
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