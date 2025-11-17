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

?>



<!doctype html>

<html lang="en">



<head>

<meta charset="utf-8" />

<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />

<meta http-equiv="X-UA-Compatible" content="ie=edge" />

<title><?= $judul ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="/absensi/assets/css/tabler.min.css?1684106062" rel="stylesheet" />

<link href="/absensi/assets/css/tabler-vendors.min.css?1684106062" rel="stylesheet" />

<link href="/absensi/assets/css/demo.min.css?1684106062" rel="stylesheet" />

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

</style>

</head>



<body>

<script src="./dist/js/demo-theme.min.js?1684106062"></script>

<div class="page">

<header class="navbar navbar-expand-md d-print-none">

<div class="container-xl">

<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">

<span class="navbar-toggler-icon"></span>

</button>

<h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">

<a href="<?= ('../../pegawai/home/home.php') ?>">

<img src="/absensi/assets/img/logo.png" width="110" height="32" alt="Tabler" class="navbar-brand-image">

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

<a href="/absensi/pegawai/home/profil.php" class="dropdown-item">Profil</a>

<a href="/absensi/pegawai/home/edit_password.php" class="dropdown-item">Ubah Password</a>

<a href="/absensi/auth/logout.php" class="dropdown-item">Logout</a>

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

<a class="nav-link" href="/absensi/pegawai/home/pengajuan.php">

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

<li class="nav-item">

<?php

$allowed_ids = array('21', '106', '138', '145', '157', '166', '198', '221', '226', '230', '253', '257', '279', '23', '24', '349');



if (in_array($id_pengguna, $allowed_ids)) {

?>

<a class="nav-link" href="/absensi/pegawai/home/konfirmasi_izin_ruang.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Konfirmasi Izin Sub-Bagian

</span>

</a>

<?php

}

?>

</li>

<li class="nav-item">

<?php

$allowed_ids = array('24', '21', '9', '349', '23');



if (in_array($id_pengguna, $allowed_ids)) {

?>

<a class="nav-link" href="/absensi/pegawai/home/konfirmasi_izin_teknik.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Konfirmasi Izin Bagian Teknik

</span>

</a>

<?php

}

?>

</li>

<li class="nav-item">

<?php

$allowed_ids = array('24', '21', '8', '349', '23');



if (in_array($id_pengguna, $allowed_ids)) {

?>

<a class="nav-link" href="/absensi/pegawai/home/konfirmasi_izin_hublang.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Konfirmasi Izin Bagian HubLang

</span>

</a>

<?php

}

?>

</li>

<li class="nav-item">

<?php

$allowed_ids = array('24', '21', '7', '349', '23');



if (in_array($id_pengguna, $allowed_ids)) {

?>

<a class="nav-link" href="/absensi/pegawai/home/konfirmasi_izin_umum.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Konfirmasi Izin Bagian Umum

</span>

</a>

<?php

}

?>

</li>

<li class="nav-item">

<a class="nav-link" href="/absensi/pegawai/home/rekap.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Rekap Presensi

</span>

</a>

</li>

<li class="nav-item">

<?php

$allowed_ids = array('21', '106', '138', '145', '157', '166', '198', '221', '226', '230', '253', '257', '279', '24', '23');



if (in_array($id_pengguna, $allowed_ids)) {

?>

<a class="nav-link" href="/absensi/pegawai/home/rekap_ruang.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Presensi sub-Bagian

</span>

</a>

<?php

}

?>

</li>

<li class="nav-item">

<?php

$allowed_ids = array('24', '21', '9', '23');



if (in_array($id_pengguna, $allowed_ids)) {

?>

<a class="nav-link" href="/absensi/pegawai/home/rekap_teknik.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Presensi Bagian Teknik

</span>

</a>

<?php

}

?>

</li>

<li class="nav-item">

<?php

$allowed_ids = array('24', '21', '8', '23');



if (in_array($id_pengguna, $allowed_ids)) {

?>

<a class="nav-link" href="/absensi/pegawai/home/rekap_hublang.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Presensi Bagian HubLang

</span>

</a>

<?php

}

?>

</li>

<li class="nav-item">

<?php

$allowed_ids = array('24', '21', '7', '23');



if (in_array($id_pengguna, $allowed_ids)) {

?>

<a class="nav-link" href="/absensi/pegawai/home/rekap_umum.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Presensi Bagian Umum

</span>

</a>

<?php

}

?>

</li>

<li class="nav-item">

<?php

$allowed_ids = array('24', '21', '3', '23');



if (in_array($id_pengguna, $allowed_ids)) {

?>

<a class="nav-link" href="/absensi/pegawai/home/rekap_all.php">

<span class="nav-link-icon d-md-none d-lg-inline-block">

<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">

<path stroke="none" d="M0 0h24v24H0z" fill="none" />

<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />

<path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />

<path d="M9 14l2 2l4 -4" />

</svg>

</span>

<span class="nav-link-title">

Presensi Perusahaan

</span>

</a>

<?php

}

?>

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