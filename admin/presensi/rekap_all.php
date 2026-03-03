<?php
ob_start();
session_start();
if (!isset($_SESSION["login"])) {
    header("Location: ../../auth/login.php?pesan=belum_login");
    exit();
} elseif ($_SESSION["role"] != "admin") {
    header("Location: ../../auth/login.php?pesan=tolak_akses");
    exit();
}

$judul = "Rekap Presensi - Ringkasan";
include('../layout/header.php');
include_once('../../config.php');

// Filter pencarian berdasarkan nama jika parameter pencarian diberikan
$nama_condition = isset($_GET['nama']) ? "AND pegawai.nama LIKE ?" : "";

if (empty($_GET["tanggal_dari"])) {
    $tanggal_hari_ini = date('Y-m-d');
    $query = "SELECT
                    pegawai.id as pegawai_id,
                    pegawai.nama,
                    pegawai.lokasi_presensi,
                    pegawai.bagian,
                    pegawai.jabatan,
                    users.role,
                    COUNT(CASE WHEN presensi.tanggal_masuk IS NOT NULL AND presensi.keterangan IS NULL THEN 1 END) as jumlah_hadir,
                    COUNT(CASE WHEN presensi.tanggal_masuk IS NOT NULL 
                    AND (presensi.tanggal_keluar IS NULL OR presensi.jam_keluar IS NULL) THEN 1 END) as jumlah_tidak_presensi_pulang,
                    COUNT(CASE WHEN presensi.tanggal_masuk IS NOT NULL AND (
                        (users.role = 'pegawai' AND presensi.jam_masuk > (
                            CASE DAYOFWEEK(presensi.tanggal_masuk)
                                WHEN 1 THEN jam_kerja.jam_masuk_senin
                                WHEN 2 THEN jam_kerja.jam_masuk_selasa
                                WHEN 3 THEN jam_kerja.jam_masuk_rabu
                                WHEN 4 THEN jam_kerja.jam_masuk_kamis
                                WHEN 5 THEN jam_kerja.jam_masuk_jumat
                                ELSE jam_kerja.jam_masuk_sabtu
                            END
                        )) OR 
                        (users.role IN ('sumber', 'tidar') AND presensi.jam_masuk > (
                            CASE presensi.shift
                                WHEN 'A' THEN shift.masuk_a
                                WHEN 'B' THEN shift.masuk_b
                                WHEN 'C' THEN shift.masuk_c
                                WHEN 'D' THEN shift.masuk_d
                                ELSE shift.masuk_e
                            END
                        ))
                    ) THEN 1 END) as jumlah_terlambat,
                    SUM(CASE WHEN presensi.tanggal_masuk IS NOT NULL AND (
                        (users.role = 'pegawai' AND presensi.jam_masuk > (
                            CASE DAYOFWEEK(presensi.tanggal_masuk)
                                WHEN 1 THEN jam_kerja.jam_masuk_senin
                                WHEN 2 THEN jam_kerja.jam_masuk_selasa
                                WHEN 3 THEN jam_kerja.jam_masuk_rabu
                                WHEN 4 THEN jam_kerja.jam_masuk_kamis
                                WHEN 5 THEN jam_kerja.jam_masuk_jumat
                                ELSE jam_kerja.jam_masuk_sabtu
                            END
                        )) OR 
                        (users.role IN ('sumber', 'tidar') AND presensi.jam_masuk > (
                            CASE presensi.shift
                                WHEN 'A' THEN shift.masuk_a
                                WHEN 'B' THEN shift.masuk_b
                                WHEN 'C' THEN shift.masuk_c
                                WHEN 'D' THEN shift.masuk_d
                                ELSE shift.masuk_e
                            END
                        ))
                    ) THEN 
                        CASE 
                            WHEN users.role = 'pegawai' THEN 
                                TIMESTAMPDIFF(MINUTE, 
                                    CONCAT(presensi.tanggal_masuk, ' ', 
                                        CASE DAYOFWEEK(presensi.tanggal_masuk)
                                            WHEN 1 THEN jam_kerja.jam_masuk_senin
                                            WHEN 2 THEN jam_kerja.jam_masuk_selasa
                                            WHEN 3 THEN jam_kerja.jam_masuk_rabu
                                            WHEN 4 THEN jam_kerja.jam_masuk_kamis
                                            WHEN 5 THEN jam_kerja.jam_masuk_jumat
                                            ELSE jam_kerja.jam_masuk_sabtu
                                        END
                                    ),
                                    CONCAT(presensi.tanggal_masuk, ' ', presensi.jam_masuk)
                                )
                            WHEN users.role IN ('sumber', 'tidar') THEN
                                TIMESTAMPDIFF(MINUTE, 
                                    CONCAT(presensi.tanggal_masuk, ' ', 
                                        CASE presensi.shift
                                            WHEN 'A' THEN shift.masuk_a
                                            WHEN 'B' THEN shift.masuk_b
                                            WHEN 'C' THEN shift.masuk_c
                                            WHEN 'D' THEN shift.masuk_d
                                            ELSE shift.masuk_e
                                        END
                                    ),
                                    CONCAT(presensi.tanggal_masuk, ' ', presensi.jam_masuk)
                                )
                            ELSE 0
                        END
                    ELSE 0 END) as total_menit_terlambat,
                    COUNT(CASE WHEN presensi.tanggal_keluar IS NOT NULL AND presensi.jam_keluar IS NOT NULL AND (
                        (users.role = 'pegawai' AND presensi.jam_keluar < (
                            CASE DAYOFWEEK(presensi.tanggal_keluar)
                                WHEN 1 THEN jam_kerja.jam_pulang_senin
                                WHEN 2 THEN jam_kerja.jam_pulang_selasa
                                WHEN 3 THEN jam_kerja.jam_pulang_rabu
                                WHEN 4 THEN jam_kerja.jam_pulang_kamis
                                WHEN 5 THEN jam_kerja.jam_pulang_jumat
                                ELSE jam_kerja.jam_pulang_sabtu
                            END
                        )) OR 
                        (users.role IN ('sumber', 'tidar') AND presensi.jam_keluar < (
                            CASE presensi.shift
                                WHEN 'A' THEN shift.pulang_a
                                WHEN 'B' THEN shift.pulang_b
                                WHEN 'C' THEN shift.pulang_c
                                WHEN 'D' THEN shift.pulang_d
                                ELSE shift.pulang_e
                            END
                        ))
                    ) THEN 1 END) as jumlah_pulang_awal,
                    SUM(CASE WHEN presensi.tanggal_keluar IS NOT NULL AND presensi.jam_keluar IS NOT NULL AND (
                        (users.role = 'pegawai' AND presensi.jam_keluar < (
                            CASE DAYOFWEEK(presensi.tanggal_keluar)
                                WHEN 1 THEN jam_kerja.jam_pulang_senin
                                WHEN 2 THEN jam_kerja.jam_pulang_selasa
                                WHEN 3 THEN jam_kerja.jam_pulang_rabu
                                WHEN 4 THEN jam_kerja.jam_pulang_kamis
                                WHEN 5 THEN jam_kerja.jam_pulang_jumat
                                ELSE jam_kerja.jam_pulang_sabtu
                            END
                        )) OR 
                        (users.role IN ('sumber', 'tidar') AND presensi.jam_keluar < (
                            CASE presensi.shift
                                WHEN 'A' THEN shift.pulang_a
                                WHEN 'B' THEN shift.pulang_b
                                WHEN 'C' THEN shift.pulang_c
                                WHEN 'D' THEN shift.pulang_d
                                ELSE shift.pulang_e
                            END
                        ))
                    ) THEN 
                        CASE 
                            WHEN users.role = 'pegawai' THEN 
                                TIMESTAMPDIFF(MINUTE, 
                                    CONCAT(presensi.tanggal_keluar, ' ', presensi.jam_keluar),
                                    CONCAT(presensi.tanggal_keluar, ' ', 
                                        CASE DAYOFWEEK(presensi.tanggal_keluar)
                                            WHEN 1 THEN jam_kerja.jam_pulang_senin
                                            WHEN 2 THEN jam_kerja.jam_pulang_selasa
                                            WHEN 3 THEN jam_kerja.jam_pulang_rabu
                                            WHEN 4 THEN jam_kerja.jam_pulang_kamis
                                            WHEN 5 THEN jam_kerja.jam_pulang_jumat
                                            ELSE jam_kerja.jam_pulang_sabtu
                                        END
                                    )
                                )
                            WHEN users.role IN ('sumber', 'tidar') THEN
                                TIMESTAMPDIFF(MINUTE, 
                                    CONCAT(presensi.tanggal_keluar, ' ', presensi.jam_keluar),
                                    CONCAT(presensi.tanggal_keluar, ' ', 
                                        CASE presensi.shift
                                            WHEN 'A' THEN shift.pulang_a
                                            WHEN 'B' THEN shift.pulang_b
                                            WHEN 'C' THEN shift.pulang_c
                                            WHEN 'D' THEN shift.pulang_d
                                            ELSE shift.pulang_e
                                        END
                                    )
                                )
                            ELSE 0
                        END
                    ELSE 0 END) as total_menit_pulang_awal,
                    COUNT(CASE WHEN presensi.keterangan = 'Sakit' THEN 1 END) as jumlah_sakit,
                    COUNT(CASE WHEN presensi.keterangan = 'Izin' THEN 1 END) as jumlah_izin,
                    COUNT(CASE WHEN presensi.tanggal_masuk IS NULL AND presensi.keterangan IS NULL THEN 1 END) as jumlah_tanpa_keterangan
                FROM
                    pegawai
                    LEFT JOIN presensi ON pegawai.id = presensi.id_pegawai 
                    AND presensi.tanggal_masuk = ?
                    LEFT JOIN users ON users.id_pegawai = pegawai.id 
                    LEFT JOIN jam_kerja ON jam_kerja.id = 1
                    LEFT JOIN shift ON shift.id = 1
                WHERE
                    users.role != 'admin'
                    AND users.status = 'aktif'
                    $nama_condition
                GROUP BY pegawai.id, pegawai.nama, pegawai.lokasi_presensi, pegawai.bagian, pegawai.jabatan, users.role
                ORDER BY
                    CASE pegawai.jabatan
                        WHEN 'Direktur Utama' THEN 1
                        WHEN 'Direktur' THEN 2
                        WHEN 'Ketua' THEN 3
                        WHEN 'Manajer' THEN 4
                        WHEN 'Pengawas' THEN 5
                        WHEN 'Asisten Manajer' THEN 6
                        WHEN 'Kepala Unit' THEN 7
                        WHEN 'Komandan Regu' THEN 8
                        WHEN 'Staf' THEN 9
                        ELSE 10
                    END ASC,
                    pegawai.bagian ASC, 
                    pegawai.id ASC;";
    $stmt = $connection->prepare($query);
    if (isset($_GET['nama'])) {
        $nama = "%" . $_GET['nama'] . "%";
        $stmt->bind_param("ss", $tanggal_hari_ini, $nama);
    } else {
        $stmt->bind_param("s", $tanggal_hari_ini);
    }
} else {
    $tanggal_dari = $_GET["tanggal_dari"];
    $tanggal_sampai = $_GET["tanggal_sampai"];
    $query = "SELECT
                    pegawai.id as pegawai_id,
                    pegawai.nama,
                    pegawai.lokasi_presensi,
                    pegawai.bagian,
                    pegawai.jabatan,
                    users.role,
                    COUNT(CASE WHEN presensi.tanggal_masuk IS NOT NULL THEN 1 END) as jumlah_hadir,
                    COUNT(CASE WHEN presensi.tanggal_masuk IS NOT NULL AND (
                        (users.role = 'pegawai' AND presensi.jam_masuk > (
                            CASE DAYOFWEEK(presensi.tanggal_masuk)
                                WHEN 1 THEN jam_kerja.jam_masuk_senin
                                WHEN 2 THEN jam_kerja.jam_masuk_selasa
                                WHEN 3 THEN jam_kerja.jam_masuk_rabu
                                WHEN 4 THEN jam_kerja.jam_masuk_kamis
                                WHEN 5 THEN jam_kerja.jam_masuk_jumat
                                ELSE jam_kerja.jam_masuk_sabtu
                            END
                        )) OR 
                        (users.role IN ('sumber', 'tidar') AND presensi.jam_masuk > (
                            CASE presensi.shift
                                WHEN 'A' THEN shift.masuk_a
                                WHEN 'B' THEN shift.masuk_b
                                WHEN 'C' THEN shift.masuk_c
                                WHEN 'D' THEN shift.masuk_d
                                ELSE shift.masuk_e
                            END
                        ))
                    ) THEN 1 END) as jumlah_terlambat,
                    SUM(CASE WHEN presensi.tanggal_masuk IS NOT NULL AND (
                        (users.role = 'pegawai' AND presensi.jam_masuk > (
                            CASE DAYOFWEEK(presensi.tanggal_masuk)
                                WHEN 1 THEN jam_kerja.jam_masuk_senin
                                WHEN 2 THEN jam_kerja.jam_masuk_selasa
                                WHEN 3 THEN jam_kerja.jam_masuk_rabu
                                WHEN 4 THEN jam_kerja.jam_masuk_kamis
                                WHEN 5 THEN jam_kerja.jam_masuk_jumat
                                ELSE jam_kerja.jam_masuk_sabtu
                            END
                        )) OR 
                        (users.role IN ('sumber', 'tidar') AND presensi.jam_masuk > (
                            CASE presensi.shift
                                WHEN 'A' THEN shift.masuk_a
                                WHEN 'B' THEN shift.masuk_b
                                WHEN 'C' THEN shift.masuk_c
                                WHEN 'D' THEN shift.masuk_d
                                ELSE shift.masuk_e
                            END
                        ))
                    ) THEN 
                        CASE 
                            WHEN users.role = 'pegawai' THEN 
                                TIMESTAMPDIFF(MINUTE, 
                                    CONCAT(presensi.tanggal_masuk, ' ', 
                                        CASE DAYOFWEEK(presensi.tanggal_masuk)
                                            WHEN 1 THEN jam_kerja.jam_masuk_senin
                                            WHEN 2 THEN jam_kerja.jam_masuk_selasa
                                            WHEN 3 THEN jam_kerja.jam_masuk_rabu
                                            WHEN 4 THEN jam_kerja.jam_masuk_kamis
                                            WHEN 5 THEN jam_kerja.jam_masuk_jumat
                                            ELSE jam_kerja.jam_masuk_sabtu
                                        END
                                    ),
                                    CONCAT(presensi.tanggal_masuk, ' ', presensi.jam_masuk)
                                )
                            WHEN users.role IN ('sumber', 'tidar') THEN
                                TIMESTAMPDIFF(MINUTE, 
                                    CONCAT(presensi.tanggal_masuk, ' ', 
                                        CASE presensi.shift
                                            WHEN 'A' THEN shift.masuk_a
                                            WHEN 'B' THEN shift.masuk_b
                                            WHEN 'C' THEN shift.masuk_c
                                            WHEN 'D' THEN shift.masuk_d
                                            ELSE shift.masuk_e
                                        END
                                    ),
                                    CONCAT(presensi.tanggal_masuk, ' ', presensi.jam_masuk)
                                )
                            ELSE 0
                        END
                    ELSE 0 END) as total_menit_terlambat,
                    COUNT(CASE WHEN presensi.tanggal_keluar IS NOT NULL AND presensi.jam_keluar IS NOT NULL AND (
                        (users.role = 'pegawai' AND presensi.jam_keluar < (
                            CASE DAYOFWEEK(presensi.tanggal_keluar)
                                WHEN 1 THEN jam_kerja.jam_pulang_senin
                                WHEN 2 THEN jam_kerja.jam_pulang_selasa
                                WHEN 3 THEN jam_kerja.jam_pulang_rabu
                                WHEN 4 THEN jam_kerja.jam_pulang_kamis
                                WHEN 5 THEN jam_kerja.jam_pulang_jumat
                                ELSE jam_kerja.jam_pulang_sabtu
                            END
                        )) OR 
                        (users.role IN ('sumber', 'tidar') AND presensi.jam_keluar < (
                            CASE presensi.shift
                                WHEN 'A' THEN shift.pulang_a
                                WHEN 'B' THEN shift.pulang_b
                                WHEN 'C' THEN shift.pulang_c
                                WHEN 'D' THEN shift.pulang_d
                                ELSE shift.pulang_e
                            END
                        ))
                    ) THEN 1 END) as jumlah_pulang_awal,
                    SUM(CASE WHEN presensi.tanggal_keluar IS NOT NULL AND presensi.jam_keluar IS NOT NULL AND (
                        (users.role = 'pegawai' AND presensi.jam_keluar < (
                            CASE DAYOFWEEK(presensi.tanggal_keluar)
                                WHEN 1 THEN jam_kerja.jam_pulang_senin
                                WHEN 2 THEN jam_kerja.jam_pulang_selasa
                                WHEN 3 THEN jam_kerja.jam_pulang_rabu
                                WHEN 4 THEN jam_kerja.jam_pulang_kamis
                                WHEN 5 THEN jam_kerja.jam_pulang_jumat
                                ELSE jam_kerja.jam_pulang_sabtu
                            END
                        )) OR 
                        (users.role IN ('sumber', 'tidar') AND presensi.jam_keluar < (
                            CASE presensi.shift
                                WHEN 'A' THEN shift.pulang_a
                                WHEN 'B' THEN shift.pulang_b
                                WHEN 'C' THEN shift.pulang_c
                                WHEN 'D' THEN shift.pulang_d
                                ELSE shift.pulang_e
                            END
                        ))
                    ) THEN 
                        CASE 
                            WHEN users.role = 'pegawai' THEN 
                                TIMESTAMPDIFF(MINUTE, 
                                    CONCAT(presensi.tanggal_keluar, ' ', presensi.jam_keluar),
                                    CONCAT(presensi.tanggal_keluar, ' ', 
                                        CASE DAYOFWEEK(presensi.tanggal_keluar)
                                            WHEN 1 THEN jam_kerja.jam_pulang_senin
                                            WHEN 2 THEN jam_kerja.jam_pulang_selasa
                                            WHEN 3 THEN jam_kerja.jam_pulang_rabu
                                            WHEN 4 THEN jam_kerja.jam_pulang_kamis
                                            WHEN 5 THEN jam_kerja.jam_pulang_jumat
                                            ELSE jam_kerja.jam_pulang_sabtu
                                        END
                                    )
                                )
                            WHEN users.role IN ('sumber', 'tidar') THEN
                                TIMESTAMPDIFF(MINUTE, 
                                    CONCAT(presensi.tanggal_keluar, ' ', presensi.jam_keluar),
                                    CONCAT(presensi.tanggal_keluar, ' ', 
                                        CASE presensi.shift
                                            WHEN 'A' THEN shift.pulang_a
                                            WHEN 'B' THEN shift.pulang_b
                                            WHEN 'C' THEN shift.pulang_c
                                            WHEN 'D' THEN shift.pulang_d
                                            ELSE shift.pulang_e
                                        END
                                    )
                                )
                            ELSE 0
                        END
                    ELSE 0 END) as total_menit_pulang_awal,
                    COUNT(CASE WHEN presensi.keterangan = 'Sakit' THEN 1 END) as jumlah_sakit,
                    COUNT(CASE WHEN presensi.keterangan = 'Izin' THEN 1 END) as jumlah_izin,
                    COUNT(CASE WHEN presensi.tanggal_masuk IS NULL AND presensi.keterangan IS NULL THEN 1 END) as jumlah_tanpa_keterangan
                FROM
                    pegawai
                    LEFT JOIN presensi ON pegawai.id = presensi.id_pegawai 
                    AND presensi.tanggal_masuk BETWEEN ? AND ?
                    LEFT JOIN users ON users.id_pegawai = pegawai.id 
                    LEFT JOIN jam_kerja ON jam_kerja.id = 1
                    LEFT JOIN shift ON shift.id = 1
                WHERE
                    users.role != 'admin'
                    $nama_condition
                GROUP BY pegawai.id, pegawai.nama, pegawai.lokasi_presensi, pegawai.bagian, pegawai.jabatan, users.role
                ORDER BY
                    CASE pegawai.jabatan
                        WHEN 'Direktur Utama' THEN 1
                        WHEN 'Direktur' THEN 2
                        WHEN 'Ketua' THEN 3
                        WHEN 'Manajer' THEN 4
                        WHEN 'Pengawas' THEN 5
                        WHEN 'Asisten Manajer' THEN 6
                        WHEN 'Kepala Unit' THEN 7
                        WHEN 'Komandan Regu' THEN 8
                        WHEN 'Staf' THEN 9
                        ELSE 10
                    END ASC,
                    pegawai.bagian ASC, 
                    pegawai.id ASC;";
    $stmt = $connection->prepare($query);
    if (isset($_GET['nama'])) {
        $nama = "%" . $_GET['nama'] . "%";
        $stmt->bind_param("sss", $tanggal_dari, $tanggal_sampai, $nama);
    } else {
        $stmt->bind_param("ss", $tanggal_dari, $tanggal_sampai);
    }
}
$stmt->execute();
$result = $stmt->get_result();

