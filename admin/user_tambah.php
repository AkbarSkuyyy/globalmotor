<?php
require '../config/security.php';
if ($_SESSION['role'] !== 'admin') exit;
include '../config/database.php';

$alert = '';
if (isset($_POST['simpan'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    $cek = mysqli_query($conn,"SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        $alert = '<div class="alert alert-danger shadow-sm border-0"><i class="fa-solid fa-circle-exclamation me-2"></i>Username sudah digunakan!</div>';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $login_kontrak = str_replace('-', '', $username);

        mysqli_query($conn,"
            INSERT INTO users (username, login_kontrak, password, role, status, created_at)
            VALUES ('$username', '$login_kontrak', '$hash', '$role', 'AKTIF', NOW())
        ");
        $alert = '<div class="alert alert-success shadow-sm border-0"><i class="fa-solid fa-check-circle me-2"></i>Akun berhasil dibuat!</div>';
    }
}
?>

<div class="container-fluid mt-3 mb-5 px-3" style="max-width: 600px;">
    <div class="d-flex align-items-center mb-4">
        <button type="button" onclick="window.history.back()" class="btn btn-outline-secondary rounded-circle me-3">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
               <div>
            <h3 class="fw-bold text-dark m-0">Tambah User Baru</h3>
            <p class="text-secondary m-0">Buat akses login untuk karyawan atau nasabah baru.</p>
        </div>
    </div>

    <?= $alert ?>

    <div class="card shadow-sm border-0 rounded-4 p-4">
        <form method="POST">
            <div class="mb-3">
                <label class="fw-bold text-secondary mb-1">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control border-start-0" 
                           placeholder="Contoh: karyawan1" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold text-secondary mb-1">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" 
                           placeholder="Minimal 6 karakter" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="fw-bold text-secondary mb-1">Role Akun</label>
                <select name="role" class="form-select border-light-subtle" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="karyawan">Karyawan</option>
                    <option value="nasabah">Nasabah</option>
                </select>
            </div>

            <button name="simpan" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                <i class="fa-solid fa-floppy-disk me-2"></i> Simpan User
            </button>
        </form>
    </div>
</div>