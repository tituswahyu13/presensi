<?php
session_start();
require_once('../../config.php');
require_once('../../vendor/autoload.php');

use Intervention\Image\ImageManagerStatic as Image;

$judul = "Data Pegawai";
include('../layout/header.php');

// Fungsi untuk kompresi gambar
function compressImage($source, $destination, $quality) {
    $img = Image::make($source)->encode('jpg', $quality);
    $img->save($destination);
}

// Cek login dan role
function checkUserAuthentication() {
    if (!isset($_SESSION["login"])) {
        header("Location: ../../auth/login.php?pesan=belum_login");
        exit;
    } elseif ($_SESSION["role"] != "admin") {
        header("Location: ../../auth/login.php?pesan=tolak_akses");
        exit;
    }
}

checkUserAuthentication();

// Fungsi untuk menangani upload dan kompresi gambar
function handleImageUpload($file) {
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileDirectory = "../../assets/img/foto_pegawai/" . $fileName;

    $maxFileSize = 2 * 1024 * 1024; // 2MB
    $maxQuality = 90;

    // Periksa ukuran file sebelum mengompresi
    if ($fileSize > $maxFileSize) {
        compressImage($fileTmp, $fileTmp, $maxQuality);
    }
    move_uploaded_file($fileTmp, $fileDirectory);
}

if (isset($_FILES['foto'])) {
    handleImageUpload($_FILES['foto']);
}

// Mendapatkan data pegawai dari database
function fetchEmployees($connection, $searchTerm = '') {
    $searchTerm = mysqli_real_escape_string($connection, $searchTerm);
    $query = "SELECT users.id_pegawai, users.username, users.password, users.status, users.role, pegawai.* 
              FROM users 
              JOIN pegawai ON users.id_pegawai = pegawai.id
              WHERE users.role != 'admin'";
              
    if (!empty($searchTerm)) {
        $query .= " AND pegawai.nama LIKE '%$searchTerm%'";
    }
    
    $query .= " ORDER BY CASE pegawai.jabatan
                        WHEN 'Direktur Utama' THEN 1
                        WHEN 'Direktur' THEN 2
                        WHEN 'Ketua' THEN 3
                        WHEN 'Manajer' THEN 4
                        WHEN 'Pengawas' THEN 5
                        WHEN 'Asisten Manajer' THEN 6
                        WHEN 'Kepala Unit' THEN 7
                        WHEN 'Komandan Regu' THEN 8
                        WHEN 'Staf' THEN 9
                        ELSE 10
                    END ASC,
                    pegawai.bagian ASC, 
                    pegawai.id ASC";
    return mysqli_query($connection, $query);
}

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$result = fetchEmployees($connection, $searchTerm);

?>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Data Pegawai</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="get" action="" class="d-flex">
                            <input type="text" class="form-control me-2" name="search" placeholder="Cari nama pegawai" value="<?= htmlspecialchars($searchTerm) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-search"></i> Cari
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="/admin/data_pegawai/tambah.php" class="btn btn-primary">
                            <i class="fa-solid fa-circle-plus"></i> Tambah Data Pegawai
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center" width="5%">No</th>
                                <th class="text-center" width="20%">NIK</th>
                                <th width="45%">Nama</th>
                                <th class="text-center" width="30%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) === 0) { ?>
                                <tr>
                                    <td colspan="4" class="text-center">Data tidak ditemukan</td>
                                </tr>
                            <?php } else { ?>
                                <?php $no = 1; while ($pegawai = mysqli_fetch_array($result)) : ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="text-center fw-medium"><?= htmlspecialchars($pegawai['nik']) ?></td>
                                        <td><?= htmlspecialchars($pegawai['nama']) ?></td>
                                        <td class="text-center">
                                            <a href="/admin/data_pegawai/detail.php?id=<?= htmlspecialchars($pegawai['id']) ?>" class="btn btn-sm btn-info">
                                                <i class="fa-solid fa-eye"></i> Detail
                                            </a>
                                            <a href="/admin/data_pegawai/edit.php?id=<?= htmlspecialchars($pegawai['id']) ?>" class="btn btn-sm btn-warning">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </a>
                                            <a href="/admin/data_pegawai/hapus.php?id=<?= htmlspecialchars($pegawai['id']) ?>" class="btn btn-sm btn-danger tombol-hapus">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../layout/footer.php'); ?>
