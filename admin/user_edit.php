<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;

include '../config/database.php';

$id = mysqli_real_escape_string($conn, $_GET['id'] ?? '');

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
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $status   = mysqli_real_escape_string($conn, $_POST['status']);

    mysqli_query($conn,"
        UPDATE users SET
        username='$username',
        status='$status'
        WHERE id='$id'
    ");

    echo "<script>
        alert('Data berhasil diperbarui!');
        window.location='dashboard.php?page=user_karyawan';
    </script>";
    exit;
}
?>

<div class="container mt-4 mb-5" style="max-width: 600px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0" style="font-family: 'Montserrat', sans-serif;">Edit Karyawan</h3>
            <p class="text-secondary m-0">Ubah data akun dan status karyawan.</p>
        </div>
        <a href="dashboard.php?page=user_karyawan" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white">
        <form method="POST">
            
            <div class="mb-4">
                <label class="form-label fw-bold small text-secondary">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-secondary"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control border-secondary" 
                           value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small text-secondary">Status Akun</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-secondary"><i class="fa-solid fa-power-off"></i></span>
                    <select name="status" class="form-select border-secondary">
                        <option value="AKTIF" <?= $user['status']=='AKTIF'?'selected':'' ?>>AKTIF</option>
                        <option value="NONAKTIF" <?= $user['status']=='NONAKTIF'?'selected':'' ?>>NONAKTIF</option>
                    </select>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Perubahan
                </button>
            </div>

        </form>
    </div>

</div>

<style>
    .form-control, .form-select {
        border-radius: 10px;
        padding: 12px 15px;
        font-size: 14px;
    }
    .form-control:focus, .form-select:focus { 
        border-color: #3b82f6; 
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25); 
    }
    .input-group-text {
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
        border: 1px solid #dee2e6;
    }
</style>