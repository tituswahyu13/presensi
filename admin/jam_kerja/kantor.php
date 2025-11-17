<?php
session_start();
ob_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
    exit;
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
    exit;
}

$judul = "Edit Jam Kerja Kantor";
include('../layout/header.php');
require_once('../../config.php');

if (isset($_POST["update"])) {
    $jam_masuk_senin = htmlspecialchars($_POST['jam_masuk_senin']);
    $jam_pulang_senin = htmlspecialchars($_POST['jam_pulang_senin']);
    $jam_masuk_selasa = htmlspecialchars($_POST['jam_masuk_selasa']);
    $jam_pulang_selasa = htmlspecialchars($_POST['jam_pulang_selasa']);
    $jam_masuk_rabu = htmlspecialchars($_POST['jam_masuk_rabu']);
    $jam_pulang_rabu = htmlspecialchars($_POST['jam_pulang_rabu']);
    $jam_masuk_kamis = htmlspecialchars($_POST['jam_masuk_kamis']);
    $jam_pulang_kamis = htmlspecialchars($_POST['jam_pulang_kamis']);
    $jam_masuk_jumat = htmlspecialchars($_POST['jam_masuk_jumat']);
    $jam_pulang_jumat = htmlspecialchars($_POST['jam_pulang_jumat']);
    $jam_masuk_sabtu = htmlspecialchars($_POST['jam_masuk_sabtu']);
    $jam_pulang_sabtu = htmlspecialchars($_POST['jam_pulang_sabtu']);

    $pesan_kesalahan = [];

    // Add any necessary validation checks here and append to $pesan_kesalahan array if needed

    if (!empty($pesan_kesalahan)) {
        $_SESSION['validasi'] = implode("<br>", $pesan_kesalahan);
    } else {
        // Use prepared statements to prevent SQL injection
        $query = "UPDATE jam_kerja SET 
                    jam_masuk_senin=?, jam_pulang_senin=?, 
                    jam_masuk_selasa=?, jam_pulang_selasa=?, 
                    jam_masuk_rabu=?, jam_pulang_rabu=?, 
                    jam_masuk_kamis=?, jam_pulang_kamis=?, 
                    jam_masuk_jumat=?, jam_pulang_jumat=?, 
                    jam_masuk_sabtu=?, jam_pulang_sabtu=? 
                  WHERE id=1";

        if ($stmt = mysqli_prepare($connection, $query)) {
            mysqli_stmt_bind_param(
                $stmt,
                'ssssssssssss',
                $jam_masuk_senin,
                $jam_pulang_senin,
                $jam_masuk_selasa,
                $jam_pulang_selasa,
                $jam_masuk_rabu,
                $jam_pulang_rabu,
                $jam_masuk_kamis,
                $jam_pulang_kamis,
                $jam_masuk_jumat,
                $jam_pulang_jumat,
                $jam_masuk_sabtu,
                $jam_pulang_sabtu
            );
            $result = mysqli_stmt_execute($stmt);

            if ($result) {
                $_SESSION['berhasil'] = "Data berhasil diupdate";
                header("Location: kantor.php");
                exit;
            } else {
                $_SESSION['validasi'] = "Data gagal diupdate: " . mysqli_error($connection);
            }

            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['validasi'] = "Data gagal diupdate: " . mysqli_error($connection);
        }
    }
}

$result = mysqli_query($connection, "SELECT * FROM jam_kerja WHERE id=1");

if ($jam = mysqli_fetch_array($result)) {
    $jam_masuk_senin = $jam['jam_masuk_senin'];
    $jam_pulang_senin = $jam['jam_pulang_senin'];
    $jam_masuk_selasa = $jam['jam_masuk_selasa'];
    $jam_pulang_selasa = $jam['jam_pulang_selasa'];
    $jam_masuk_rabu = $jam['jam_masuk_rabu'];
    $jam_pulang_rabu = $jam['jam_pulang_rabu'];
    $jam_masuk_kamis = $jam['jam_masuk_kamis'];
    $jam_pulang_kamis = $jam['jam_pulang_kamis'];
    $jam_masuk_jumat = $jam['jam_masuk_jumat'];
    $jam_pulang_jumat = $jam['jam_pulang_jumat'];
    $jam_masuk_sabtu = $jam['jam_masuk_sabtu'];
    $jam_pulang_sabtu = $jam['jam_pulang_sabtu'];
}
?>
<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <form action="/absensi/admin/jam_kerja/kantor.php" method="POST">
            <div class="row">
                <div class="card col-md-6">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="">Jam Masuk Senin</label>
                            <input type="time" class="form-control" name="jam_masuk_senin" value="<?= htmlspecialchars($jam_masuk_senin) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Jam Masuk Selasa</label>
                            <input type="time" class="form-control" name="jam_masuk_selasa" value="<?= htmlspecialchars($jam_masuk_selasa) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Jam Masuk Rabu</label>
                            <input type="time" class="form-control" name="jam_masuk_rabu" value="<?= htmlspecialchars($jam_masuk_rabu) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Jam Masuk Kamis</label>
                            <input type="time" class="form-control" name="jam_masuk_kamis" value="<?= htmlspecialchars($jam_masuk_kamis) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Jam Masuk Jum'at</label>
                            <input type="time" class="form-control" name="jam_masuk_jumat" value="<?= htmlspecialchars($jam_masuk_jumat) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Jam Masuk Sabtu</label>
                            <input type="time" class="form-control" name="jam_masuk_sabtu" value="<?= htmlspecialchars($jam_masuk_sabtu) ?>">
                        </div>
                    </div>
                </div>
                <div class="card col-md-6">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="">Jam Pulang Senin</label>
                            <input type="time" class="form-control" name="jam_pulang_senin" value="<?= htmlspecialchars($jam_pulang_senin) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Jam Pulang Selasa</label>
                            <input type="time" class="form-control" name="jam_pulang_selasa" value="<?= htmlspecialchars($jam_pulang_selasa) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Jam Pulang Rabu</label>
                            <input type="time" class="form-control" name="jam_pulang_rabu" value="<?= htmlspecialchars($jam_pulang_rabu) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Jam Pulang Kamis</label>
                            <input type="time" class="form-control" name="jam_pulang_kamis" value="<?= htmlspecialchars($jam_pulang_kamis) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Jam Pulang Jum'at</label>
                            <input type="time" class="form-control" name="jam_pulang_jumat" value="<?= htmlspecialchars($jam_pulang_jumat) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="">Jam Pulang Sabtu</label>
                            <input type="time" class="form-control" name="jam_pulang_sabtu" value="<?= htmlspecialchars($jam_pulang_sabtu) ?>">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="update" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php include('../layout/footer.php'); ?>