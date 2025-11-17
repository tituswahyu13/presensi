<?php
session_start();
ob_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
}

$judul = "Edit Pegawai";
include('../layout/header.php');
require_once('../../config.php');

if (isset($_POST['edit'])) {

    $id = $_POST['id'];
    $nik = htmlspecialchars($_POST['nik']);
    $nama = htmlspecialchars($_POST['nama']);
    // $jenis_kelamin = htmlspecialchars($_POST['jenis_kelamin']);
    $alamat = htmlspecialchars($_POST['alamat']);
    // Hitung tanggal pensiun (lahir + 56 tahun)
    $lahir = htmlspecialchars($_POST['lahir']); // Ambil nilai lahir dari formulir
    $lahir_date = date_create($lahir); // Konversi string lahir menjadi objek DateTime
    date_modify($lahir_date, '+56 years'); // Tambahkan 56 tahun ke tanggal lahir
    $pensiun = date_format($lahir_date, 'Y-m-d'); // Format ulang tanggal menjadi string untuk disimpan ke database
    $ttl = htmlspecialchars($_POST['ttl']);
    $mulai_kerja = htmlspecialchars($_POST['mulai_kerja']);
    // $kawin = htmlspecialchars($_POST['kawin']);
    // $jum_anak = htmlspecialchars($_POST['jum_anak']);
    // $jum_kel = htmlspecialchars($_POST['jum_kel']);
    // $agama = htmlspecialchars($_POST['agama']);
    $pendidikan = htmlspecialchars($_POST['pendidikan']);
    // $capeg = htmlspecialchars($_POST['capeg']);
    // $peg = htmlspecialchars($_POST['peg']);
    $golongan = htmlspecialchars($_POST['golongan']);
    $gol_dar = htmlspecialchars($_POST['gol_dar']);
    $bagian = htmlspecialchars($_POST['bagian']);
    $jabatan = htmlspecialchars($_POST['jabatan']);
    $status = htmlspecialchars($_POST['status']);
    $username = htmlspecialchars($_POST['username']);
    $role = htmlspecialchars($_POST['role']);
    $lokasi_presensi = htmlspecialchars($_POST['lokasi_presensi']);

    if (empty($_POST['password'])) {
        $password = $_POST['password_lama'];
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    if ($_FILES['foto_baru']['error'] === 4) {
        $nama_file = $_POST['foto_lama'];
    } else {
        if (isset($_FILES['foto_baru'])) {
            $file = $_FILES['foto_baru'];
            $nama_file = $file['name'];
            $file_tmp = $file['tmp_name'];
            $ukuran_file = $file['size'];
            $file_direktori = "../../assets/img/foto_pegawai/" . $nama_file;

            $ambil_ekstensi = pathinfo($nama_file, PATHINFO_EXTENSION);
            $ekstensi_diizinkan = ["jpg", "png", "jpeg"];
            $max_ukuran_file = 10 * 1024 * 1024;

            move_uploaded_file($file_tmp, $file_direktori);
        }
    }

    $foto = htmlspecialchars($nama_file);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (empty($nik)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> NIK wajib diisi";
        }
        if (empty($nama)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Nama wajib diisi";
        }
        // if (empty($jenis_kelamin)) {
        //     $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Jenis kelamin wajib diisi";
        // }
        if (empty($alamat)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Alamat wajib diisi";
        }
        if (empty($jabatan)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Jabatan wajib diisi";
        }
        // if (empty($status)) {
        //     $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Status wajib diisi";
        // }
        if (empty($username)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Username wajib diisi";
        }
        if (empty($lokasi_presensi)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Lokasi presensi wajib diisi";
        }
        if (empty($password)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Password wajib diisi";
        }
        if ($_POST['password'] != $_POST['ulang_password']) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Password tidak cocok";
        }
        if ($_FILES['foto_baru']['error'] !== 4) {
            if (!in_array(strtolower($ambil_ekstensi), $ekstensi_diizinkan)) {
                $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Hanya file JPG, JPEG dan PNG";
            }
            if ($ukuran_file > $max_ukuran_file) {
                $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Foto melebihi 10mb";
            }
        }

        if (!empty($pesan_kesalahan)) {
            $_SESSION['validasi'] = implode("<br>", $pesan_kesalahan);
        } else {
            // Sebelum menyimpan ke database
            if ($role == 'Admin') {
                $role = 'admin';
            }

            if ($role == 'Kantor') {
                $role = 'pegawai';
            }

            if ($role == 'Sumber') {
                $role = 'sumber';
            }

            if ($role == 'Tidar') {
                $role = 'tidar';
            }

            if ($role == 'Kalimas') {
                $role = 'kalimas';
            }

            if ($role == 'Sri Ponganten') {
                $role = 'sri_ponganten';
            }

            if ($role == 'Satpam') {
                $role = 'satpam';
            }

            $pegawai = mysqli_query(
                $connection,
                "UPDATE pegawai SET
                    nik = '$nik',
                    nama = '$nama',
                    -- jenis_kelamin = '$jenis_kelamin',
                    alamat = '$alamat',
                    lahir = '$lahir',
                    ttl = '$ttl',
                    mulai_kerja = '$mulai_kerja',
                    -- kawin = '$kawin',
                    -- jum_anak = '$jum_anak',
                    -- jum_kel = '$jum_kel',
                    -- agama = '$agama',
                    pendidikan = '$pendidikan',
                    -- capeg = '$capeg',
                    -- peg = '$peg',
                    pensiun = '$pensiun',
                    golongan = '$golongan',
                    gol_dar = '$gol_dar',
                    bagian = '$bagian',
                    jabatan = '$jabatan',
                    lokasi_presensi = '$lokasi_presensi',
                    foto = '$foto'
                    WHERE id = '$id'"
            );

            $user = mysqli_query(
                $connection,
                "UPDATE users SET
                    username = '$username',
                    password = '$password',
                    status = '$status'" . 
                    (!empty($role) ? ", role = '$role'" : "") . 
                    " WHERE id_pegawai = '$id'"
            );

            $_SESSION['berhasil'] = 'Data berhasil diupdate';
            header("Location: pegawai.php");
            exit;
        }
    }
}

