<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include '../config/database.php';
include '../config/audit_log.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = $_POST['password'];

$q = mysqli_query($conn,"
SELECT * FROM users
WHERE username='$username'
OR login_kontrak='$username'
LIMIT 1
");

if(!$q){
die('Query error: '.mysqli_error($conn));
}

$user = mysqli_fetch_assoc($q);

if($user && password_verify($password,$user['password'])){

$_SESSION['login'] = true;
$_SESSION['role'] = $user['role'];
$_SESSION['user_id'] = $user['id'];
$_SESSION['LAST_ACTIVITY'] = time();

/* CATAT LOGIN */
audit_log($conn,'LOGIN','User login berhasil');

/* REDIRECT ROLE */

if($user['role'] === 'admin'){
header("Location: ../admin/dashboard.php");
exit;
}

elseif($user['role'] === 'karyawan'){
header("Location: ../karyawan/dashboard.php");
exit;
}

else{
header("Location: ../nasabah/dashboard.php");
exit;
}

}else{

$error = 'Username / password salah atau akun nonaktif';

}

}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<title>GLOBAL MOTOR</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-md-4">

<div class="card shadow border-0">

<div class="card-body text-center">

<img src="../assets/logohitam.png"
alt="Global Motor"
style="max-width:180px;"
class="mb-3">

<h4 class="mb-3">MASUK</h4>

<?php if ($error != '') { ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php } ?>

<form method="POST">

<input type="text"
name="username"
class="form-control mb-2"
placeholder="Username / No Kontrak"
required>

<input type="password"
name="password"
class="form-control mb-3"
placeholder="Password"
required>

<button type="submit" class="btn btn-danger w-100">
Login
</button>

</form>

</div>
</div>

</div>
</div>
</div>

</body>
</html>