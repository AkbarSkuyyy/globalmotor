<?php
// index.php (Root Router)
session_start();

// 1. CEK SESI: Jika user sudah login, langsung arahkan ke dashboard masing-masing TANPA .php
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard");
    } elseif ($_SESSION['role'] === 'karyawan') {
        header("Location: karyawan/dashboard");
    } else {
        header("Location: nasabah/dashboard");
    }
    exit();
}

// 2. Jika belum login, otomatis arahkan ke halaman login TANPA .php
header("Location: auth/login");
exit();