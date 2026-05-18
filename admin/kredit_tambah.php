<?php
require '../config/security.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD']=='POST'){

/* ================= NASABAH ================= */

$nama        = $_POST['nama'];
$alamat      = $_POST['alamat'];
$rt_rw       = $_POST['rt_rw'];
$kelurahan   = $_POST['kelurahan'];
$kecamatan   = $_POST['kecamatan'];
$no_hp       = $_POST['no_hp'];
$jk          = $_POST['jenis_kelamin'];
$pekerjaan   = $_POST['pekerjaan'];
$email       = $_POST['email'];

/* ================= MOTOR ================= */

$merk       = $_POST['merk'];
$tipe       = $_POST['tipe'];
$warna      = $_POST['warna'];
$no_rangka  = $_POST['no_rangka'];
$no_mesin   = $_POST['no_mesin'];
$harga_otr  = preg_replace('/[^0-9]/','',$_POST['harga_otr']);

/* ================= KREDIT ================= */

$dp       = preg_replace('/[^0-9]/','',$_POST['dp']);
$tenor    = $_POST['tenor'];
$angsuran = preg_replace('/[^0-9]/','',$_POST['angsuran']);
$jatuh_tempo = $_POST['jatuh_tempo'];

/* =========================
GENERATE NO KONTRAK
FORMAT : GMYYYYMMDD001
========================= */

$tanggal = date('Ymd');

$q = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total
FROM penjualan
WHERE DATE(created_at)=CURDATE()
"));

$urutan = $q['total'] + 1;

$no_kontrak = "GM".$tanggal.str_pad($urutan,3,"0",STR_PAD_LEFT);

/* ================= SIMPAN MOTOR ================= */

mysqli_query($conn,"
INSERT INTO kendaraan
(merk,tipe,warna,no_rangka,no_mesin,harga_cash,status)
VALUES
('$merk','$tipe','$warna','$no_rangka','$no_mesin','$harga_otr','TERJUAL')
");

$kendaraan_id = mysqli_insert_id($conn);

/* ================= BUAT AKUN NASABAH ================= */

$nama_clean = strtolower(preg_replace("/[^a-z]/","",$nama));
$nama_part  = substr($nama_clean,0,4);

$random     = rand(1000,9999);

$password_plain = ucfirst($nama_part).$random;

$password = password_hash($password_plain,PASSWORD_DEFAULT);

mysqli_query($conn,"
INSERT INTO users
(username,password,role,status,created_at)
VALUES
('$no_kontrak','$password','nasabah','AKTIF',NOW())
");

/* ================= PROFIL NASABAH ================= */

mysqli_query($conn,"
INSERT INTO nasabah_profile
(no_kontrak,nama,alamat,rt_rw,kelurahan,kecamatan,no_hp,jenis_kelamin,pekerjaan,email)
VALUES
('$no_kontrak','$nama','$alamat','$rt_rw','$kelurahan','$kecamatan','$no_hp','$jk','$pekerjaan','$email')
");


/* ================= KIRIM EMAIL AKUN NASABAH ================= */

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

$mail->Subject = 'Akun Nasabah GLOBAL MOTOR';

$mail->Body = "

<div style='font-family:Arial;background:#f4f6f9;padding:20px'>

<div style='max-width:500px;margin:auto;background:white;border-radius:8px;padding:25px;border:1px solid #ddd'>

<center>
<img src='https://i.ibb.co.com/39TsRYtW/logohitam.png' width='120'><br>
<h2 style='margin:10px 0;color:#333'>GLOBAL MOTOR</h2>
</center>

<hr>

<h3 style='color:#444'>Akun Nasabah Anda Telah Dibuat</h3>

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
<td>Username</td>
<td><b>$no_kontrak</b></td>
</tr>

<tr>
<td>Password</td>
<td><b>$password_plain</b></td>
</tr>

</table>

<br>

<div style='background:#eef4ff;padding:10px;border-radius:6px'>
Silakan login ke sistem nasabah GLOBAL MOTOR menggunakan akun di atas.
</div>

<br>

<center style='font-size:12px;color:#777'>
GLOBAL MOTOR<br>
Simpan email ini sebagai informasi akun anda.
</center>

</div>

</div>

";

$mail->send();

} catch (Exception $e) {

// jika email gagal, sistem tetap jalan

}

/* ================= DATA PENJUALAN ================= */

mysqli_query($conn,"
INSERT INTO penjualan
(no_kontrak,kendaraan_id,dp,tenor,angsuran,created_at)
VALUES
('$no_kontrak','$kendaraan_id','$dp','$tenor','$angsuran',NOW())
");

$penjualan_id = mysqli_insert_id($conn);

/* ================= GENERATE ANGSURAN ================= */

for($i=0;$i<$tenor;$i++){

$tempo = date('Y-m-d',strtotime("+$i month",strtotime($jatuh_tempo)));

mysqli_query($conn,"
INSERT INTO angsuran
(penjualan_id,bulan_ke,jumlah,jatuh_tempo,status)
VALUES
('$penjualan_id','".($i+1)."','$angsuran','$tempo','BELUM')
");

}

echo "<script>
alert('Kredit berhasil ditambahkan');
window.location='dashboard.php?page=user_nasabah';
</script>";
exit;

}
?>

<!DOCTYPE html>
<html>
<head>

<title>Tambah Kredit</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-4">

<h4 class="mb-3">Tambah Kredit</h4>

<form method="POST" class="card shadow-sm p-4 needs-validation" novalidate>

<div class="row">

<!-- ================= NASABAH ================= -->

<div class="col-md-6">

<div class="card shadow-sm mb-3">
<div class="card-body">

<h6 class="fw-bold mb-3">Data Nasabah</h6>

<input name="nama" class="form-control mb-2" placeholder="Nama Nasabah" required>

<input name="no_hp" class="form-control mb-2" placeholder="No HP" required>

<input name="email" class="form-control mb-2" placeholder="Email" required>

<select name="jenis_kelamin" class="form-control mb-2" required>
<option value="">Jenis Kelamin</option>
<option value="L">Laki-laki</option>
<option value="P">Perempuan</option>
</select>

<textarea name="alamat" class="form-control mb-2" placeholder="Alamat" required></textarea>

<input name="rt_rw" class="form-control mb-2" placeholder="RT/RW" required>

<input name="kelurahan" class="form-control mb-2" placeholder="Kelurahan" required>

<input name="kecamatan" class="form-control mb-2" placeholder="Kecamatan" required>

<input name="pekerjaan" class="form-control mb-2" placeholder="Pekerjaan" required>

</div>
</div>

</div>


<!-- ================= MOTOR ================= -->

<div class="col-md-6">

<div class="card shadow-sm mb-3">
<div class="card-body">

<h6 class="fw-bold mb-3">Data Motor</h6>

<input name="merk" class="form-control mb-2" placeholder="Merk Motor" required>

<input name="tipe" class="form-control mb-2" placeholder="Tipe Motor" required>

<input name="warna" class="form-control mb-2" placeholder="Warna" required>

<input name="no_rangka" class="form-control mb-2" placeholder="No Rangka" required>

<input name="no_mesin" class="form-control mb-2" placeholder="No Mesin" required>

<input name="harga_otr" id="otr" class="form-control mb-2" placeholder="Harga OTR" required>

</div>
</div>

</div>

</div>


<!-- ================= KREDIT ================= -->

<div class="card shadow-sm">
<div class="card-body">

<h6 class="fw-bold mb-3">Data Kredit</h6>

<div class="row">

<div class="col-md-3">
<input name="dp" id="dp" class="form-control mb-2" placeholder="DP" required>
</div>

<div class="col-md-3">
<input name="tenor" class="form-control mb-2" placeholder="Tenor (bulan)" required>
</div>

<div class="col-md-3">
<input name="angsuran" id="angsuran" class="form-control mb-2" placeholder="Jumlah Angsuran" required>
</div>

<div class="col-md-3">
<input type="date" name="jatuh_tempo" class="form-control mb-2" required>
</div>

</div>

<button class="btn btn-primary w-100 mt-2">
Simpan Kredit
</button>

</div>
</div>

</form>

</div>


<script>

document.querySelectorAll('input, select, textarea').forEach((field, index, fields) => {

field.addEventListener('keydown', function(e){

if(e.key === "Enter"){

e.preventDefault();

let nextField = fields[index + 1];

if(nextField){
nextField.focus();
}

}

});

});



(() => {
'use strict'

const forms = document.querySelectorAll('.needs-validation')

Array.from(forms).forEach(form => {
form.addEventListener('submit', event => {

if (!form.checkValidity()) {
event.preventDefault()
event.stopPropagation()
}

form.classList.add('was-validated')

}, false)
})
})();


document.querySelectorAll('input').forEach(function(el){
el.addEventListener('keydown', function(e){
if(e.key === "Enter"){
e.preventDefault();
}
});
});


function formatRupiah(input){

input.addEventListener('input', function(){

let angka = this.value.replace(/[^0-9]/g,'');

if(angka === ''){
this.value='';
return;
}

this.value = 'Rp ' + angka.replace(/\B(?=(\d{3})+(?!\d))/g,'.');

});

}

formatRupiah(document.getElementById('otr'));
formatRupiah(document.getElementById('dp'));
formatRupiah(document.getElementById('angsuran'));

</script>

</body>
</html>