<?php

global $judul;

require_once('../../config.php');



if (session_status() == PHP_SESSION_NONE) {

session_start();

}



// Periksa apakah pengguna telah login atau belum

if (isset($_SESSION["nama"])) {

$nama_pengguna = $_SESSION["nama"];

} else {

// Jika pengguna belum login, arahkan ke halaman login atau lakukan tindakan yang sesuai

header("Location: login.php");

exit;

}



if (isset($_SESSION["id"])) {

$id_pengguna = $_SESSION["id"];

} else {

// Jika pengguna belum login, arahkan ke halaman login atau lakukan tindakan yang sesuai

header("Location: login.php");

exit;

}



// Simpan id_pengguna ke dalam session

$_SESSION["id_pengguna"] = $id_pengguna;


// Definisikan array user ID yang diizinkan untuk setiap menu KONFIRMASI IZIN
$allowed_ruang = array('21', '106', '138', '145', '157', '166', '198', '221', '226', '230', '253', '257', '279', '23', '24', '350');
$allowed_teknik = array('24', '21', '9', '23');
$allowed_hublang = array('24', '21', '8', '3', '23');
$allowed_umum = array('24', '21', '7', '23');

// Cek apakah pengguna diizinkan untuk melihat menu Konfirmasi Izin sama sekali
$is_konfirmasi_allowed = in_array($id_pengguna, $allowed_ruang) ||
                         in_array($id_pengguna, $allowed_teknik) ||
                         in_array($id_pengguna, $allowed_hublang) ||
                         in_array($id_pengguna, $allowed_umum);

// --- LOGIC UNTUK MENGAMBIL HITUNGAN PENGAJUAN IZIN YANG TERTUNDA ---

// Fungsi helper untuk menghasilkan indikator neon tanpa jumlah
function generate_indicator($count) {
    if ($count > 0) {
        // Menggunakan kelas khusus untuk styling dot indicator
        return '<span class="badge rounded-circle badge-indicator badge-pulse ms-1"></span>';
    }
    return '';
}

// 1. Hitungan untuk Konfirmasi Izin Sub-Bagian (Ruang)
$count_ruang = 0;
// PASTIKAN $connection ADA SEBELUM MENGGUNAKANNYA
if (isset($connection)) {
    $query_ruang = "SELECT COUNT(a.id_pegawai) AS total FROM absensi a 
        JOIN pegawai p ON a.id_pegawai = p.id
        WHERE a.status IS NULL 
        AND p.jabatan = 'Staf'
        AND p.bagian = (SELECT bagian FROM pegawai WHERE id = ?)";
    $stmt_ruang = $connection->prepare($query_ruang);
    $stmt_ruang->bind_param("i", $id_pengguna);
    $stmt_ruang->execute();
    $result_ruang = $stmt_ruang->get_result()->fetch_assoc();
    $count_ruang = $result_ruang['total'];
}


// 2. Hitungan untuk Konfirmasi Izin Bagian Teknik
$count_teknik = 0;
if (isset($connection)) {
    $query_teknik = "SELECT COUNT(a.id_pegawai) AS total FROM absensi a 
        JOIN pegawai p ON a.id_pegawai = p.id
        WHERE a.status IS NULL 
        AND p.jabatan = 'Asisten Manajer'
        AND p.bagian LIKE '1%'";
    $stmt_teknik = $connection->prepare($query_teknik);
    $stmt_teknik->execute();
    $result_teknik = $stmt_teknik->get_result()->fetch_assoc();
    $count_teknik = $result_teknik['total'];
}

// 3. Hitungan untuk Konfirmasi Izin Bagian HubLang
$count_hublang = 0;
if (isset($connection)) {
    $query_hublang = "SELECT COUNT(a.id_pegawai) AS total FROM absensi a 
        JOIN pegawai p ON a.id_pegawai = p.id
        WHERE a.status IS NULL 
        AND p.jabatan = 'Asisten Manajer'
        AND p.bagian LIKE '2%'";
    $stmt_hublang = $connection->prepare($query_hublang);
    $stmt_hublang->execute();
    $result_hublang = $stmt_hublang->get_result()->fetch_assoc();
    $count_hublang = $result_hublang['total'];
}

