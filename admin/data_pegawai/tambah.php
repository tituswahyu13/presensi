<?php
session_start();
ob_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
    exit();
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
    exit();
}

$judul = "Tambah Pegawai";
include('../layout/header.php');
require_once('../../config.php');

if (isset($_POST['submit'])) {
    // Sanitize input
    $nik = htmlspecialchars($_POST['nik']);
    $nama = htmlspecialchars($_POST['nama']);
    $alamat = htmlspecialchars($_POST['alamat']);
    $lahir = htmlspecialchars($_POST['lahir']);
    $ttl = htmlspecialchars($_POST['ttl']);
    $awal_kerja = htmlspecialchars($_POST['awal_kerja']);
    $agama = htmlspecialchars($_POST['agama']);
    $pendidikan = htmlspecialchars($_POST['pendidikan']);
    $golongan = htmlspecialchars($_POST['golongan']);
    $gol_darah = htmlspecialchars($_POST['gol_darah']);
    $bagian = htmlspecialchars($_POST['bagian']);
    $jabatan = htmlspecialchars($_POST['jabatan']);
    $status = htmlspecialchars($_POST['status']);
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $ulang_password = $_POST['ulang_password'];
    $role = htmlspecialchars($_POST['role']);
    $lokasi_presensi = htmlspecialchars($_POST['lokasi_presensi']);

    // Calculate retirement date
    $lahir_date = date_create($lahir);
    date_modify($lahir_date, '+56 years');
    $pensiun = date_format($lahir_date, 'Y-m-d');

    // Initialize error array
    $pesan_kesalahan = [];

    // Validate required fields
    $required_fields = [
        'nik' => $nik, 'nama' => $nama, 'alamat' => $alamat,
        'jabatan' => $jabatan, 'status' => $status, 'username' => $username, 'role' => $role,
        'lokasi_presensi' => $lokasi_presensi, 'password' => $password
    ];

    foreach ($required_fields as $field => $value) {
        if (empty($value)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> " . ucfirst($field) . " wajib diisi";
        }
    }

    // Password validation
    if ($password !== $ulang_password) {
        $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Password tidak cocok";
    } else {
        $password = password_hash($password, PASSWORD_DEFAULT);
    }

    // Ganti nilai role sesuai dengan label yang diinginkan
    if ($role == 'Admin') {
        $role = 'admin'; // Ganti 'Kantor' dengan 'pegawai'
    }

    if ($role == 'Kantor') {
        $role = 'pegawai'; // Ganti 'Kantor' dengan 'pegawai'
    }

    if ($role == 'Sumber') {
        $role = 'sumber'; // Ganti 'Sumber' dengan 'sumber'
    }

    if ($role == 'Tidar') {
        $role = 'tidar'; // Ganti 'Tidar' dengan 'tidar'
    }

    if ($role == 'Kalimas') {
        $role = 'kalimas'; // Ganti 'Sumber' dengan 'sumber'
    }

    if ($role == 'Sri Ponganten') {
        $role = 'sri_ponganten'; // Ganti 'Tidar' dengan 'tidar'
    }

    if ($role == 'Satpam') {
        $role = 'satpam'; // Ganti 'Satpam' dengan 'satpam'
    }

    // File upload validation
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $nama_file = $file['name'];
        $file_tmp = $file['tmp_name'];
        $ukuran_file = $file['size'];
        $file_direktori = "../../assets/img/foto_pegawai/" . $nama_file;
        $ambil_ekstensi = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        $ekstensi_diizinkan = ["jpg", "png", "jpeg"];
        $max_ukuran_file = 20 * 1024 * 1024;

        if (!in_array($ambil_ekstensi, $ekstensi_diizinkan)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Hanya file JPG, JPEG dan PNG";
        } elseif ($ukuran_file > $max_ukuran_file) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Foto melebihi 20 MB";
        } elseif (!move_uploaded_file($file_tmp, $file_direktori)) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Gagal mengunggah foto";
        } else {
            $foto = htmlspecialchars($nama_file);
        }
    }

    if (empty($pesan_kesalahan)) {
        // Use prepared statements to insert data into the database
        $stmt = $connection->prepare(
            "INSERT INTO pegawai (nik, nama, alamat, lahir, ttl, mulai_kerja, agama, pendidikan, golongan, gol_dar, bagian, jabatan, lokasi_presensi, foto, pensiun) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if ($stmt === false) {
            $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Error preparing statement: " . $connection->error;
        } else {
            $stmt->bind_param(
                "sssssssssssssss",
                $nik,
                $nama,
                $alamat,
                $lahir,
                $ttl,
                $awal_kerja,
                $agama,
                $pendidikan,
                $golongan,
                $gol_darah,
                $bagian,
                $jabatan,
                $lokasi_presensi,
                $foto,
                $pensiun
            );

            if ($stmt->execute()) {
                $id_pegawai = $stmt->insert_id;
                $stmt->close();

                $stmt = $connection->prepare(
                    "INSERT INTO users (id_pegawai, username, password, status, role) 
                    VALUES (?, ?, ?, ?, ?)"
                );
                if ($stmt === false) {
                    $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Error preparing statement: " . $connection->error;
                } else {
                    $stmt->bind_param("issss", $id_pegawai, $username, $password, $status, $role);

                    if ($stmt->execute()) {
                        $_SESSION['berhasil'] = 'Data berhasil disimpan';
                        header("Location: pegawai.php");
                        exit();
                    } else {
                        $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Gagal menyimpan data pengguna: " . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Gagal menyimpan data pegawai: " . $stmt->error;
            }
        }
    }

    if (!empty($pesan_kesalahan)) {
        $_SESSION['validasi'] = implode("<br>", $pesan_kesalahan);
    }
}
?>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <form action="/admin/data_pegawai/tambah.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="">NIK</label>
                                <input type="text" class="form-control" name="nik" value="<?php if (isset($_POST['nik'])) echo $_POST['nik'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama" value="<?php if (isset($_POST['nama'])) echo $_POST['nama'] ?>">
                            </div>

                            <!-- <div class="mb-3">
                                <label for="">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-control">
                                    <option value="">--Pilih Jenis Kelamin--</option>
                                    <option <?php if (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Laki-laki') {
                                                echo 'selected';
                                            } ?> value="Laki-laki">Laki-laki</option>
                                    <option <?php if (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Perempuan') {
                                                echo 'selected';
                                            } ?>value="Perempuan">Perempuan</option>
                                </select>
                            </div> -->

                            <div class="mb-3">
                                <label for="">Alamat</label>
                                <input type="text" class="form-control" name="alamat" value="<?php if (isset($_POST['alamat'])) echo $_POST['alamat'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Tgl lahir</label>
                                <input type="date" class="form-control" name="lahir" value="<?php if (isset($_POST['lahir'])) echo $_POST['lahir'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Tempat lahir</label>
                                <input type="text" class="form-control" name="ttl" value="<?php if (isset($_POST['ttl'])) echo $_POST['ttl'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Awal kerja</label>
                                <input type="date" class="form-control" name="awal_kerja" value="<?php if (isset($_POST['awal_kerja'])) echo $_POST['awal_kerja'] ?>">
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
                                <input type="text" class="form-control" name="anak" value="<?php if (isset($_POST['anak'])) echo $_POST['anak'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Jumlah keluarga</label>
                                <input type="text" class="form-control" name="keluarga" value="<?php if (isset($_POST['keluarga'])) echo $_POST['keluarga'] ?>">
                            </div> -->

                            <div class="mb-3">
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
                            </div>

                            <div class="mb-3">
                                <label for="">Pendidikan</label>
                                <input type="text" class="form-control" name="pendidikan" value="<?php if (isset($_POST['pendidikan'])) echo $_POST['pendidikan'] ?>">
                            </div>

                            <!-- <div class="mb-3">
                                <label for="">Tgl capeg</label>
                                <input type="date" class="form-control" name="capeg" value="<?php if (isset($_POST['capeg'])) echo $_POST['capeg'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Tgl peg</label>
                                <input type="date" class="form-control" name="peg" value="<?php if (isset($_POST['peg'])) echo $_POST['peg'] ?>">
                            </div> -->

                            <div class="mb-3">
                                <label for="">Golongan</label>
                                <input type="text" class="form-control" name="golongan" value="<?php if (isset($_POST['golongan'])) echo $_POST['golongan'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Gol darah</label>
                                <input type="text" class="form-control" name="gol_darah" value="<?php if (isset($_POST['gol_darah'])) echo $_POST['gol_darah'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Jabatan</label>
                                <select name="jabatan" class="form-control">
                                    <option value="">--Pilih Jabatan--</option>

                                    <?php
                                    $ambil_jabatan = mysqli_query($connection, "SELECT * FROM jabatan ORDER BY id ASC");
                                    while ($jabatan = mysqli_fetch_assoc($ambil_jabatan)) {
                                        $nama_jabatan = $jabatan['jabatan'];
                                        if (isset($_POST['jabatan']) && $_POST['jabatan'] == $nama_jabatan) {
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
                                <label for="">Bagian</label>
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
                                <!-- <input type="text" class="form-control" name="bagian" value="<?php if (isset($_POST['bagian'])) echo $_POST['bagian'] ?>"> -->
                            </div>

                            <div class="mb-3">
                                <label for="">Status</label>
                                <select name="status" class="form-control">
                                    <option value="">--Pilih Status--</option>
                                    <option <?php if (isset($_POST['status']) && $_POST['status'] == 'Aktif') {
                                                echo 'selected';
                                            } ?> value="Aktif">Aktif</option>
                                    <option <?php if (isset($_POST['status']) && $_POST['status'] == 'Tidak Aktif') {
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
                                <input type="text" class="form-control" name="username" value="<?php if (isset($_POST['username'])) echo $_POST['username'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="">Password</label>
                                <input type="password" class="form-control" name="password" value="">
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
                                        if (isset($_POST['lokasi_presensi']) && $_POST['lokasi_presensi'] == $nama_lokasi) {
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
                                <input type="file" class="form-control" name="foto">
                            </div>

                            <div>
                                <button type="submit" class="btn btn-primary" name="submit">Simpan</button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include('../layout/footer.php'); ?>