$bulan = empty($_GET['tanggal_dari']) ? date('Y-m-d') : $_GET['tanggal_dari'] . '-' . $_GET['tanggal_sampai'];
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

    /* Summary cards styling - Flat */
    .summary-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        border: 1px solid var(--border-color);
    }

    .summary-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 15px;
        text-transform: none;
        letter-spacing: 0;
    }

    /* Card styling for summary - Flat */
    .card {
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: white;
    }

    .card:hover {
        transform: none;
        box-shadow: none;
    }

    .card-body {
        padding: 20px;
    }

    .card-title {
        font-size: 14px;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0;
    }

    .card h3 {
        font-size: 24px;
        font-weight: 600;
        margin: 0;
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

    .btn::before {
        display: none;
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

    .table {
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: white;
    }

    .table thead th {
        background: var(--primary-color);
        color: white;
        font-weight: 500;
        border: none;
        padding: 12px;
        font-size: 13px;
        text-transform: none;
        letter-spacing: 0;
    }

    /* Warna baris tabel - Flat */
    tr[style*="Kantor PDAM"] {
        background-color: #f0f9ff !important;
    }

    tr[style*="background-color: #d1e7dd"] {
        background-color: #f0fdf4 !important;
    }

    td[style*="background-color: #f0f8ff"] {
        background-color: #f8fafc !important;
    }

    td[style*="background-color: #e6ffe6"] {
        background-color: #f0fdf4 !important;
    }

    td[style*="background-color: #ffe6e6"] {
        background-color: #fef2f2 !important;
    }

    td[style*="background-color: #fff3e6"] {
        background-color: #fff7ed !important;
    }

    /* Badge styles - Flat */
    .badge {
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 500;
        font-size: 12px;
        text-transform: none;
        letter-spacing: 0;
        border: 1px solid transparent;
        min-width: 40px;
        display: inline-block;
    }

    .badge:hover {
        transform: none;
        box-shadow: none;
    }

    .badge.bg-success {
        background: var(--success-color) !important;
        color: white !important;
        border-color: var(--success-color);
    }

    .badge.bg-primary {
        background: var(--primary-color) !important;
        color: white !important;
        border-color: var(--primary-color);
    }

    .badge.bg-secondary {
        background: var(--secondary-color) !important;
        color: white !important;
        border-color: var(--secondary-color);
    }

    .badge.bg-warning {
        background: var(--warning-color) !important;
        color: white !important;
        border-color: var(--warning-color);
    }

    .badge.bg-info {
        background: #0dcaf0 !important;
        color: white !important;
        border-color: #0dcaf0;
    }

    .badge.bg-danger {
        background: var(--danger-color) !important;
        color: white !important;
        border-color: var(--danger-color);
    }

    /* Status colors */
    span[style*="color: red"] {
        color: var(--danger-color) !important;
    }

    /* Input groups - Flat */
    .input-group {
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: white;
    }

    .input-group:hover {
        box-shadow: none;
    }

    .input-group input {
        border: none;
        padding: 10px 12px;
        font-size: 14px;
        font-weight: 400;
        background: white;
    }

    .input-group input:focus {
        outline: none;
        background: white;
        box-shadow: none;
    }

    .input-group .btn {
        border-radius: 0 6px 6px 0;
        margin: 0;
    }

    /* Modal styling - Flat */
    .modal-content {
        border-radius: 8px;
        border: 1px solid var(--border-color);
        box-shadow: none;
    }

    .modal-header {
        background: var(--primary-color);
        color: white;
        border-radius: 8px 8px 0 0;
        border-bottom: none;
        padding: 16px 20px;
    }

    .modal-header .modal-title {
        font-weight: 600;
        text-transform: none;
        letter-spacing: 0;
    }

    .modal-body {
        padding: 20px;
        background: white;
    }

    .modal-body .form-label {
        font-weight: 500;
        color: var(--text-dark);
        text-transform: none;
        letter-spacing: 0;
        margin-bottom: 8px;
    }

    .modal-body .form-control {
        border-radius: 6px;
        border: 1px solid var(--border-color);
        padding: 10px 12px;
        font-weight: 400;
    }

    .modal-body .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: none;
        outline: none;
    }

    /* Date display - Flat */
    .date-display {
        background: white;
        color: var(--text-dark);
        font-size: 14px;
        font-weight: 500;
        margin: 20px 0;
        padding: 12px 16px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        text-align: center;
        text-transform: none;
        letter-spacing: 0;
    }

    /* Action buttons container - Flat */
    .action-buttons {
        background: white;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid var(--border-color);
    }

    /* Filter buttons container - Flat */
    .filter-buttons {
        background: white;
        padding: 12px;
        border-radius: 8px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        border: 1px solid var(--border-color);
    }

    /* Tambahkan CSS baru untuk tombol - Flat */
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .action-buttons .btn {
        min-width: 100px;
        padding: 8px 12px;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 36px;
    }

    .filter-buttons {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
    }

    .filter-buttons .btn {
        min-width: 90px;
        white-space: nowrap;
    }

    .search-container {
        flex: 1;
        margin-left: 15px;
    }

    .search-container .input-group {
        max-width: 100%;
    }

    .date-filter {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .date-filter input[type="date"] {
        width: 200px;
    }

    .export-btn {
        width: 100%;
        margin-bottom: 15px;
    }

    .badge-pill {
        padding: 6px 12px;
        text-decoration: none;
        margin: 0 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0;
        border-radius: 4px;
        border: 1px solid transparent;
        display: inline-block;
        min-width: 60px;
    }

    .badge-pill:hover {
        transform: none;
        box-shadow: none;
        text-decoration: none;
    }

    /* Clickable card styling */
    .clickable-card {
        transition: all 0.2s ease;
        border: 2px solid transparent !important;
    }

    .clickable-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        border-color: var(--primary-color) !important;
    }

    .clickable-card.active {
        border-color: var(--primary-color) !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3) !important;
    }

    .clickable-card .card-body {
        position: relative;
    }

    .clickable-card .card-body::after {
        content: '🔍';
        position: absolute;
        top: 8px;
        right: 8px;
        font-size: 12px;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .clickable-card:hover .card-body::after {
        opacity: 0.6;
    }
</style>

<style>
    /* Tambahan agar tabel tidak melebihi container dan bisa di-scroll - Flat */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        position: relative;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .table {
        min-width: 1200px;
        margin-bottom: 0;
        border-collapse: collapse;
        background: white;
    }

    /* Sticky header saat scroll horizontal - Flat */
    .table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--primary-color) !important;
        color: white !important;
        font-weight: 500;
        font-size: 12px;
        text-align: center;
        vertical-align: middle;
        border: none;
        white-space: nowrap;
        padding: 12px 10px;
        text-transform: none;
        letter-spacing: 0;
    }

    /* Sticky kolom pertama - Flat */
    .table th:first-child,
    .table td:first-child {
        position: sticky;
        left: 0;
        z-index: 3;
        background: white;
        border-right: 1px solid var(--border-color);
    }

    .table th,
    .table td {
        vertical-align: middle;
        text-align: center;
        white-space: nowrap;
        border: 1px solid var(--border-color);
        font-size: 13px;
        padding: 10px 8px;
        background: white;
    }

    .table th {
        color: white !important;
    }

    .table td {
        color: var(--text-dark) !important;
        background: white;
        font-weight: 400;
    }

    .table td:first-child {
        text-align: left;
        font-weight: 500;
        color: var(--primary-color) !important;
        background: #f8fafc;
    }

    .table tbody tr {
        border-bottom: 1px solid var(--border-color);
    }

    .table tbody tr:hover {
        background: #f9fafb;
        transform: none;
        box-shadow: none;
    }

    .table tbody tr:nth-child(even) {
        background: #fafbfc;
    }

    .table tbody tr:nth-child(even):hover {
        background: #f1f5f9;
    }

    @media (max-width: 900px) {
        .table {
            min-width: 1000px;
        }

        .table thead th {
            font-size: 11px;
            padding: 10px 6px;
        }

        .table td {
            font-size: 12px;
            padding: 8px 6px;
        }
    }
