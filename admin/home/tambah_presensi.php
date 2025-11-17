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

$judul = "Tambah Presensi +";
include('../layout/header.php');
require_once('../../config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    // Get selected employees and global settings
    $selected_employees = $_POST['selected_employees'] ?? [];
    $global_date = $_POST['global_date'] ?? '';
    $global_time = $_POST['global_time'] ?? '';
    $global_keterangan = $_POST['global_keterangan'] ?? '';
    
    if (empty($selected_employees)) {
        $_SESSION['validasi'] = "Pilih minimal satu karyawan.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    foreach ($selected_employees as $nama) {
        // Use global settings if available, otherwise use individual settings
        $tgl_masuk = $global_date ?: ($_POST['tgl_masuk'][$nama] ?? '');
        $jam_masuk = $global_time ?: ($_POST['jam_masuk'][$nama] ?? '');
        $keterangan = $global_keterangan ?: ($_POST['keterangan'][$nama] ?? '');

        // Skip if required fields are empty
        if (empty($tgl_masuk) || empty($jam_masuk)) {
            $errors[] = "Tanggal dan jam harus diisi untuk $nama.";
            $error_count++;
            continue;
        }

        // Fetch employee id based on the selected name
        $stmt = $connection->prepare("SELECT id FROM pegawai WHERE nama = ?");
        $stmt->bind_param("s", $nama);
        $stmt->execute();
        $stmt->bind_result($id_pegawai);
        $stmt->fetch();
        $stmt->close();

        if ($id_pegawai) {
            // Check if attendance already exists for this employee on this date
            $stmt = $connection->prepare("SELECT id FROM presensi WHERE id_pegawai = ? AND tanggal_masuk = ?");
            $stmt->bind_param("is", $id_pegawai, $tgl_masuk);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $errors[] = "Presensi untuk $nama pada tanggal $tgl_masuk sudah ada.";
                $error_count++;
            } else {
                // Insert the attendance record
                $stmt = $connection->prepare("INSERT INTO presensi (id_pegawai, tanggal_masuk, jam_masuk, foto_masuk, keterangan) VALUES (?, ?, ?, 'LogoBW.png', ?)");
                $stmt->bind_param("isss", $id_pegawai, $tgl_masuk, $jam_masuk, $keterangan);
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $errors[] = "Gagal menambahkan presensi untuk $nama.";
                    $error_count++;
                }
                $stmt->close();
            }
        } else {
            $errors[] = "Karyawan $nama tidak ditemukan.";
            $error_count++;
        }
    }
    
    // Set session messages
    if ($success_count > 0) {
        $_SESSION['success'] = "Berhasil menambahkan $success_count presensi.";
    }
    if ($error_count > 0) {
        $_SESSION['validasi'] = implode("<br>", $errors);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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
.card-actions {
    display: flex;
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
input[type="date"], input[type="time"], select.form-control, .form-control {
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    font-size: 1rem;
}
input[type="date"]:focus, input[type="time"]:focus, select.form-control:focus, .form-control:focus {
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

<!-- Page body -->
<div class="page-body">
    <div class="container-xl py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24"><path stroke="#2563eb" stroke-width="2" d="M12 6v6l4 2"/><circle cx="12" cy="12" r="10" stroke="#2563eb" stroke-width="2"/></svg>
                    <h1 class="mb-0" style="font-weight:700; color:#2563eb;">Tambah Presensi Karyawan</h1>
                </div>
                <div class="text-muted mb-3" style="font-size:1.1rem;">Input presensi harian karyawan dengan mudah dan cepat. Pilih karyawan, atur waktu, dan simpan presensi secara massal.</div>
            </div>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" id="presensiForm">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Tambah Presensi Karyawan</h3>
                            <div class="card-actions">
                                <!-- <button type="button" class="btn btn-success" onclick="addEntry()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
                                    Tambah Entry
                                </button>
                                <button type="button" class="btn btn-danger" onclick="removeEntry()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <line x1="4" y1="7" x2="20" y2="7" />
                                        <line x1="10" y1="11" x2="10" y2="17" />
                                        <line x1="14" y1="11" x2="14" y2="17" />
                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                    </svg>
                                    Hapus Entry
                                </button> -->
                            </div>
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
                            
                            <!-- Global Date and Time Settings -->
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">Pengaturan Global</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="global_date">Tanggal Masuk (Semua)</label>
                                                        <input type="date" class="form-control" id="global_date" onchange="updateAllDates(this.value)">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="global_time">Jam Masuk (Semua)</label>
                                                        <input type="time" class="form-control" id="global_time" onchange="updateAllTimes(this.value)">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="global_keterangan">Keterangan (Semua)</label>
                                                        <select class="form-control" id="global_keterangan" onchange="updateAllKeterangan(this.value)">
                                                            <option value="">--Pilih Keterangan--</option>
                                                            <option value="Hadir">Hadir</option>
                                                            <option value="Terlambat">Terlambat</option>
                                                            <!-- <option value="Sakit">Sakit</option>
                                                            <option value="Izin">Izin</option>
                                                            <option value="Cuti">Cuti</option> -->
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
                                                Terapkan ke Semua Entry
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Employee Selection -->
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
                                            <!-- Search Input -->
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

                            <div id="entries-container">
                                <!-- Entries will be generated dynamically based on selected employees -->
                            </div>
                            
                            <input type="hidden" name="global_date" id="global_date_hidden">
                            <input type="hidden" name="global_time" id="global_time_hidden">
                            <input type="hidden" name="global_keterangan" id="global_keterangan_hidden">
                            
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary" name="submit" onclick="return validateForm()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M5 12l5 5l10 -10" />
                                    </svg>
                                    Simpan Presensi untuk Karyawan Terpilih
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
// Employee selection functions
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
    const selectedCount = checkboxes.length;
    document.getElementById('selected-count').textContent = selectedCount;
}

function validateForm() {
    const selectedEmployees = document.querySelectorAll('.employee-checkbox:checked');
    if (selectedEmployees.length === 0) {
        alert('Pilih minimal satu karyawan!');
        return false;
    }
    
    const globalDate = document.getElementById('global_date').value;
    const globalTime = document.getElementById('global_time').value;
    
    if (!globalDate || !globalTime) {
        alert('Tanggal dan jam harus diisi!');
        return false;
    }
    
    return true;
}

// Add event listeners to checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    updateSelectedCount();
});

