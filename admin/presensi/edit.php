<?php
session_start();
ob_start();

// Redirect if not logged in or not an admin
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
    exit;
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
    exit;
}

$judul = "Edit Presensi";
include('../layout/header.php');
require_once('../../config.php');

// Inisialisasi variabel
$pesan_kesalahan = [];
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if ($id === 0) {
    // Jika ID tidak ditemukan di GET atau POST, arahkan kembali
    $_SESSION['validasi'] = 'ID presensi tidak ditemukan.';
    header("Location: rekap_harian.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    // Sanitasi data POST
    $id_post = intval($_POST['id']);
    $tanggal_masuk = !empty($_POST['tanggal_masuk']) ? $_POST['tanggal_masuk'] : null;
    $jam_masuk = !empty($_POST['jam_masuk']) ? $_POST['jam_masuk'] : null;
    $tanggal_keluar = !empty($_POST['tanggal_keluar']) ? $_POST['tanggal_keluar'] : null;
    $jam_keluar = !empty($_POST['jam_keluar']) ? $_POST['jam_keluar'] : null;
    $keterangan = !empty($_POST['keterangan']) ? $_POST['keterangan'] : null; // Ambil kolom baru

    // Build the SQL query dynamically
    $update_fields = [];
    $params = [];
    $types = "";

    if ($tanggal_masuk !== null) {
        $update_fields[] = "tanggal_masuk = ?";
        $params[] = $tanggal_masuk;
        $types .= "s";
    }

    if ($jam_masuk !== null) {
        $update_fields[] = "jam_masuk = ?";
        $params[] = $jam_masuk;
        $types .= "s";
    }

    if ($tanggal_keluar !== null) {
        $update_fields[] = "tanggal_keluar = ?";
        $params[] = $tanggal_keluar;
        $types .= "s";
    }

    if ($jam_keluar !== null) {
        $update_fields[] = "jam_keluar = ?";
        $params[] = $jam_keluar;
        $types .= "s";
    }
    
    // Tambahkan kolom KETERANGAN
    if ($keterangan !== null) {
        $update_fields[] = "keterangan = ?";
        $params[] = $keterangan;
        $types .= "s";
    }

    // Tambahkan ID untuk klausa WHERE
    $params[] = $id_post;
    $types .= "i";

    if (!empty($update_fields)) {
        $sql = "UPDATE presensi SET " . implode(", ", $update_fields) . " WHERE id = ?";
        
        $stmt = $connection->prepare($sql);
        
        // Panggil bind_param dengan tipe data dan parameter
        if (!empty($params)) {
             $stmt->bind_param($types, ...$params);
        }
       
        if ($stmt->execute()) {
            $_SESSION['berhasil'] = 'Data berhasil diupdate';
        } else {
            $_SESSION['validasi'] = 'Gagal mengupdate data. Error: ' . $stmt->error;
        }
        $stmt->close();
        
        // Arahkan kembali ke halaman rekap setelah pemrosesan
        header("Location: rekap_harian.php");
        exit;
    }
}


// Fetch presensi data with pegawai name
$stmt = $connection->prepare("
    SELECT 
        presensi.tanggal_masuk, 
        presensi.jam_masuk, 
        presensi.tanggal_keluar, 
        presensi.jam_keluar,
        presensi.keterangan, 
        pegawai.nama
    FROM presensi 
    LEFT JOIN pegawai ON presensi.id_pegawai = pegawai.id 
    WHERE presensi.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$presensi = $result->fetch_assoc();
$stmt->close();

if (!$presensi) {
    $_SESSION['validasi'] = 'Data presensi tidak ditemukan.';
    header("Location: rekap_harian.php");
    exit;
}

$nama = $presensi['nama'];
$tanggal_masuk = $presensi['tanggal_masuk'];
$jam_masuk = $presensi['jam_masuk'];
$tanggal_keluar = $presensi['tanggal_keluar'];
$jam_keluar = $presensi['jam_keluar'];
$keterangan_saat_ini = $presensi['keterangan']; // Ambil nilai keterangan saat ini
?>

<style>
    /* Variabel warna - Flat Design */
    :root {
        --primary-color: #2563eb;
        --primary-hover: #1d4ed8;
        --secondary-color: #64748b;
        --secondary-hover: #475569;
        --success-color: #22c55e;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --background-light: #ffffff;
        --border-color: #e5e7eb;
        --text-dark: #1f2937;
        --text-muted: #6b7280;
    }

    .page-body {
        padding: 20px;
        background-color: #f9fafb;
    }
    
    .container-xl {
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        max-width: 100%;
        width: 100%;
    }

    .btn {
        border-radius: 6px;
        padding: 10px 16px;
        font-weight: 500;
        font-size: 14px;
        text-transform: none;
        letter-spacing: 0;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        background: white;
        color: var(--text-dark);
    }

    .btn:hover {
        transform: none;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        border-color: var(--primary-hover);
        transform: none;
        box-shadow: none;
    }

    .btn-secondary {
        background: var(--secondary-color);
        color: white;
        border-color: var(--secondary-color);
    }

    .btn-secondary:hover {
        background: var(--secondary-hover);
        border-color: var(--secondary-hover);
        transform: none;
        box-shadow: none;
    }

    .card {
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: white;
        box-shadow: none;
    }

    .card-body {
        padding: 20px;
    }

    .form-label {
        font-weight: 500;
        color: var(--text-dark);
        text-transform: none;
        letter-spacing: 0;
        margin-bottom: 8px;
    }

    .form-control {
        border-radius: 6px;
        border: 1px solid var(--border-color);
        padding: 10px 12px;
        font-weight: 400;
        font-size: 14px;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: none;
        outline: none;
    }

    .alert {
        border-radius: 6px;
        border: 1px solid transparent;
        padding: 12px 16px;
        margin-bottom: 20px;
        font-weight: 400;
        text-transform: none;
        letter-spacing: 0;
    }

    .alert-success {
        background: #f0fdf4;
        border-color: var(--success-color);
        color: #166534;
    }

    .alert-danger {
        background: #fef2f2;
        border-color: var(--danger-color);
        color: #991b1b;
    }

    .nama-display {
        background: #f8fafc;
        color: var(--primary-color);
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 20px;
        padding: 12px 16px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        text-align: center;
        text-transform: none;
        letter-spacing: 0;
    }
</style>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <form action="edit.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card">
                        <div class="card-body">
                            <!-- Display Nama Pegawai -->
                            <div class="nama-display">
                                👤 <?= htmlspecialchars($nama) ?>
                            </div>

                            <?php if (isset($_SESSION['validasi'])): ?>
                                <div class="alert alert-danger">
                                    <?= $_SESSION['validasi'] ?>
                                </div>
                                <?php unset($_SESSION['validasi']); ?>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['berhasil'])): ?>
                                <div class="alert alert-success">
                                    <?= $_SESSION['berhasil'] ?>
                                </div>
                                <?php unset($_SESSION['berhasil']); ?>
                            <?php endif; ?>

                            <!-- Keterangan Izin/Cuti -->
                            <div class="mb-3">
                                <label for="keterangan" class="form-label">📝 Keterangan Izin/Cuti</label>
                                <select class="form-control" name="keterangan" id="keterangan">
                                    <option value="">--Pilih Keterangan--</option>
                                    <?php
                                    $options = [
                                        'Hadir' => 'Hadir', 
                                        'Terlambat' => 'Terlambat', 
                                        'Sakit' => 'Sakit', 
                                        'Surat Dokter' => 'Surat Dokter', 
                                        'Izin' => 'Izin', 
                                        'Cuti' => 'Cuti', 
                                        'Dinas Luar' => 'Dinas Luar', 
                                        'Work From Home' => 'Work From Home', 
                                        'Lainnya' => 'Lainnya'
                                    ];
                                    
                                    // Masukkan keterangan yang ada saat ini jika tidak ada di daftar
                                    if ($keterangan_saat_ini && !array_key_exists($keterangan_saat_ini, $options)) {
                                        $options[$keterangan_saat_ini] = $keterangan_saat_ini;
                                    }
                                    
                                    foreach ($options as $val => $label) {
                                        $selected = ($val == $keterangan_saat_ini) ? 'selected' : '';
                                        echo "<option value='{$val}' {$selected}>{$label}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Bidang Presensi Masuk -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tanggal_masuk" class="form-label">📅 Tanggal Masuk</label>
                                        <input type="date" class="form-control" name="tanggal_masuk" value="<?= htmlspecialchars($tanggal_masuk) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="jam_masuk" class="form-label">🕐 Jam Masuk</label>
                                        <input type="time" class="form-control" name="jam_masuk" value="<?= htmlspecialchars($jam_masuk) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Bidang Presensi Keluar -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tanggal_keluar" class="form-label">📅 Tanggal Keluar</label>
                                        <input type="date" class="form-control" name="tanggal_keluar" value="<?= htmlspecialchars($tanggal_keluar) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="jam_keluar" class="form-label">🕐 Jam Keluar</label>
                                        <input type="time" class="form-control" name="jam_keluar" value="<?= htmlspecialchars($jam_keluar) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                                <button type="submit" class="btn btn-primary" name="edit">💾 **Update Data**</button>
                                <a href="rekap_harian.php" class="btn btn-secondary">↩️ Kembali</a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include('../layout/footer.php'); ?>