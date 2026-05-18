<?php
session_start();

/*
|--------------------------------------------------------------------------
| GLOBAL MOTOR - ENTRY POINT
|--------------------------------------------------------------------------
| Redirect otomatis berdasarkan status login & role
*/

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {

    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    }

    if ($_SESSION['role'] === 'karyawan') {
        header('Location: karyawan/dashboard.php');
        exit;
    }

    if ($_SESSION['role'] === 'nasabah') {
        header('Location: nasabah/dashboard.php');
        exit;
    }

}

// jika belum login
header('Location: auth/login.php');
exit;