// 4. Hitungan untuk Konfirmasi Izin Bagian Umum
$count_umum = 0;
if (isset($connection)) {
    $query_umum = "SELECT COUNT(a.id_pegawai) AS total FROM absensi a 
        JOIN pegawai p ON a.id_pegawai = p.id
        WHERE a.status IS NULL 
        AND p.jabatan = 'Asisten Manajer'
        AND p.bagian LIKE '3%'";
    $stmt_umum = $connection->prepare($query_umum);
    $stmt_umum->execute();
    $result_umum = $stmt_umum->get_result()->fetch_assoc();
    $count_umum = $result_umum['total'];
}

// Hitung total pending requests yang terlihat/dapat diakses oleh user saat ini
$total_pending_for_user = 0;

if (in_array($id_pengguna, $allowed_ruang)) {
    $total_pending_for_user += $count_ruang;
}
if (in_array($id_pengguna, $allowed_teknik)) {
    $total_pending_for_user += $count_teknik;
}
if (in_array($id_pengguna, $allowed_hublang)) {
    $total_pending_for_user += $count_hublang;
}
if (in_array($id_pengguna, $allowed_umum)) {
    $total_pending_for_user += $count_umum;
}

// Variabel final untuk menentukan tampilan notifikasi
$show_navbar_notification = ($total_pending_for_user > 0);
?>


<!doctype html>

<html lang="en">



<head>

<meta charset="utf-8" />

<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />

<meta http-equiv="X-UA-Compatible" content="ie=edge" />

<title><?= $judul ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="/assets/css/tabler.min.css?1684106062" rel="stylesheet" />

<link href="/assets/css/tabler-vendors.min.css?1684106062" rel="stylesheet" />

<link href="/assets/css/demo.min.css?1684106062" rel="stylesheet" />

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<link rel="manifest" href="../../manifest.json">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">



<script>

if ('serviceWorker' in navigator) {

navigator.serviceWorker.register('../../services-worker.js')

.then(function(registration) {

console.log('Service Worker terdaftar dengan scope:', registration.scope);

})

.catch(function(error) {

console.log('Pendaftaran Service Worker gagal:', error);

});

}

</script>



<style>

:root {

--primary-color: #00e0b3; /* Hijau neon */

--secondary-color: #00a4d4; /* Biru elektrik */
--indicator-color: #ff007f; /* PINK NEON */
--indicator-glow: rgba(255, 0, 127, 0.8);

--bg-color: #0a0a0d;

--card-bg: rgba(18, 18, 25, 0.7);

--text-color: #e0e0e0;

--border-color: rgba(0, 224, 179, 0.3);

--glow-color: rgba(0, 224, 179, 0.5);

}



body {

font-feature-settings: "cv03", "cv04", "cv11";

background-color: var(--bg-color);

color: var(--text-color);

}


.page-wrapper, .page {

background-color: var(--bg-color) !important;

}


.page-title {

color: var(--primary-color) !important;

text-shadow: 0 0 5px var(--primary-color);

}


/* Navbar styling */

.navbar {

background-color: var(--card-bg) !important;

backdrop-filter: blur(10px);

border-bottom: 1px solid var(--border-color);

box-shadow: 0 0 15px rgba(0, 224, 179, 0.2);

}


/* High-contrast navbar toggler for dark background */

.navbar .navbar-toggler {

border-color: var(--primary-color);

color: var(--primary-color);
/* NEW: Set position relative for badge positioning */
position: relative; 
}

.navbar .navbar-toggler:hover {

background-color: rgba(0, 224, 179, 0.12);

}

.navbar .navbar-toggler:focus {

box-shadow: 0 0 0 0.25rem rgba(0, 224, 179, 0.25);

}

.navbar .navbar-toggler-icon {

/* Force light/bright icon for better contrast on dark navbar */

background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,0.95)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='3' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");

filter: drop-shadow(0 0 3px rgba(0, 224, 179, 0.6));

}


