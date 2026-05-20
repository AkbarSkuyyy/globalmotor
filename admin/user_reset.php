<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ================= AMBIL ID & ROLE ================= */
$id = $_GET['id'] ?? '';
$role = $_GET['role'] ?? 'nasabah'; // Kita tambahkan parameter role

if(!$id){ die("ID tidak ditemukan"); }

/* ================= AMBIL DATA (DINAMIS) ================= */
if($role == 'karyawan') {
    // Untuk Karyawan
    $data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username, 'Karyawan' as nama, 'karyawan@globalmotor.id' as email FROM users WHERE id='$id'"));
    $redirect = "user_karyawan";
} else {
    // Untuk Nasabah
    $data = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT u.username, np.nama, np.email 
        FROM users u 
        LEFT JOIN nasabah_profile np ON np.no_kontrak=u.username 
        WHERE u.id='$id'
    "));
    $redirect = "user_nasabah";
}

if(!$data){ die("Data pengguna tidak ditemukan"); }

$nama = $data['nama'];
$email = $data['email'];
$no_kontrak = $data['username'];

/* ================= GENERATE PASSWORD ================= */
$nama_clean = strtolower(preg_replace("/[^a-z]/","", $nama));
$nama_part = substr($nama_clean,0,4);
$random = rand(1000,9999);
$password_plain = ucfirst($nama_part).$random;
$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

/* ================= UPDATE PASSWORD ================= */
mysqli_query($conn, "UPDATE users SET password='$password_hash' WHERE id='$id'");

/* ================= KIRIM EMAIL ================= */
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'globalmotor7062@gmail.com';
    $mail->Password   = 'xnxkmpbjclnotnwn'; 
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->setFrom('globalmotor7062@gmail.com', 'GLOBAL MOTOR');
    $mail->addAddress($email, $nama);
    $mail->isHTML(true);
    $mail->Subject = "Reset Password Akun GLOBAL MOTOR";
    $mail->Body = "
    <div style='font-family:Arial;padding:20px'>
        <h3>Password Akun Anda Telah Direset</h3>
        <p>Nama: <b>$nama</b></p>
        <p>Password Baru: <b>$password_plain</b></p>
        <p>Silakan login kembali.</p>
    </div>";
    $mail->send();
} catch (Exception $e) {}

/* ================= REDIRECT DINAMIS ================= */
echo "<script>
    alert('Password berhasil direset!');
    window.location='dashboard.php?page=$redirect';
</script>";
?>