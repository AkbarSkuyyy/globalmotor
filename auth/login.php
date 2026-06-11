<?php
// auth/login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Keamanan: Jika user sudah login tapi memaksa akses halaman ini, kembalikan ke folder utama
if (isset($_SESSION['user_id'])) {
    header("Location: ../"); // Mengarah ke root folder (otomatis memicu index.php secara diam-diam)
    exit();
}

// Tetap gunakan .php untuk include/require internal script server
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

    if($user && password_verify($password, $user['password'])){
        // Set Sesi Login
        $_SESSION['login'] = true;
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['LAST_ACTIVITY'] = time();

        /* CATAT LOG */
        audit_log($conn,'LOGIN','User login berhasil');

        /* REDIRECT DENGAN URL BERSIH (TANPA .php) */
        if($user['role'] === 'admin'){
            header("Location: ../admin/dashboard");
            exit;
        }
        elseif($user['role'] === 'karyawan'){
            header("Location: ../karyawan/dashboard");
            exit;
        }
        else{
            header("Location: ../nasabah/dashboard");
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Global Motor App v2.1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;      
            justify-content: center;  
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                        url('https://images.unsplash.com/photo-1558981403-c5f9899a28bc?q=80&w=2070');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px; 
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px; 
        }
        .form-control {
            border-radius: 1rem;
            padding: 0.8rem 1.2rem;
            background: #f1f5f9;
            border: none;
        }
        .btn-login {
            background: #2563eb;
            color: white;
            border-radius: 1rem;
            padding: 0.8rem;
            font-weight: 600;
        }
        .btn-login:hover { background: #1d4ed8; color: white; }
        .version-tag { color: #94a3b8; font-size: 0.7rem; letter-spacing: 1px; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <img src="../assets/logohitam.png" alt="Global Motor" style="max-width:120px;">
            <h4 class="mt-3 fw-bold">Selamat Datang</h4>
        </div>

        <?php if ($error != '') { ?>
            <div class="alert alert-danger rounded-4 small">
                <i class="bi bi-exclamation-circle-fill"></i> <?= $error ?>
            </div>
        <?php } ?>

        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username / No Kontrak" required autofocus autocomplete="username">
            </div>
            <div class="mb-4">
                <input type="password" name="password" class="form-control" placeholder="Password" required autocomplete="current-password">
            </div>
            
            <button type="submit" name="login_btn" class="btn btn-login w-100 shadow-sm">Login</button>
        </form>

        <div class="version-tag text-center mt-4">
            GLOBAL MOTOR APP V.2.1
        </div>
    </div>

    <script>
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            // Fetch ke save_location tanpa ekstensi .php jika file tersebut juga ingin diakses via URL bersih
            fetch("../nasabah/save_location", {
                method: "POST",
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: "lat=" + position.coords.latitude +
                      "&lng=" + position.coords.longitude
            }).catch(err => console.log(err));
        }, function(error) {
            console.log("Geolocation error: " + error.message);
        });
    }
    </script>
    
</body>
</html>