$id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
$result = mysqli_query($connection, "SELECT users.id_pegawai, users.username, users.password, users.status, users.role, pegawai.* FROM users JOIN pegawai ON users.id_pegawai = pegawai.id WHERE pegawai.id = $id");
while ($pegawai = mysqli_fetch_array($result)) {
    $nik = $pegawai['nik'];
    $nama = $pegawai['nama'];
    // $jenis_kelamin = $pegawai['jenis_kelamin'];
    $alamat = $pegawai['alamat'];
    $lahir = $pegawai['lahir'];
    $ttl = $pegawai['ttl'];
    $mulai_kerja = $pegawai['mulai_kerja'];
    // $kawin = $pegawai['kawin'];
    // $jum_anak = $pegawai['jum_anak'];
    // $jum_kel = $pegawai['jum_kel'];
    // $agama = $pegawai['agama'];
    $pendidikan = $pegawai['pendidikan'];
    // $capeg = $pegawai['capeg'];
    // $peg = $pegawai['peg'];
    $pensiun = $pegawai['pensiun'];
    $golongan = $pegawai['golongan'];
    $gol_dar = $pegawai['gol_dar'];
    $bagian = $pegawai['bagian'];
    $jabatan = $pegawai['jabatan'];
    $status = $pegawai['status'];
    $username = $pegawai['username'];
    $password = $pegawai['password'];
    $role = $pegawai['role'];
    $lokasi_presensi = $pegawai['lokasi_presensi'];
    $foto = $pegawai['foto'];
}



