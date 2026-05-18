<?php

/* ================= TIMEZONE ================= */
date_default_timezone_set('Asia/Jakarta');

/* ================= DATABASE ================= */

$host = "localhost";
$user = "globalmo_tor";
$pass = "@Globalmotor";
$db   = "globalmo_tor";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

/* ================= UTF8 ================= */

mysqli_set_charset($conn, "utf8mb4");