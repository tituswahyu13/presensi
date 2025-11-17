<?php
session_start();
ob_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "shift") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}

$judul = "Edit Password";
include('../layout/header.php');
require_once('../../config.php');

// Periksa apakah pengguna telah login atau belum
if (!isset($_SESSION["nama"])) {
    header("Location: login.php");
    exit;
}

// Ambil id_pengguna dari session
$id_pengguna = $_SESSION["id"];

if (isset($_POST['edit'])) {

    $id = $_SESSION["id"];
    

    if (empty($_POST['password'])) {
        $password = $_POST['password_lama'];
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (empty($password)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Password wajib diisi";
        }
        if ($_POST['password'] != $_POST['ulang_password']) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Password tidak cocok";
        }

        if (!empty($pesan_kesalahan)) {
            $_SESSION['validasi'] = implode("<br>", $pesan_kesalahan);
        } else {

            $user = mysqli_query(
                $connection,
                "UPDATE users SET
                    password = '$password'
                    WHERE id_pegawai = '$id_pengguna'"
            );

            $_SESSION['berhasil'] = 'Data berhasil diupdate';
            header("Location: home.php");
            exit;
        }
    }
}

$id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
$result = mysqli_query($connection, "SELECT users.password WHERE pegawai.id = $id_pengguna");
while ($pegawai = mysqli_fetch_array($result)) {

    $password = $pegawai['password'];
}

?>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <form action="/absensi/shift/home/edit_password.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="">Password</label>
                                <input type="hidden" value="<?= $password ?>" name="password_lama">
                                <input type="password" class="form-control" name="password">
                            </div>

                            <div class="mb-3">
                                <label for="">Ulang Password</label>
                                <input type="password" class="form-control" name="ulang_password" value="">
                            </div>

                            <!-- <input type="text" value="<?= $id_pengguna ?>" name="id"> -->

                            <div>
                                <button type="submit" class="btn btn-primary" name="edit">Update</button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include('../layout/footer.php '); ?>