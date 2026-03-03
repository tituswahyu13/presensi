<?php

// $db_host = "localhost";
// $db_user = "root";
// $db_pass = "xyz123";
// $db_port = 3306;
// $db_name = "presensi";

$db_host = "36.95.152.234";
$db_user = "root";
$db_pass = "xyz123";
$db_port = 6011;
$db_name = "presensi";


// Set timezone default
date_default_timezone_set('Asia/Jakarta');

$connection = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

if (!$connection) {
    echo "koneksi DB gagal" . mysqli_connect_error();
}

function base_url($url = null)
{
    $base_url = 'http://localhost:8080';
    if ($url !== null) {
        return $base_url . '/' . $url;
    } else {
        return $base_url;
    }
}
