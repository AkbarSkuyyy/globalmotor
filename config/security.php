<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


/* ================= MAINTENANCE MODE ================= */

if (file_exists(__DIR__.'/../maintenance.flag')) {

die("
<div style='text-align:center;margin-top:100px;font-family:Arial'>
<h2>⚙️ Sistem Sedang Maintenance</h2>
<p>Silakan hubungi administrator.</p>
</div>
");

}


/* ================= SESSION CONFIG (HOSTING STABLE) ================= */

if (session_status() === PHP_SESSION_NONE) {

/* COOKIE GLOBAL DOMAIN */
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

/* HTTPS COOKIE */
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
ini_set('session.cookie_secure', 1);
}

session_start();

}


/* ================= SESSION TIMEOUT (30 MENIT) ================= */

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {

session_unset();
session_destroy();

header("Location: https://globalmotor.my.id/auth/login.php");
exit;

}

$_SESSION['LAST_ACTIVITY'] = time();


/* ================= CEK LOGIN ================= */

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {

header("Location: https://globalmotor.my.id/auth/login.php");
exit;

}