// Set default date to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const now = new Date();
    const currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    
    // Set global date and time
    document.getElementById('global_date').value = today;
    document.getElementById('global_time').value = currentTime;
    
    // Set default for first entry
    const firstDateInput = document.querySelector('.date-input');
    const firstTimeInput = document.querySelector('.time-input');
    if (firstDateInput) firstDateInput.value = today;
    if (firstTimeInput) firstTimeInput.value = currentTime;
});

function updateAllDates(dateValue) {
    const dateInputs = document.querySelectorAll('.date-input');
    dateInputs.forEach(input => {
        input.value = dateValue;
    });
}

function updateAllTimes(timeValue) {
    const timeInputs = document.querySelectorAll('.time-input');
    timeInputs.forEach(input => {
        input.value = timeValue;
    });
}

function applyToAll() {
    const globalDate = document.getElementById('global_date').value;
    const globalTime = document.getElementById('global_time').value;
    const globalKeterangan = document.getElementById('global_keterangan').value;
    
    // Update hidden fields for form submission
    document.getElementById('global_date_hidden').value = globalDate;
    document.getElementById('global_time_hidden').value = globalTime;
    document.getElementById('global_keterangan_hidden').value = globalKeterangan;
    
    if (globalDate) {
        updateAllDates(globalDate);
    }
    if (globalTime) {
        updateAllTimes(globalTime);
    }
    if (globalKeterangan) {
        updateAllKeterangan(globalKeterangan);
    }
}

function updateAllKeterangan(keteranganValue) {
    const keteranganInputs = document.querySelectorAll('.keterangan-input');
    keteranganInputs.forEach(input => {
        input.value = keteranganValue;
    });
}

// Search functionality
function searchEmployees() {
    const searchTerm = document.getElementById('employee-search').value.toLowerCase();
    const employeeItems = document.querySelectorAll('#employee-checklist .col-md-3');
    let visibleCount = 0;
    
    employeeItems.forEach(item => {
        const label = item.querySelector('.form-check-label');
        const employeeName = label.textContent.toLowerCase();
        
        if (employeeName.includes(searchTerm)) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Update visible count
    updateVisibleEmployeeCount(visibleCount);
}

function clearSearch() {
    document.getElementById('employee-search').value = '';
    const employeeItems = document.querySelectorAll('#employee-checklist .col-md-3');
    
    employeeItems.forEach(item => {
        item.style.display = 'block';
    });
    
    // Reset to show total count
    const totalEmployees = document.getElementById('total-employees').textContent;
    updateVisibleEmployeeCount(parseInt(totalEmployees));
}

function updateVisibleEmployeeCount(count) {
    const totalEmployees = document.getElementById('total-employees').textContent;
    const visibleCountElement = document.getElementById('visible-count');
    
    if (!visibleCountElement) {
        // Create visible count element if it doesn't exist
        const selectedCountSpan = document.getElementById('selected-count').parentElement;
        const visibleSpan = document.createElement('span');
        visibleSpan.className = 'text-muted ms-3';
        visibleSpan.innerHTML = 'Ditampilkan: <span id="visible-count">' + count + '</span>';
        selectedCountSpan.parentElement.appendChild(visibleSpan);
    } else {
        visibleCountElement.textContent = count;
    }
}
</script>

<?php include('../layout/footer.php'); ?>