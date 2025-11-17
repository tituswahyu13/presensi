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

$judul = "Izin & Cuti +";
include('../layout/header.php');
require_once('../../config.php');

// --- LOGIKA PEMROSESAN FORM YANG DIMODIFIKASI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    // 1. Ambil Karyawan Terpilih
    $selected_employees = $_POST['selected_employees'] ?? [];
    
    // 2. Ambil Pengaturan Global Tanggal dan Keterangan
    $tgl_dari = $_POST['tgl_dari_hidden'] ?? '';
    $tgl_sampai = $_POST['tgl_sampai_hidden'] ?? '';
    $global_keterangan = $_POST['global_keterangan_hidden'] ?? '';
    
    // Jam Masuk diatur default karena dihilangkan dari input
    $default_jam_masuk = '00:00:00'; 
    $default_foto = 'izin_default.png'; // Ganti dengan nama file default yang sesuai

    if (empty($selected_employees)) {
        $_SESSION['validasi'] = "Pilih minimal satu karyawan.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (empty($tgl_dari) || empty($tgl_sampai) || empty($global_keterangan)) {
        $_SESSION['validasi'] = "Tanggal Dari, Tanggal Sampai, dan Keterangan wajib diisi.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Hitung rentang tanggal
    try {
        $start_date = new DateTime($tgl_dari);
        $end_date = new DateTime($tgl_sampai);
        $end_date->modify('+1 day'); // Agar tanggal_sampai ikut terhitung
        $period = new DatePeriod($start_date, new DateInterval('P1D'), $end_date);
    } catch (Exception $e) {
        $_SESSION['validasi'] = "Format tanggal tidak valid.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $dates_to_process = [];
    foreach ($period as $date) {
        $dates_to_process[] = $date->format('Y-m-d');
    }

    if (empty($dates_to_process)) {
         $_SESSION['validasi'] = "Rentang tanggal tidak valid atau kosong.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    foreach ($selected_employees as $nama_karyawan) {
        
        // Fetch employee id
        $stmt_id = $connection->prepare("SELECT id FROM pegawai WHERE nama = ?");
        $stmt_id->bind_param("s", $nama_karyawan);
        $stmt_id->execute();
        $stmt_id->bind_result($id_pegawai);
        $stmt_id->fetch();
        $stmt_id->close();

        if ($id_pegawai) {
            foreach ($dates_to_process as $tgl_proses) {
                // Check if attendance already exists for this employee on this date
                $stmt_check = $connection->prepare("SELECT id FROM presensi WHERE id_pegawai = ? AND tanggal_masuk = ?");
                $stmt_check->bind_param("is", $id_pegawai, $tgl_proses);
                $stmt_check->execute();
                $stmt_check->store_result();
                
                if ($stmt_check->num_rows > 0) {
                    $errors[] = "Izin/Cuti untuk **$nama_karyawan** pada tanggal **" . date('d M', strtotime($tgl_proses)) . "** sudah ada.";
                    $error_count++;
                } else {
                    // Insert the attendance record
                    $stmt_insert = $connection->prepare("INSERT INTO presensi (id_pegawai, tanggal_masuk, jam_masuk, foto_masuk, keterangan) VALUES (?, ?, ?, ?, ?)");
                    
                    // Gunakan tanggal_masuk sebagai jadwal_tanggal (tgl_proses)
                    if ($stmt_insert->bind_param("issss", $id_pegawai, $tgl_proses, $default_jam_masuk, $default_foto, $global_keterangan)) {
                         if ($stmt_insert->execute()) {
                            $success_count++;
                        } else {
                            $errors[] = "Gagal insert DB untuk **$nama_karyawan** tanggal **" . date('d M', strtotime($tgl_proses)) . ".**";
                            $error_count++;
                        }
                    } else {
                        $errors[] = "Binding parameter gagal untuk **$nama_karyawan** tanggal **" . date('d M', strtotime($tgl_proses)) . ".**";
                        $error_count++;
                    }
                    $stmt_insert->close();
                }
                $stmt_check->close();
            }
        } else {
            $errors[] = "Karyawan **$nama_karyawan** tidak ditemukan.";
            $error_count++;
        }
    }
    
    // Set session messages
    if ($success_count > 0) {
        $_SESSION['success'] = "Berhasil menambahkan **$success_count** total izin/cuti untuk rentang **" . date('d/m/Y', strtotime($tgl_dari)) . " s/d " . date('d/m/Y', strtotime($tgl_sampai)) . "**.";
    }
    if ($error_count > 0) {
        $_SESSION['validasi'] = "Ditemukan $error_count kegagalan/duplikasi:<br>" . implode("<br>", $errors);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
// --- AKHIR LOGIKA PEMROSESAN FORM YANG DIMODIFIKASI ---
?>

<style>
/* Style Anda tetap dipertahankan, hanya modifikasi terkait input time yang dihapus */
body {
    background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 100%);
    min-height: 100vh;
}
.card {
    border-radius: 1rem;
    box-shadow: 0 4px 24px 0 rgba(60,72,88,0.08);
    border: none;
}
.card-header {
    background: transparent;
    border-bottom: none;
    padding-bottom: 0;
}
.card-title {
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.form-check-label {
    font-size: 1rem;
    font-weight: 500;
}
#employee-checklist {
    max-height: 260px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #f9fafb;
    padding: 1rem 0.5rem;
}
#employee-checklist .form-check {
    background: #fff;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.25rem;
    box-shadow: 0 1px 4px 0 rgba(60,72,88,0.04);
}
input[type="date"], select.form-control, .form-control {
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    font-size: 1rem;
}
input[type="date"]:focus, select.form-control:focus, .form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px #2563eb33;
}
.btn-primary, .btn-success, .btn-danger, .btn-info {
    border-radius: 0.5rem;
    font-weight: 600;
    transition: background 0.2s, box-shadow 0.2s;
}
.btn-primary:hover, .btn-success:hover, .btn-danger:hover, .btn-info:hover {
    box-shadow: 0 2px 8px 0 rgba(37,99,235,0.10);
    filter: brightness(0.95);
}
.alert {
    border-radius: 0.5rem;
    font-size: 1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}