</style>

<div class="page-body">
    <div class="container-xl">
        <div class="action-buttons">
            <div class="col-md-12" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <form method="GET" class="date-filter" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="date" class="form-control" name="tanggal_dari" value="<?= isset($_GET['tanggal_dari']) ? htmlspecialchars($_GET['tanggal_dari']) : date('Y-m-d') ?>" required>
                    <input type="date" class="form-control" name="tanggal_sampai" value="<?= isset($_GET['tanggal_sampai']) ? htmlspecialchars($_GET['tanggal_sampai']) : date('Y-m-d') ?>" required>
                    <input type="text" class="form-control" name="nama" placeholder="🔍 Cari berdasarkan nama..." value="<?= isset($_GET['nama']) ? htmlspecialchars($_GET['nama']) : '' ?>" style="min-width:200px;">
                    <button type="submit" class="btn btn-primary">🔎 Filter</button>
                    <button type="submit" formaction="rekap_all_excel.php" class="btn btn-primary export-btn">
                        📊 Export Excel
                    </button>
                </form>
            </div>
        </div>

        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="filter-buttons">
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian.php'">📊 Semua</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_kantor.php'">🏢 Kantor</button>
                    <button class="btn btn-secondary">👮 Satpam</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_sumber.php'">💧 Sumber</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_absen.php'">📝 Absen</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_harian_event.php'">🎉 Event</button>
                    <button class="btn btn-primary" onclick="location.href='../presensi/rekap_all.php'">📈 Rekap</button>
                    <button class="btn btn-secondary" onclick="location.href='../presensi/rekap_izin.php'">📈 Izin/Cuti</button>
                </div>
            </div>
        </div>

        <div class="date-display mb-3">
            <?php if (empty($_GET['tanggal_dari'])) : ?>
                <span>📅 Presensi Tanggal: <?= date('d F Y') ?></span>
            <?php else : ?>
                <span>📅 Presensi Tanggal: <?= date('d F Y', strtotime($_GET['tanggal_dari'])) . ' sampai ' . date('d F Y', strtotime($_GET['tanggal_sampai'])) ?></span>
            <?php endif; ?>
        </div>

        <?php
        // Store the result data for summary calculations
        $summary_data = [];
        $total_hadir = 0;
        $total_terlambat = 0;
        $total_pulang_awal = 0;
        $total_tidak_presensi_pulang = 0;
        $total_sakit = 0;
        $total_izin = 0;
        $total_tanpa_keterangan = 0;

        // Reset result pointer
        $result->data_seek(0);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $summary_data[] = $row;
                $total_hadir += $row['jumlah_hadir'];
                $total_terlambat += $row['jumlah_terlambat'];
                $total_pulang_awal += $row['jumlah_pulang_awal'];
                $total_sakit += $row['jumlah_sakit'];
                $total_izin += $row['jumlah_izin'];
                $total_tanpa_keterangan += $row['jumlah_tanpa_keterangan'];
            }
        }
        ?>
        <div class="summary-section">
            <div class="summary-title">
                📊 Ringkasan Statistik Presensi
            </div>
            <div class="row">
                <div class="col-md-2 mb-3">
                    <div class="card border-0 shadow-sm clickable-card" onclick="filterByStatus('hadir')" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <h5 class="card-title text-success mb-1">✅ Hadir</h5>
                            <h3 class="text-success mb-0"><?= $total_hadir ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card border-0 shadow-sm clickable-card" onclick="filterByStatus('terlambat')" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <h5 class="card-title text-warning mb-1">⏰ Terlambat</h5>
                            <h3 class="text-warning mb-0"><?= $total_terlambat ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card border-0 shadow-sm clickable-card" onclick="filterByStatus('pulang-awal')" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <h5 class="card-title text-info mb-1">🏃 Pulang Awal</h5>
                            <h3 class="text-info mb-0"><?= $total_pulang_awal ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card border-0 shadow-sm clickable-card" onclick="filterByStatus('tidak-presensi-pulang')" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <h5 class="card-title text-danger mb-1">❌ Tdk Presensi Pulang</h5>
                            <h3 class="text-danger mb-0"><?= $total_tidak_presensi_pulang ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card border-0 shadow-sm clickable-card" onclick="filterByStatus('sakit')" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary mb-1">🏥 Sakit</h5>
                            <h3 class="text-primary mb-0"><?= $total_sakit ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card border-0 shadow-sm clickable-card" onclick="filterByStatus('izin')" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary mb-1">📝 Izin</h5>
                            <h3 class="text-primary mb-0"><?= $total_izin ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card border-0 shadow-sm clickable-card" onclick="filterByStatus('tanpa-keterangan')" style="cursor: pointer;">
                        <div class="card-body text-center">
                            <h5 class="card-title text-danger mb-1">❌ Tanpa Keterangan</h5>
                            <h3 class="text-danger mb-0"><?= $total_tanpa_keterangan ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12 text-center">
                    <button class="btn btn-secondary active me-2" onclick="filterByStatus('all')">📊 Tampilkan Semua Data</button>
                    <small class="text-muted">Klik kartu statistik di atas untuk memfilter data berdasarkan kategori</small>
                </div>
            </div>
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-bordered mt-2">
                <thead>
                    <tr class="text-center">
                        <th>Nama</th>
                        <th>Jumlah Hadir</th>
                        <th>Jumlah Terlambat</th>
                        <th>Total Jam Terlambat</th>
                        <th>Jumlah Pulang Awal</th>
                        <th>Total Jam Pulang Awal</th>
                        <th>Jml Tdk Presensi Pulang</th>
                        <th>Jumlah Sakit</th>
                        <th>Jumlah Izin</th>
                        <th>Jumlah Tanpa Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summary_data)) { ?>
                        <tr>
                            <td colspan="10" class="text-center">Belum ada data</td>
                        </tr>
                        <?php } else {
                        foreach ($summary_data as $rekap) : ?>
                            <tr style="<?= $rekap['lokasi_presensi'] == 'Kantor PDAM' ? 'background-color: #add8e6;' : 'background-color: #d1e7dd;'; ?>"
                                data-hadir="<?= $rekap['jumlah_hadir'] ?>"
                                data-terlambat="<?= $rekap['jumlah_terlambat'] ?>"
                                data-pulang-awal="<?= $rekap['jumlah_pulang_awal'] ?>"
                                data-tidak-presensi-pulang="<?= $rekap['jumlah_tidak_presensi_pulang'] ?>"
                                data-sakit="<?= $rekap['jumlah_sakit'] ?>"
                                data-izin="<?= $rekap['jumlah_izin'] ?>"
                                data-tanpa-keterangan="<?= $rekap['jumlah_tanpa_keterangan'] ?>">
                                <td style="background-color: #f0f8ff;"><?= htmlspecialchars($rekap['nama']) ?></td>
                                <td class="text-center">
                                    <?php if ($rekap['jumlah_hadir'] > 0) : ?>
                                        <span class="badge bg-success"><?= $rekap['jumlah_hadir'] ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-danger">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($rekap['jumlah_terlambat'] > 0) : ?>
                                        <span class="badge bg-warning"><?= $rekap['jumlah_terlambat'] ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $total_jam_terlambat = floor($rekap['total_menit_terlambat'] / 60);
                                    $total_menit_terlambat = $rekap['total_menit_terlambat'] % 60;
                                    if ($rekap['total_menit_terlambat'] > 0) : ?>
                                        <span class="badge bg-warning"><?= $total_jam_terlambat ?>j <?= $total_menit_terlambat ?>m</span>
                                    <?php else : ?>
                                        <span class="badge bg-success">0j 0m</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($rekap['jumlah_pulang_awal'] > 0) : ?>
                                        <span class="badge bg-warning"><?= $rekap['jumlah_pulang_awal'] ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $total_jam_pulang_awal = floor($rekap['total_menit_pulang_awal'] / 60);
                                    $total_menit_pulang_awal = $rekap['total_menit_pulang_awal'] % 60;
                                    if ($rekap['total_menit_pulang_awal'] > 0) : ?>
                                        <span class="badge bg-warning"><?= $total_jam_pulang_awal ?>j <?= $total_menit_pulang_awal ?>m</span>
                                    <?php else : ?>
                                        <span class="badge bg-success">0j 0m</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"> <?php if ($rekap['jumlah_tidak_presensi_pulang'] > 0) : ?>
                                        <span class="badge bg-danger"><?= $rekap['jumlah_tidak_presensi_pulang'] ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($rekap['jumlah_sakit'] > 0) : ?>
                                        <span class="badge bg-info"><?= $rekap['jumlah_sakit'] ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($rekap['jumlah_izin'] > 0) : ?>
                                        <span class="badge bg-info"><?= $rekap['jumlah_izin'] ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($rekap['jumlah_tanpa_keterangan'] > 0) : ?>
                                        <span class="badge bg-danger"><?= $rekap['jumlah_tanpa_keterangan'] ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="/admin/presensi/rekap.php?id=<?= htmlspecialchars($rekap['pegawai_id']) ?>" class="badge badge-pill bg-primary">Rekap</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include('../layout/footer.php');
ob_end_flush();
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.sort-btn');
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const column = button.dataset.column;
                const order = button.dataset.order === 'asc' ? 'desc' : 'asc';
                sortTable(column, order);
                button.dataset.order = order;
            });
        });
    });

    function sortTable(column, order) {
        const table = document.querySelector('table');
        const rows = Array.from(table.querySelectorAll('tr')).slice(1); // Exclude the header row
        const isNumeric = !isNaN(rows[0].querySelectorAll('td')[column].innerText);
        const sortedRows = rows.sort((a, b) => {
            const aValue = isNumeric ? parseFloat(a.querySelectorAll('td')[column].innerText) : a.querySelectorAll('td')[column].innerText;
            const bValue = isNumeric ? parseFloat(b.querySelectorAll('td')[column].innerText) : b.querySelectorAll('td')[column].innerText;
            return order === 'asc' ? aValue > bValue ? 1 : -1 : aValue < bValue ? 1 : -1;
        });

        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';
        sortedRows.forEach(row => tbody.appendChild(row));
    }

    function filterByStatus(status) {
        const table = document.querySelector('table tbody');
        const rows = Array.from(table.querySelectorAll('tr'));
        const cards = document.querySelectorAll('.clickable-card');
        const buttons = document.querySelectorAll('.btn');

        // Update card and button states
        cards.forEach(card => card.classList.remove('active'));
        buttons.forEach(btn => btn.classList.remove('active'));

        // Add active class to clicked element
        if (event.target.closest('.clickable-card')) {
            event.target.closest('.clickable-card').classList.add('active');
        } else if (event.target.classList.contains('btn')) {
            event.target.classList.add('active');
        }

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 0) return;

            let shouldShow = false;

            switch (status) {
                case 'all':
                    shouldShow = true;
                    break;
                case 'hadir':
                    shouldShow = parseInt(row.dataset.hadir) > 0;
                    break;
                case 'terlambat':
                    shouldShow = parseInt(row.dataset.terlambat) > 0;
                    break;
                case 'pulang-awal':
                    shouldShow = parseInt(row.dataset.pulangAwal) > 0;
                    break;
                case 'sakit':
                    shouldShow = parseInt(row.dataset.sakit) > 0;
                    break;
                case 'izin':
                    shouldShow = parseInt(row.dataset.izin) > 0;
                    break;
                case 'tanpa-keterangan':
                    shouldShow = parseInt(row.dataset.tanpaKeterangan) > 0;
                    break;
            }

            row.style.display = shouldShow ? '' : 'none';
        });
    }
</script>