.navbar-brand img {

/* filter: drop-shadow(0 0 5px var(--primary-color)); */

transition: transform 0.3s ease;

}


.navbar-brand img:hover {

transform: scale(1.05);

}


.navbar-nav .nav-link {

color: var(--text-color) !important;

transition: all 0.2s ease-in-out;

}



.navbar-nav .nav-link:hover {

color: var(--primary-color) !important;

text-shadow: 0 0 5px var(--primary-color);

transform: translateY(-2px);

}


.navbar-nav .nav-link-icon svg {

stroke: var(--text-color) !important;

}


.navbar-nav .nav-link:hover .nav-link-icon svg {

stroke: var(--primary-color) !important;

}


.dropdown-menu {

background: var(--card-bg) !important;

border: 1px solid var(--border-color);

box-shadow: 0 0 10px rgba(0, 224, 179, 0.2);

}



.dropdown-item {

color: var(--text-color) !important;

transition: all 0.2s ease-in-out;

}


.dropdown-item:hover {

background-color: rgba(0, 224, 179, 0.1) !important;

color: var(--primary-color) !important;

}



.avatar {

background-color: var(--primary-color) !important;

border: 2px solid var(--secondary-color);

box-shadow: 0 0 10px rgba(0, 224, 179, 0.5);

}


.avatar svg {

stroke: var(--bg-color) !important;

}


/* Ensure Font Awesome icons inside avatar are sized and contrasted well */

.avatar i {

font-size: 18px;

line-height: 1;

color: var(--bg-color) !important;

display: inline-flex;

align-items: center;

justify-content: center;

width: 1em;

height: 1em;

}


/* Page header */

.page-header {

padding-top: 3rem;

padding-bottom: 2rem;

}


/* Tombol Circle (jika ada) */

.btn-circle {

background-color: var(--primary-color);

color: var(--bg-color);

box-shadow: 0 0 10px var(--glow-color);

transition: all 0.3s ease;

}



.btn-circle:hover {

background-color: var(--secondary-color);

box-shadow: 0 0 15px rgba(0, 164, 212, 0.6);

transform: translateY(-2px);

}

/* New CSS for clearer navigation groupings (dropdowns) */
.nav-link.dropdown-toggle::after {
    border-top-color: var(--text-color);
}
.nav-link:hover.dropdown-toggle::after {
    border-top-color: var(--primary-color);
}
.dropdown-item .nav-link-icon svg {
    margin-right: 0.5rem;
}
.dropdown-item {
    padding: 0.5rem 1rem !important;
}

/* Tambahkan animasi pulse untuk notifikasi badge */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 0, 127, 0.6); /* Menggunakan indicator-glow */
    }
    70% {
        box-shadow: 0 0 0 8px rgba(255, 0, 127, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 0, 127, 0);
    }
}
.badge-pulse {
    animation: pulse 1.5s infinite;
}

/* Styling untuk dot indicator */
.badge-indicator {
    height: 10px; /* Ukuran dot */
    width: 10px; 
    padding: 0 !important; 
    border-radius: 50% !important;
    background-color: var(--indicator-color) !important;
    box-shadow: 0 0 5px var(--indicator-glow);
    display: inline-block;
    vertical-align: middle;
}

/* Styling untuk notifikasi badge di navbar toggler */
.navbar-toggler .badge {
    position: absolute;
    top: -3px; 
    right: -3px; 
    z-index: 10;
}

</style>

</head>



<body>

<script src="./dist/js/demo-theme.min.js?1684106062"></script>

<div class="page">

<header class="navbar navbar-expand-md d-print-none">

<div class="container-xl">

<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">

<span class="navbar-toggler-icon"></span>
<?php
    // Tampilkan indikator hanya jika ada total pengajuan pending > 0 UNTUK USER INI
    if ($show_navbar_notification) {
        // Menggunakan gaya inline untuk posisi badge di dalam button yang relative, menggunakan kelas badge-indicator
        echo '<span class="badge badge-indicator badge-pulse" style="position: absolute; top: 0px; right: 0px; z-index: 10;"></span>';
    }