@media (max-width: 768px) {
    .card-title { font-size: 1.1rem; }
    #employee-checklist { max-height: 180px; }
}
</style>

<div class="page-body">
    <div class="container-xl py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24"><path stroke="#2563eb" stroke-width="2" d="M12 6v6l4 2"/><circle cx="12" cy="12" r="10" stroke="#2563eb" stroke-width="2"/></svg>
                    <h1 class="mb-0" style="font-weight:700; color:#2563eb;">Input Izin & Cuti Karyawan</h1>
                </div>
                <div class="text-muted mb-3" style="font-size:1.1rem;">Input izin & cuti karyawan dengan mudah dan cepat. Pilih karyawan, atur waktu, dan simpan izin & cuti secara massal.</div>
            </div>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" id="presensiForm">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Input Izin & Cuti Karyawan</h3>
                        </div>
                        <div class="card-body">
                            <?php if (isset($_SESSION['validasi'])) : ?>
                                <div class="alert alert-danger">
                                    <?= $_SESSION['validasi'];
                                    unset($_SESSION['validasi']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['success'])) : ?>
                                <div class="alert alert-success">
                                    <?= htmlspecialchars($_SESSION['success']);
                                    unset($_SESSION['success']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">Pengaturan Tanggal & Keterangan</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="global_date_dari">Tanggal Dari</label>
                                                        <input type="date" class="form-control" id="global_date_dari">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="global_date_sampai">Tanggal Sampai</label>
                                                        <input type="date" class="form-control" id="global_date_sampai">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="global_keterangan">Keterangan (Semua)</label>
                                                        <select class="form-control" id="global_keterangan">
                                                            <option value="">--Pilih Keterangan--</option>
                                                            <option value="Sakit">Sakit</option>
                                                            <option value="Surat Dokter">Surat Dokter</option>
                                                            <option value="Izin">Izin</option>
                                                            <option value="Cuti">Cuti</option>
                                                            <option value="Dinas Luar">Dinas Luar</option>
                                                            <option value="Work From Home">Work From Home</option>
                                                            <option value="Lainnya">Lainnya</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-info" onclick="applyToAll()">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M9 12l2 2l4 -4" />
                                                </svg>
                                                Terapkan ke Form
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">Pilih Karyawan</h4>
                                            <div class="card-actions">
                                                <button type="button" class="btn btn-success btn-sm" onclick="selectAllEmployees()">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M9 12l2 2l4 -4" />
                                                    </svg>
                                                    Pilih Semua
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="deselectAllEmployees()">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <line x1="18" y1="6" x2="6" y2="18" />
                                                        <line x1="6" y1="6" x2="18" y2="18" />
                                                    </svg>
                                                    Hapus Semua
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <circle cx="10" cy="10" r="7" />
                                                            <path d="M21 21l-6 -6" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" class="form-control" id="employee-search" placeholder="Cari nama karyawan..." onkeyup="searchEmployees()">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <line x1="18" y1="6" x2="6" y2="18" />
                                                            <line x1="6" y1="6" x2="18" y2="18" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row" id="employee-checklist">
                                                <?php
                                                $ambil_nama = mysqli_query($connection, "SELECT * FROM pegawai ORDER BY nama ASC");
                                                $employee_count = 0;
                                                while ($nama = mysqli_fetch_assoc($ambil_nama)) {
                                                    $nama_karyawan = $nama['nama'];
                                                    $employee_id = $nama['id'];
                                                    echo '<div class="col-md-3 mb-2">';
                                                    echo '<div class="form-check">';
                                                    echo '<input class="form-check-input employee-checkbox" type="checkbox" name="selected_employees[]" value="' . htmlspecialchars($nama_karyawan) . '" id="emp_' . $employee_id . '">';
                                                    echo '<label class="form-check-label" for="emp_' . $employee_id . '">' . htmlspecialchars($nama_karyawan) . '</label>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                    $employee_count++;
                                                }
                                                ?>
                                            </div>
                                            <div class="mt-3">
                                                <span class="text-muted">Total Karyawan: <span id="total-employees"><?= $employee_count ?></span></span>
                                                <span class="text-muted ms-3">Dipilih: <span id="selected-count">0</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="tgl_dari_hidden" id="tgl_dari_hidden">
                            <input type="hidden" name="tgl_sampai_hidden" id="tgl_sampai_hidden">
                            <input type="hidden" name="global_keterangan_hidden" id="global_keterangan_hidden">
                            
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary" name="submit" onclick="return prepareFormSubmission()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M5 12l5 5l10 -10" />
                                    </svg>
                                    Simpan Izin & Cuti untuk Karyawan Terpilih
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Global UI Functions
function selectAllEmployees() {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelectedCount();
}

function deselectAllEmployees() {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
    document.getElementById('selected-count').textContent = checkboxes.length;
}

function applyToAll() {
    const dateDari = document.getElementById('global_date_dari').value;
    const dateSampai = document.getElementById('global_date_sampai').value;
    const keterangan = document.getElementById('global_keterangan').value;
    
    if (!dateDari || !dateSampai || !keterangan) {
        alert('Mohon isi semua field Pengaturan Tanggal & Keterangan sebelum menerapkan.');
        return;
    }
    
    // Fill hidden fields with final values (done during submission)
    
    alert('Pengaturan Tanggal (' + dateDari + ' s/d ' + dateSampai + ') dan Keterangan (' + keterangan + ') berhasil diterapkan ke form!');
}

function prepareFormSubmission() {
    const dateDari = document.getElementById('global_date_dari').value;
    const dateSampai = document.getElementById('global_date_sampai').value;
    const keterangan = document.getElementById('global_keterangan').value;
    const selectedEmployees = document.querySelectorAll('.employee-checkbox:checked');

    if (selectedEmployees.length === 0) {
        alert('Pilih minimal satu karyawan!');
        return false;
    }
    
    if (!dateDari || !dateSampai || !keterangan) {
        alert('Tanggal Dari, Tanggal Sampai, dan Keterangan wajib diisi!');
        return false;
    }
    
    if (new Date(dateDari) > new Date(dateSampai)) {
        alert('Tanggal Dari tidak boleh melebihi Tanggal Sampai!');
        return false;
    }

    // Ensure hidden fields are set before submitting
    document.getElementById('tgl_dari_hidden').value = dateDari;
    document.getElementById('tgl_sampai_hidden').value = dateSampai;
    document.getElementById('global_keterangan_hidden').value = keterangan;
    
    return true;
}

// Search functionality
function searchEmployees() {
    const searchTerm = document.getElementById('employee-search').value.toLowerCase();
    const employeeItems = document.querySelectorAll('#employee-checklist .col-md-3');
    
    employeeItems.forEach(item => {
        const label = item.querySelector('.form-check-label');
        const employeeName = label.textContent.toLowerCase();
        
        if (employeeName.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function clearSearch() {
    document.getElementById('employee-search').value = '';
    searchEmployees(); // Re-run search to show all employees
}


// Initialization
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    
    // Set default date
    document.getElementById('global_date_dari').value = today;
    document.getElementById('global_date_sampai').value = today;
    
    // Add event listeners to checkboxes
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    updateSelectedCount();
});
</script>

<?php include('../layout/footer.php'); ?>