<?php
require '../config/security.php';
include '../config/database.php';

if ($_SESSION['role'] !== 'admin') {
    exit;
}

$success = '';
$error = '';

if (isset($_POST['simpan'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // cek username
    $cek = mysqli_query($conn,"SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        $error = 'Username sudah digunakan';
    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        // login_kontrak otomatis (hapus strip)
        $login_kontrak = str_replace('-', '', $username);

        mysqli_query($conn,"
            INSERT INTO users
            (username, login_kontrak, password, role, status, created_at)
            VALUES
            ('$username', '$login_kontrak', '$hash', '$role', 'AKTIF', NOW())
        ");

        $success = 'Akun berhasil dibuat';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">

    <h4 class="mb-3">Tambah Akun User</h4>

    <?php if ($success) { ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php } ?>

    <?php if ($error) { ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php } ?>

    <form method="POST">

        <label>Username</label>
        <input type="text" name="username" class="form-control mb-2"
               placeholder="contoh: karyawan1 / GM-TEST-001" required>

        <label>Password</label>
        <input type="text" name="password" class="form-control mb-2"
               placeholder="contoh: 123456" required>

        <label>Role</label>
        <select name="role" class="form-control mb-3" required>
            <option value="">-- Pilih Role --</option>
            <option value="karyawan">Karyawan</option>
            <option value="nasabah">Nasabah</option>
        </select>

        <button name="simpan" class="btn btn-primary w-100">
            💾 Simpan User
        </button>

    </form>

    <a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-3">
        Kembali
    </a>

</div>

</body>
</html>
