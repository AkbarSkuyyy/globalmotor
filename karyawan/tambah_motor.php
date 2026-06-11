<?php
session_start();
require '../config/security.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

$pesan = '';

if (isset($_POST['simpan_motor'])) {
    $merk       = mysqli_real_escape_string($conn, $_POST['merk']);
    $tipe       = mysqli_real_escape_string($conn, $_POST['tipe']);
    $warna      = mysqli_real_escape_string($conn, $_POST['warna']);
    $no_rangka  = mysqli_real_escape_string($conn, $_POST['no_rangka']);
    $no_mesin   = mysqli_real_escape_string($conn, $_POST['no_mesin']);
    $harga_otr  = preg_replace("/[^0-9]/", "", $_POST['harga_otr']);
    $status     = 'READY'; 

    $query = "INSERT INTO motor (merk, tipe, warna, no_rangka, no_mesin, harga_otr, status) 
              VALUES ('$merk', '$tipe', '$warna', '$no_rangka', '$no_mesin', '$harga_otr', '$status')";
              
    if (mysqli_query($conn, $query)) {
        $pesan = '<div class="alert alert-success fw-bold rounded-3 shadow-sm border-0"><i class="fa-solid fa-check-circle me-2"></i> Data motor berhasil ditambahkan ke stok!</div>';
    } else {
        $pesan = '<div class="alert alert-danger fw-bold rounded-3 shadow-sm border-0"><i class="fa-solid fa-triangle-exclamation me-2"></i> Gagal menyimpan: ' . mysqli_error($conn) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Motor - Global Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #334155; }
        h3 { font-family: 'Montserrat', sans-serif; }
        .form-control, .form-select { border-radius: 10px; padding: 12px 15px; font-size: 14px; }
        .form-label { font-weight: 600; font-size: 13px; color: #475569; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container mt-5 mb-5" style="max-width: 800px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark m-0">Input Data Motor</h3>
                <p class="text-secondary m-0">Tambahkan stok kendaraan baru ke sistem.</p>
            </div>
            <a href="dashboard" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i> Kembali
            </a>
        </div>

        <?= $pesan ?>

        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white">
            <form method="POST" action="">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Merek Motor</label>
                        <select name="merk" class="form-select" required>
                            <option value="">-- Pilih Merek --</option>
                            <option value="Honda">Honda</option>
                            <option value="Yamaha">Yamaha</option>
                            <option value="Suzuki">Suzuki</option>
                            <option value="Kawasaki">Kawasaki</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipe/Model Motor</label>
                        <input type="text" name="tipe" class="form-control" placeholder="Contoh: Beat CBS ISS" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Warna</label>
                        <input type="text" name="warna" class="form-control" placeholder="Contoh: Matte Black" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga OTR (Rp)</label>
                        <input type="text" name="harga_otr" id="harga_otr" class="form-control fw-bold text-success" placeholder="Rp 0" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nomor Rangka</label>
                        <input type="text" name="no_rangka" class="form-control text-uppercase" placeholder="17 Digit No Rangka" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nomor Mesin</label>
                        <input type="text" name="no_mesin" class="form-control text-uppercase" placeholder="Masukkan No Mesin" required>
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top text-end">
                        <button type="submit" name="simpan_motor" class="btn btn-primary rounded-pill px-5 fw-bold py-2 shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Data Motor
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-Format Rupiah
        const hargaInput = document.getElementById('harga_otr');
        hargaInput.addEventListener('input', function() {
            let val = this.value.replace(/[^0-9]/g, '');
            this.value = val ? 'Rp ' + val.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        });
    </script>
</body>
</html>