?>
</button>

<h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">

<a href="<?= ('../../pegawai/home/home.php') ?>">

<img src="/assets/img/logo.png" width="110" height="32" alt="Tabler" class="navbar-brand-image">

</a>

</h1>

<div class="navbar-nav flex-row order-md-last">

<div class="nav-item dropdown">

<a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">

<span class="bg-green text-white avatar">

<i class="fa-solid fa-user-astronaut" aria-hidden="true"></i>

</span>

<div class="d-none d-xl-block ps-2">

<div><?= $nama_pengguna ?></div>

</div>

</a>

<div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">

<a href="/pegawai/home/profil.php" class="dropdown-item">Profil</a>

<a href="/pegawai/home/edit_password.php" class="dropdown-item">Ubah Password</a>

<a href="/auth/logout.php" class="dropdown-item">Logout</a>

</div>

</div>

</div>

</div>

</header>

<header class="navbar-expand-md">

<div class="collapse navbar-collapse" id="navbar-menu">

<div class="navbar">

<div class="container-xl">

<ul class="navbar-nav">

<li class="nav-item">

<a class="nav-link" href="<?= ('../../pegawai/home/home.php') ?>">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M5 12l-2 0l9 -9l9 9l-2 0" />

<path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />

<path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />

</svg>

</span>

<span class="nav-link-title">

Home

</span>

</a>

</li>

<li class="nav-item">

<a class="nav-link" href="/pegawai/home/pengajuan.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-plus" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M14 3v4a1 1 0 0 0 1 1h4" />

<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />

<path d="M12 11v6" />

<path d="M9 14h6" />

</svg>

</span>

<span class="nav-link-title">

Pengajuan Izin

</span>

</a>

</li>

<?php if ($is_konfirmasi_allowed) { /* START Konfirmasi Izin Main Menu Check */ ?>
<li class="nav-item dropdown">

<a class="nav-link dropdown-toggle <?= $total_pending_for_user > 0 ? 'text-primary' : '' ?>" href="#konfirmasi-izin-menu" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Konfirmasi Izin <?= generate_indicator($total_pending_for_user) ?>

</span>

</a>

<div class="dropdown-menu">

<?php

if (in_array($id_pengguna, $allowed_ruang)) {

?>

<a class="dropdown-item" href="/pegawai/home/konfirmasi_izin_ruang.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none"/>

<path d="M14 3v4a1 1 0 0 0 1 1h4" />

<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />

<path d="M9 15l2 2l4 -4" />

</svg>

</span>

Konfirmasi Izin Sub-Bagian <?= generate_indicator($count_ruang) ?>

</a>

<?php

}

if (in_array($id_pengguna, $allowed_teknik)) {

?>

<a class="dropdown-item" href="/pegawai/home/konfirmasi_izin_teknik.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none"/>

<path d="M14 3v4a1 1 0 0 0 1 1h4" />

<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />

<path d="M9 15l2 2l4 -4" />

</svg>

</span>

Konfirmasi Izin Bagian Teknik <?= generate_indicator($count_teknik) ?>

</a>

<?php

}

if (in_array($id_pengguna, $allowed_hublang)) {

?>

<a class="dropdown-item" href="/pegawai/home/konfirmasi_izin_hublang.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none"/>

<path d="M14 3v4a1 1 0 0 0 1 1h4" />

<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />

<path d="M9 15l2 2l4 -4" />

</svg>

</span>

Konfirmasi Izin Bagian HubLang <?= generate_indicator($count_hublang) ?>

</a>

<?php

}

if (in_array($id_pengguna, $allowed_umum)) {

?>

<a class="dropdown-item" href="/pegawai/home/konfirmasi_izin_umum.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none"/>

<path d="M14 3v4a1 1 0 0 0 1 1h4" />

<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />

<path d="M9 15l2 2l4 -4" />

</svg>

</span>

Konfirmasi Izin Bagian Umum <?= generate_indicator($count_umum) ?>

</a>

<?php

}

?>

</div>

</li>
<?php } /* END Konfirmasi Izin Main Menu Check */ ?>

