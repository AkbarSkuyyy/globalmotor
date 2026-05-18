<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

$id = $_GET['id'] ?? '';

if(!$id){
    echo "<div class='alert alert-danger'>ID tidak ditemukan</div>";
    exit;
}

$user = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM users WHERE id='$id'
"));

if(!$user){
    echo "<div class='alert alert-danger'>Data tidak ditemukan</div>";
    exit;
}

if($_SERVER['REQUEST_METHOD']=='POST'){

    $username = $_POST['username'];
    $status   = $_POST['status'];

    mysqli_query($conn,"
        UPDATE users SET
        username='$username',
        status='$status'
        WHERE id='$id'
    ");

    echo "<script>
    window.location='dashboard.php?page=user_karyawan';
    </script>";
    exit;
}
?>

<div class="container-fluid px-3 mt-4">

<h4 class="fw-bold mb-3">Edit Data Karyawan</h4>

<div class="card shadow-sm border-0">
<div class="card-body">

<form method="POST">

<div class="mb-3">
<label class="form-label">Username</label>
<input type="text" name="username"
class="form-control"
value="<?= htmlspecialchars($user['username']) ?>"
required>
</div>

<div class="mb-3">
<label class="form-label">Status</label>
<select name="status" class="form-control">
<option value="AKTIF" <?= $user['status']=='AKTIF'?'selected':'' ?>>AKTIF</option>
<option value="NONAKTIF" <?= $user['status']=='NONAKTIF'?'selected':'' ?>>NONAKTIF</option>
</select>
</div>

<button class="btn btn-primary w-100 mb-2">Simpan</button>

<a href="dashboard.php?page=user_karyawan"
class="btn btn-secondary w-100">
Kembali
</a>

</form>

</div>
</div>

</div>