?>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <form action="/absensi/admin/data_pegawai/edit.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="">NIK</label>
                                <input type="text" class="form-control" name="nik" value="<?= $nik ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama" value="<?= $nama ?>">
                            </div>

                            <!-- <div class="mb-3">
                                <label for="">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-control">
                                    <option value="">--Pilih Jenis Kelamin--</option>
                                    <option <?php if ($jenis_kelamin == 'Laki-laki') {
                                                echo 'selected';
                                            } ?> value="Laki-laki">Laki-laki</option>
                                    <option <?php if ($jenis_kelamin == 'Perempuan') {
                                                echo 'selected';
                                            } ?>value="Perempuan">Perempuan</option>
                                </select>
                            </div> -->

                            <div class="mb-3">
                                <label for="">Alamat</label>
                                <input type="text" class="form-control" name="alamat" value="<?= $alamat ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Tgl lahir</label>
                                <input type="date" class="form-control" name="lahir" value="<?= $lahir ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Tempat lahir</label>
                                <input type="text" class="form-control" name="ttl" value="<?= $ttl ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Awal kerja</label>
                                <input type="date" class="form-control" name="mulai_kerja" value="<?= $mulai_kerja ?>">
                            </div>

                            <!-- <div class="mb-3">
                                <label for="kawin">Status Perkawinan</label>
                                <select name="kawin" class="form-control">
                                    <option value="">--Pilih Status Perkawinan--</option>
                                    <option <?php if (isset($_POST['kawin']) && $_POST['kawin'] == 'Belum Menikah') {
                                                echo 'selected';
                                            } ?> value="Belum Menikah">Belum Menikah</option>
                                    <option <?php if (isset($_POST['kawin']) && $_POST['kawin'] == 'Sudah Menikah') {
                                                echo 'selected';
                                            } ?> value="Sudah Menikah">Sudah Menikah</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="">Jumlah anak</label>
                                <input type="text" class="form-control" name="jum_anak" value="<?= $jum_anak ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Jumlah keluarga</label>
                                <input type="text" class="form-control" name="jum_kel" value="<?= $jum_kel ?>">
                            </div> -->

                            <!-- <div class="mb-3">
                                <label for="agama">Agama</label>
                                <select name="agama" class="form-control">
                                    <option value="">--Pilih Agama--</option>
                                    <option <?php if (isset($_POST['agama']) && $_POST['agama'] == 'Islam') {
                                                echo 'selected';
                                            } ?> value="Islam">Islam</option>
                                    <option <?php if (isset($_POST['agama']) && $_POST['agama'] == 'Kristen') {
                                                echo 'selected';
                                            } ?> value="Kristen">Kristen</option>
                                    <option <?php if (isset($_POST['agama']) && $_POST['agama'] == 'Katholik') {
                                                echo 'selected';
                                            } ?> value="Katholik">Katholik</option>
                                    <option <?php if (isset($_POST['agama']) && $_POST['agama'] == 'Hindu') {
                                                echo 'selected';
                                            } ?> value="Hindu">Hindu</option>
                                    <option <?php if (isset($_POST['agama']) && $_POST['agama'] == 'Buddha') {
                                                echo 'selected';
                                            } ?> value="Buddha">Buddha</option>
                                    <option <?php if (isset($_POST['agama']) && $_POST['agama'] == 'Konghucu') {
                                                echo 'selected';
                                            } ?> value="Konghucu">Konghucu</option>
                                </select>
                            </div> -->

                            <div class="mb-3">
                                <label for="">Pendidikan</label>
                                <input type="text" class="form-control" name="pendidikan" value="<?= $pendidikan ?>">
                            </div>

                            <!-- <div class="mb-3">
                                <label for="">Tgl capeg</label>
                                <input type="date" class="form-control" name="capeg" value="<?= $capeg ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Tgl peg</label>
                                <input type="date" class="form-control" name="peg" value="<?= $peg ?>">
                            </div> -->

                            <div class="mb-3">
                                <label for="">Tgl perk. pensiun</label>
                                <input type="text" class="form-control" name="pensiun" value="<?= $pensiun ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="">Golongan</label>
                                <input type="text" class="form-control" name="golongan" value="<?= $golongan ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Gol darah</label>
                                <input type="text" class="form-control" name="gol_dar" value="<?= $gol_dar ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Jabatan</label>
                                <select name="jabatan" class="form-control">
                                    <option value="">--Pilih Jabatan--</option>

                                    <?php
                                    $ambil_jabatan = mysqli_query($connection, "SELECT * FROM jabatan ORDER BY id ASC");
                                    while ($row = mysqli_fetch_assoc($ambil_jabatan)) {
                                        $nama_jabatan = $row['jabatan'];
                                        if ($jabatan == $nama_jabatan) {
                                            echo '<option value="' . $nama_jabatan . '"
                                            selected="selected">' . $nama_jabatan . '</option>';
                                        } else {
                                            echo '<option value="' . $nama_jabatan . '">' . $nama_jabatan . '</option>';
                                        }
                                    }

                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="">Bagian/Sub Bagian</label>

                                <select name="bagian" class="form-control">
                                    <option value="">--Pilih Bagian/Sub Bagian--</option>
                                    <?php
                                    $ambil_bagian = mysqli_query($connection, "SELECT * FROM bagian ORDER BY id ASC");
                                    while ($row = mysqli_fetch_assoc($ambil_bagian)) {
                                        $nama_bagian = $row['bagian'];
                                        if ($bagian == $nama_bagian) {
                                            echo '<option value="' . $nama_bagian . '"
                                            selected="selected">' . $nama_bagian . '</option>';
                                        } else {
                                            echo '<option value="' . $nama_bagian . '">' . $nama_bagian . '</option>';
                                        }
                                    }

                                    ?>
                                </select>
                                <!-- <input type="text" class="form-control" name="bagian" value="<?= $bagian ?>"> -->
                            </div>

                            <div class="mb-3">
                                <label for="">Status</label>
                                <select name="status" class="form-control">
                                    <option value="">--Pilih Status--</option>
                                    <option <?php if ($status == 'Aktif') {
                                                echo 'selected';
                                            } ?> value="Aktif">Aktif</option>
                                    <option <?php if ($status == 'Tidak Aktif') {
                                                echo 'selected';
                                            } ?>value="Tidak Aktif">Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">

                            <div class="mb-3">
                                <label for="">Username</label>
                                <input type="text" class="form-control" name="username" value="<?= $username ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Password</label>
                                <input type="hidden" value="<?= $password ?>" name="password_lama">
                                <input type="password" class="form-control" name="password">
                            </div>

                            <div class="mb-3">
                                <label for="">Ulang Password</label>
                                <input type="password" class="form-control" name="ulang_password" value="">
                            </div>

                            <div class="mb-3">
                                <label for="">Jam Kerja</label>
                                <select name="role" class="form-control">
                                    <option value="">--Pilih Jam Kerja--</option>
                                    <option <?php if ($role == 'admin') { echo 'selected'; } ?> value="admin">Admin</option>
                                    <option <?php if ($role == 'pegawai') { echo 'selected'; } ?> value="pegawai">Kantor</option>
                                    <option <?php if ($role == 'sumber') { echo 'selected'; } ?> value="sumber">Sumber</option>
                                    <option <?php if ($role == 'tidar') { echo 'selected'; } ?> value="tidar">Tidar</option>
                                    <option <?php if ($role == 'kalimas') { echo 'selected'; } ?> value="kalimas">Kalimas</option>
                                    <option <?php if ($role == 'sri_ponganten') { echo 'selected'; } ?> value="sri_ponganten">Sri Ponganten</option>
                                    <option <?php if ($role == 'satpam') { echo 'selected'; } ?> value="satpam">Satpam</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="">Lokasi Presensi</label>
                                <select name="lokasi_presensi" class="form-control">
                                    <option value="">--Pilih Lokasi Presensi--</option>

                                    <?php
                                    $ambil_lok_presensi = mysqli_query($connection, "SELECT * FROM lokasi_presensi ORDER BY nama_lokasi ASC");
                                    while ($lokasi = mysqli_fetch_assoc($ambil_lok_presensi)) {
                                        $nama_lokasi = $lokasi['nama_lokasi'];
                                        if ($lokasi_presensi == $nama_lokasi) {
                                            echo '<option value="' . $nama_lokasi . '"
                                            selected="selected">' . $nama_lokasi . '</option>';
                                        } else {
                                            echo '<option value="' . $nama_lokasi . '">' . $nama_lokasi . '</option>';
                                        }
                                    }

                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="">Foto</label>
                                <input type="hidden" value="<?= $foto ?>" name="foto_lama">
                                <input type="file" class="form-control" name="foto_baru">
                            </div>

                            <input type="hidden" value="<?= $id ?>" name="id">

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

<?php include('../layout/footer.php'); ?>