<li class="nav-item dropdown">

<a class="nav-link dropdown-toggle" href="#rekap-presensi-menu" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-list" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 12l2 2l4 -4" />

<path d="M9 16h6" />

</svg>

</span>

<span class="nav-link-title">

Rekap & Presensi

</span>

</a>

<div class="dropdown-menu">

<a class="dropdown-item" href="/pegawai/home/rekap.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

Rekap Presensi

</a>

<?php

$allowed_ruang_presensi = array('21', '106', '138', '145', '157', '166', '198', '221', '226', '230', '253', '257', '279', '24', '23', '350');

if (in_array($id_pengguna, $allowed_ruang_presensi)) {

?>

<a class="dropdown-item" href="/pegawai/home/rekap_ruang.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users-group" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none"/>

<path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />

<path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1" />

<path d="M15 5h3.5a2 2 0 0 1 0 4h-3.5" />

<path d="M15 11h3.5a2 2 0 0 0 0 4h-3.5" />

<path d="M5 5a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v1h-8v-1" />

</svg>

</span>

Presensi Sub-Bagian

</a>

<?php

}

$allowed_teknik_presensi = array('24', '21', '9', '23');

if (in_array($id_pengguna, $allowed_teknik_presensi)) {

?>

<a class="dropdown-item" href="/pegawai/home/rekap_teknik.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none"/>

<path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .356 2.37 .5 2.572 1.065z" />

<path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />

</svg>

</span>

Presensi Bagian Teknik

</a>

<?php

}

$allowed_hublang_presensi = array('24', '21', '8', '23');

if (in_array($id_pengguna, $allowed_hublang_presensi)) {

?>

<a class="dropdown-item" href="/pegawai/home/rekap_hublang.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-route" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none"/>

<path d="M3 19a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />

<path d="M19 7a2 2 0 1 0 0 -4a2 2 0 0 0 0 4" />

<path d="M11 19h-1a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h2" />

<path d="M17 19h-1a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h2" />

</svg>

</span>

Presensi Bagian HubLang

</a>

<?php

}

$allowed_umum_presensi = array('24', '21', '7', '23');

if (in_array($id_pengguna, $allowed_umum_presensi)) {

?>

<a class="dropdown-item" href="/pegawai/home/rekap_umum.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-building-warehouse" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none"/>

<path d="M3 21v-13l9 -4l9 4v13" />

<path d="M13 13h4v8h-10v-6h6" />

<path d="M13 17h4" />

</svg>

</span>

Presensi Bagian Umum

</a>

<?php

}

$allowed_all_presensi = array('24', '21', '3', '23');

if (in_array($id_pengguna, $allowed_all_presensi)) {

?>

<a class="dropdown-item" href="/pegawai/home/rekap_all.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-world" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none"/>

<path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />

<path d="M3.6 9h16.8" />

<path d="M3.6 15h16.8" />

<path d="M11.5 3a17 17 0 0 0 0 18" />

<path d="M12.5 3a17 17 0 0 1 0 18" />

</svg>

</span>

Presensi Perusahaan

</a>

<?php

}

?>

</div>

</li>

<li class="nav-item">

<a class="nav-link" href="<?= ('../../pegawai/home/event.php') ?>">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-butterfly" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M12 18.176a3 3 0 1 1 -4.953 -2.449l-.025 .023a4.502 4.502 0 0 1 1.483 -8.75c1.414 0 2.675 .652 3.5 1.671a4.5 4.5 0 1 1 4.983 7.079a3 3 0 1 1 -4.983 2.25z" />

<path d="M12 19v-10" />

<path d="M9 3l3 2l3 -2" />

</svg>

</span>

<span class="nav-link-title">

Event

</span>

</a>

</li>

</ul>

</div>

</div>

</div>

</header>

<div class="page-wrapper">

<div class="page-header d-print-none">

<div class="container-xl">

<div class="row g-2 align-items-center">

<div class="col">

<h2 class="page-title">

<?= $judul ?>

</h2>

</div>

</div>

</div>

</div>