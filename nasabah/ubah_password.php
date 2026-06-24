<?php
session_start();
require '../config/security.php';

// Cek akses hanya untuk nasabah
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login');
    exit;
}

include '../config/database.php';
$pesan = '';

if (isset($_POST['ubah_password'])) {
    $user_id = $_SESSION['user_id'];
    $pass_lama = mysqli_real_escape_string($conn, $_POST['pass_lama']);
    $pass_baru = mysqli_real_escape_string($conn, $_POST['pass_baru']);
    $pass_konfirmasi = mysqli_real_escape_string($conn, $_POST['pass_konfirmasi']);

    // Cek password lama di database
    $query = mysqli_query($conn, "SELECT password FROM users WHERE id='$user_id'");
    $user = mysqli_fetch_assoc($query);

    // Proses pencocokan (Verify Hash)
    if (password_verify($pass_lama, $user['password'])) {
        if ($pass_baru === $pass_konfirmasi) {
            // Hash password baru dan update ke database
            $pass_hash = password_hash($pass_baru, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$pass_hash' WHERE id='$user_id'");
            
            $pesan = '<div class="alert alert-success border-0 shadow-sm rounded-4 fw-bold"><i class="bi bi-check-circle-fill me-2"></i> Password berhasil diubah! Gunakan password baru ini untuk login berikutnya.</div>';
        } else {
            $pesan = '<div class="alert alert-danger border-0 shadow-sm rounded-4 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi password baru tidak cocok!</div>';
        }
    } else {
        $pesan = '<div class="alert alert-danger border-0 shadow-sm rounded-4 fw-bold"><i class="bi bi-x-circle-fill me-2"></i> Password lama yang Anda masukkan salah!</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - Nasabah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        body { background: #eef2f7; font-family: 'Segoe UI', sans-serif; }
        .header-top { background: linear-gradient(135deg, #2563eb, #1e40af); color: white; padding: 25px 20px; border-radius: 0 0 25px 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card-form { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); padding: 35px 25px; }
        .form-control { border-radius: 12px; padding: 12px 15px; border: 1px solid #e2e8f0; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body>

    <div class="header-top mb-4 d-flex align-items-center">
        <a href="dashboard" class="text-white text-decoration-none me-3"><i class="bi bi-arrow-left fs-4"></i></a>
        <h5 class="fw-bold mb-0">Ubah Password</h5>
    </div>

    <div class="container mb-5" style="max-width: 500px;">
        
        <?= $pesan ?>
        
        <div class="card card-form bg-white">
            <form method="POST" action="">
                
                <div class="mb-3">
                    <label class="form-label text-secondary fw-semibold small">Password Lama</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-secondary"><i class="bi bi-lock"></i></span>
                        <input type="password" name="pass_lama" class="form-control bg-light border-start-0 ps-0" placeholder="Masukkan password saat ini" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-secondary fw-semibold small">Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-secondary"><i class="bi bi-key"></i></span>
                        <input type="password" name="pass_baru" class="form-control bg-light border-start-0 ps-0" placeholder="Buat password baru" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label text-secondary fw-semibold small">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-secondary"><i class="bi bi-check-circle"></i></span>
                        <input type="password" name="pass_konfirmasi" class="form-control bg-light border-start-0 ps-0" placeholder="Ketik ulang password baru" required>
                    </div>
                </div>
                
                <button type="submit" name="ubah_password" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold mb-3 shadow-sm">
                    Simpan Password
                </button>
                
            </form>
        </div>
    </div>

</body>
</html>