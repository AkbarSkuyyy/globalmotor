<?php

require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/* ================= AMBIL ID ================= */

$id = $_GET['id'] ?? '';

if(!$id){
die("ID nasabah tidak ditemukan");
}


/* ================= AMBIL DATA ================= */

$data = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT 
u.username,
np.nama,
np.email
FROM users u
LEFT JOIN nasabah_profile np 
ON np.no_kontrak=u.username
WHERE u.id='$id'
"));

if(!$data){
die("Data nasabah tidak ditemukan");
}

$nama = $data['nama'];
$email = $data['email'];
$no_kontrak = $data['username'];


/* ================= GENERATE PASSWORD ================= */

$nama_clean = strtolower(preg_replace("/[^a-z]/","",$nama));

$nama_part = substr($nama_clean,0,4);

$random = rand(1000,9999);

$password_plain = ucfirst($nama_part).$random;

$password_hash = password_hash($password_plain,PASSWORD_DEFAULT);


/* ================= UPDATE PASSWORD ================= */

mysqli_query($conn,"
UPDATE users 
SET password='$password_hash'
WHERE id='$id'
");


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

<div style='font-family:Arial;background:#f4f6f9;padding:20px'>

<div style='max-width:500px;margin:auto;background:white;border-radius:8px;padding:25px;border:1px solid #ddd'>

<center>

<img src='https://i.ibb.co.com/39TsRYtW/logohitam.png' width='120'><br>
<h2 style='margin:10px 0;color:#333'>GLOBAL MOTOR</h2>

</center>

<hr>

<h3>Password Akun Anda Telah Direset</h3>

<table style='width:100%;font-size:14px'>

<tr>
<td>Nama</td>
<td><b>$nama</b></td>
</tr>

<tr>
<td>No Kontrak</td>
<td><b>$no_kontrak</b></td>
</tr>

<tr>
<td>Password Baru</td>
<td><b>$password_plain</b></td>
</tr>

</table>

<br>

<div style='background:#eef4ff;padding:10px;border-radius:6px'>
Silakan login kembali menggunakan password baru.
</div>

<br>

<center style='font-size:12px;color:#777'>
GLOBAL MOTOR
</center>

</div>

</div>

";

$mail->send();

}catch (Exception $e){}


/* ================= REDIRECT ================= */

echo "<script>

alert('Password berhasil direset dan email telah dikirim');

window.location='dashboard.php?page=user_nasabah';

</script>";

?>