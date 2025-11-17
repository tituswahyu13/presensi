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

$judul = "Edit Jam Kerja Shift";
include('../layout/header.php');
require_once('../../config.php');

if (isset($_POST["update"])) {
    $masuk_a = htmlspecialchars($_POST['masuk_a']);
    $pulang_a = htmlspecialchars($_POST['pulang_a']);
    $masuk_b = htmlspecialchars($_POST['masuk_b']);
    $pulang_b = htmlspecialchars($_POST['pulang_b']);
    $masuk_c = htmlspecialchars($_POST['masuk_c']);
    $pulang_c = htmlspecialchars($_POST['pulang_c']);
    $masuk_d = htmlspecialchars($_POST['masuk_d']);
    $pulang_d = htmlspecialchars($_POST['pulang_d']);
    $masuk_e = htmlspecialchars($_POST['masuk_e']);
    $pulang_e = htmlspecialchars($_POST['pulang_e']);
    $masuk_f = htmlspecialchars($_POST['masuk_f']);
    $pulang_f = htmlspecialchars($_POST['pulang_f']);
    $masuk_g = htmlspecialchars($_POST['masuk_g']);
    $pulang_g = htmlspecialchars($_POST['pulang_g']);
    $masuk_h = htmlspecialchars($_POST['masuk_h']);
    $pulang_h = htmlspecialchars($_POST['pulang_h']);
    $masuk_i = htmlspecialchars($_POST['masuk_i']);
    $pulang_i = htmlspecialchars($_POST['pulang_i']);

    $pesan_kesalahan = [];

    // Add any necessary validation checks here and append to $pesan_kesalahan array if needed

    if (!empty($pesan_kesalahan)) {
        $_SESSION['validasi'] = implode("<br>", $pesan_kesalahan);
    } else {
        // Use prepared statements to prevent SQL injection
        $query = "UPDATE shift SET 
                    masuk_a=?, pulang_a=?, 
                    masuk_b=?, pulang_b=?, 
                    masuk_c=?, pulang_c=?, 
                    masuk_d=?, pulang_d=?, 
                    masuk_e=?, pulang_e=?, 
                    masuk_f=?, pulang_f=?, 
                    masuk_g=?, pulang_g=?,
                    masuk_h=?, pulang_h=?, 
                    masuk_i=?, pulang_i=? 
                  WHERE id=1";

        if ($stmt = mysqli_prepare($connection, $query)) {
            mysqli_stmt_bind_param(
                $stmt,
                'ssssssssssssssssss',
                $masuk_a,
                $pulang_a,
                $masuk_b,
                $pulang_b,
                $masuk_c,
                $pulang_c,
                $masuk_d,
                $pulang_d,
                $masuk_e,
                $pulang_e,
                $masuk_f,
                $pulang_f,
                $masuk_g,
                $pulang_g,
                $masuk_h,
                $pulang_h,
                $masuk_i,
                $pulang_i
            );

            // Debugging: Print the values being bound
            error_log("Binding values: $masuk_a, $pulang_a, $masuk_b, $pulang_b, $masuk_c, $pulang_c, $masuk_d, $pulang_d, $masuk_e, $pulang_e, $masuk_f, $pulang_f, $masuk_g, $pulang_g, $masuk_h, $pulang_h, $masuk_i, $pulang_i");

            $result = mysqli_stmt_execute($stmt);

            if ($result) {
                $_SESSION['berhasil'] = "Data berhasil diupdate";
                header("Location: shift.php");
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

$result = mysqli_query($connection, "SELECT * FROM shift WHERE id=1");

if ($jam = mysqli_fetch_array($result)) {
    $masuk_a = $jam['masuk_a'];
    $pulang_a = $jam['pulang_a'];
    $masuk_b = $jam['masuk_b'];
    $pulang_b = $jam['pulang_b'];
    $masuk_c = $jam['masuk_c'];
    $pulang_c = $jam['pulang_c'];
    $masuk_d = $jam['masuk_d'];
    $pulang_d = $jam['pulang_d'];
    $masuk_e = $jam['masuk_e'];
    $pulang_e = $jam['pulang_e'];
    $masuk_f = $jam['masuk_f'];
    $pulang_f = $jam['pulang_f'];
    $masuk_g = $jam['masuk_g'];
    $pulang_g = $jam['pulang_g'];
    $masuk_h = $jam['masuk_h'];
    $pulang_h = $jam['pulang_h'];
    $masuk_i = $jam['masuk_i'];
    $pulang_i = $jam['pulang_i'];
}
?>

<style>
body {
    background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 100%);
    min-height: 100vh;
}
.card {
    border-radius: 1rem;
    box-shadow: 0 4px 24px 0 rgba(60,72,88,0.08);
    border: none;
    margin-bottom: 1.5rem;
}
.card-header {
    background: transparent;
    border-bottom: 1px solid #e5e7eb;
    padding: 1.5rem 1.5rem 1rem 1.5rem;
}
.card-title {
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0;
}
.shift-group {
    background: #f9fafb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e5e7eb;
}
.shift-group h5 {
    color: #374151;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.time-pair {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}
.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}
input[type="time"] {
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    font-size: 1rem;
    padding: 0.75rem;
    transition: all 0.2s;
}
input[type="time"]:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
}
.btn-primary {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    padding: 0.75rem 2rem;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
}
.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
}
.alert {
    border-radius: 0.75rem;
    border: none;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.alert-success {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #166534;
}
.alert-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}
.page-header {
    background: #fff;
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 12px rgba(60,72,88,0.06);
}
.page-header h1 {
    color: #2563eb;
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.page-header p {
    color: #6b7280;
    font-size: 1.1rem;
    margin-bottom: 0;
}
@media (max-width: 768px) {
    .time-pair {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    .shift-group {
        padding: 1rem;
    }
    .page-header {
        padding: 1.5rem;
    }
}
</style>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl py-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex align-items-center gap-3 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24">
                    <path stroke="#2563eb" stroke-width="2" d="M12 6v6l4 2"/>
                    <circle cx="12" cy="12" r="10" stroke="#2563eb" stroke-width="2"/>
                </svg>
                <h1 class="mb-0">Pengaturan Jam Kerja Shift</h1>
            </div>
            <p>Kelola jadwal kerja untuk berbagai lokasi dan posisi. Atur waktu masuk dan pulang sesuai kebutuhan operasional.</p>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['validasi'])) : ?>
            <div class="alert alert-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                <?= $_SESSION['validasi']; unset($_SESSION['validasi']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['berhasil'])) : ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
                <?= $_SESSION['berhasil']; unset($_SESSION['berhasil']); ?>
            </div>
        <?php endif; ?>

        <form action="/absensi/admin/jam_kerja/shift.php" method="POST">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-width="2" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                                </svg>
                                Jadwal Kerja Berdasarkan Lokasi
                            </h3>
                        </div>
                        <div class="card-body">
                            <!-- SUMBER -->
                            <div class="shift-group">
                                <h5>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zM7 6.5C7 7.328 6.552 8 6 8s-1-.672-1-1.5S5.448 5 6 5s1 .672 1 1.5zM4.285 9.567a.5.5 0 0 1 .683.183A3.498 3.498 0 0 0 8 11.5a3.498 3.498 0 0 0 3.032-1.75.5.5 0 1 1 .866.5A4.498 4.498 0 0 1 8 12.5a4.498 4.498 0 0 1-3.898-2.25.5.5 0 0 1 .183-.683zM10 8c-.552 0-1-.672-1-1.5S9.448 5 10 5s1 .672 1 1.5S10.552 8 10 8z"/>
                                    </svg>
                                    SUMBER
                                </h5>
                                <div class="time-pair">
                                    <div>
                                        <label class="form-label">Jam Masuk Pagi</label>
                                        <input type="time" class="form-control" name="masuk_a" value="<?= htmlspecialchars($masuk_a) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Jam Pulang Pagi</label>
                                        <input type="time" class="form-control" name="pulang_a" value="<?= htmlspecialchars($pulang_a) ?>">
                                    </div>
                                </div>
                                <div class="time-pair">
                                    <div>
                                        <label class="form-label">Jam Masuk Sore</label>
                                        <input type="time" class="form-control" name="masuk_b" value="<?= htmlspecialchars($masuk_b) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Jam Pulang Sore</label>
                                        <input type="time" class="form-control" name="pulang_b" value="<?= htmlspecialchars($pulang_b) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- TIDAR -->
                            <div class="shift-group">
                                <h5>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                    </svg>
                                    TIDAR
                                </h5>
                                <div class="time-pair">
                                    <div>
                                        <label class="form-label">Jam Masuk Pagi</label>
                                        <input type="time" class="form-control" name="masuk_c" value="<?= htmlspecialchars($masuk_c) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Jam Pulang Pagi</label>
                                        <input type="time" class="form-control" name="pulang_c" value="<?= htmlspecialchars($pulang_c) ?>">
                                    </div>
                                </div>
                                <div class="time-pair">
                                    <div>
                                        <label class="form-label">Jam Masuk Sore</label>
                                        <input type="time" class="form-control" name="masuk_d" value="<?= htmlspecialchars($masuk_d) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Jam Pulang Sore</label>
                                        <input type="time" class="form-control" name="pulang_d" value="<?= htmlspecialchars($pulang_d) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- KALIMAS -->
                            <div class="shift-group">
                                <h5>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                    </svg>
                                    KALIMAS
                                </h5>
                                <div class="time-pair">
                                    <div>
                                        <label class="form-label">Jam Masuk</label>
                                        <input type="time" class="form-control" name="masuk_h" value="<?= htmlspecialchars($masuk_h) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Jam Pulang</label>
                                        <input type="time" class="form-control" name="pulang_h" value="<?= htmlspecialchars($pulang_h) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- SRI PONGANTEN -->
                            <div class="shift-group">
                                <h5>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M9.5 6.5a1.5 1.5 0 0 1-1 1.415l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99a1.5 1.5 0 1 1 2-1.415z"/>
                                    </svg>
                                    SRI PONGANTEN
                                </h5>
                                <div class="time-pair">
                                    <div>
                                        <label class="form-label">Jam Masuk</label>
                                        <input type="time" class="form-control" name="masuk_i" value="<?= htmlspecialchars($masuk_i) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Jam Pulang</label>
                                        <input type="time" class="form-control" name="pulang_i" value="<?= htmlspecialchars($pulang_i) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- SATPAM -->
                            <div class="shift-group">
                                <h5>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zM3 6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6z"/>
                                    </svg>
                                    SATPAM (Security)
                                </h5>
                                <div class="time-pair">
                                    <div>
                                        <label class="form-label">Jam Masuk Pagi</label>
                                        <input type="time" class="form-control" name="masuk_e" value="<?= htmlspecialchars($masuk_e) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Jam Pulang Pagi</label>
                                        <input type="time" class="form-control" name="pulang_e" value="<?= htmlspecialchars($pulang_e) ?>">
                                    </div>
                                </div>
                                <div class="time-pair">
                                    <div>
                                        <label class="form-label">Jam Masuk Sore</label>
                                        <input type="time" class="form-control" name="masuk_f" value="<?= htmlspecialchars($masuk_f) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Jam Pulang Sore</label>
                                        <input type="time" class="form-control" name="pulang_f" value="<?= htmlspecialchars($pulang_f) ?>">
                                    </div>
                                </div>
                                <div class="time-pair">
                                    <div>
                                        <label class="form-label">Jam Masuk Malam</label>
                                        <input type="time" class="form-control" name="masuk_g" value="<?= htmlspecialchars($masuk_g) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Jam Pulang Malam</label>
                                        <input type="time" class="form-control" name="pulang_g" value="<?= htmlspecialchars($pulang_g) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="row mt-4">
                <div class="col-12 text-end">
                    <button type="submit" name="update" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-width="2" d="M5 12l5 5L20 7"/>
                        </svg>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include('../layout/footer.php'); ?>