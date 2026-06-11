<?php
session_start();
require '../config/security.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

// Proteksi Gerbang Karyawan
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'karyawan') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_kredit'])){

    /* ================= NASABAH ================= */
    $nama        = mysqli_real_escape_string($conn, $_POST['nama']);
    $alamat      = mysqli_real_escape_string($conn, $_POST['alamat']);
    $rt_rw       = mysqli_real_escape_string($conn, $_POST['rt_rw']);
    $kelurahan   = mysqli_real_escape_string($conn, $_POST['kelurahan']);
    $kecamatan   = mysqli_real_escape_string($conn, $_POST['kecamatan']);
    $no_hp       = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $jk          = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $pekerjaan   = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $email       = mysqli_real_escape_string($conn, $_POST['email']);

    /* ================= MOTOR (INPUT BARU SESUAI ADMIN) ================= */
    $merk       = mysqli_real_escape_string($conn, $_POST['merk']);
    $tipe       = mysqli_real_escape_string($conn, $_POST['tipe']);
    $warna      = mysqli_real_escape_string($conn, $_POST['warna']);
    $no_rangka  = mysqli_real_escape_string($conn, $_POST['no_rangka']);
    $no_mesin   = mysqli_real_escape_string($conn, $_POST['no_mesin']);
    $harga_otr  = preg_replace('/[^0-9]/', '', $_POST['harga_otr']);

    /* ================= KREDIT ================= */
    $dp          = preg_replace('/[^0-9]/', '', $_POST['dp']);
    $tenor       = (int)$_POST['tenor'];
    $angsuran    = preg_replace('/[^0-9]/', '', $_POST['angsuran']);
    $jatuh_tempo = mysqli_real_escape_string($conn, $_POST['jatuh_tempo']);

    /* =========================
    GENERATE NO KONTRAK
    FORMAT : GMYYYYMMDD001
    ========================= */
    $tanggal = date('Ymd');
    $q = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM penjualan WHERE DATE(created_at)=CURDATE()"));
    $urutan = $q['total'] + 1;
    $no_kontrak = "GM".$tanggal.str_pad($urutan, 3, "0", STR_PAD_LEFT);

    /* ================= SIMPAN MOTOR (Tabel: kendaraan) ================= */
    mysqli_query($conn,"
        INSERT INTO kendaraan (merk,tipe,warna,no_rangka,no_mesin,harga_cash,status)
        VALUES ('$merk','$tipe','$warna','$no_rangka','$no_mesin','$harga_otr','TERJUAL')
    ");
    $kendaraan_id = mysqli_insert_id($conn);

    /* ================= BUAT AKUN NASABAH ================= */
    $nama_clean = strtolower(preg_replace("/[^a-z]/","",$nama));
    $nama_part  = substr($nama_clean, 0, 4);
    $random     = rand(1000, 9999);
    $password_plain = ucfirst($nama_part).$random;
    $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

    mysqli_query($conn,"
        INSERT INTO users (username,password,role,status,created_at)
        VALUES ('$no_kontrak','$password_hashed','nasabah','AKTIF',NOW())
    ");

    /* ================= PROFIL NASABAH ================= */
    mysqli_query($conn,"
        INSERT INTO nasabah_profile (no_kontrak,nama,alamat,rt_rw,kelurahan,kecamatan,no_hp,jenis_kelamin,pekerjaan,email)
        VALUES ('$no_kontrak','$nama','$alamat','$rt_rw','$kelurahan','$kecamatan','$no_hp','$jk','$pekerjaan','$email')
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
                    <tr><td>Nama</td><td><b>$nama</b></td></tr>
                    <tr><td>No Kontrak</td><td><b>$no_kontrak</b></td></tr>
                    <tr><td>Username</td><td><b>$no_kontrak</b></td></tr>
                    <tr><td>Password</td><td><b>$password_plain</b></td></tr>
                </table>
                <br>
                <div style='background:#eef4ff;padding:10px;border-radius:6px'>
                    Silakan login ke sistem nasabah GLOBAL MOTOR menggunakan akun di atas.
                </div>
                <br>
                <center style='font-size:12px;color:#777'>
                    GLOBAL MOTOR<br>Simpan email ini sebagai informasi akun anda.
                </center>
            </div>
        </div>
        ";
        $mail->send();
    } catch (Exception $e) {
        // Abaikan jika email gagal, data tetap tersimpan
    }

    /* ================= DATA PENJUALAN ================= */
    mysqli_query($conn,"
        INSERT INTO penjualan (no_kontrak,kendaraan_id,dp,tenor,angsuran,created_at)
        VALUES ('$no_kontrak','$kendaraan_id','$dp','$tenor','$angsuran',NOW())
    ");
    $penjualan_id = mysqli_insert_id($conn);

    /* ================= GENERATE ANGSURAN ================= */
    for($i = 0; $i < $tenor; $i++){
        $tempo = date('Y-m-d', strtotime("+$i month", strtotime($jatuh_tempo)));
        mysqli_query($conn,"
            INSERT INTO angsuran (penjualan_id,bulan_ke,jumlah,jatuh_tempo,status)
            VALUES ('$penjualan_id','".($i+1)."','$angsuran','$tempo','BELUM')
        ");
    }

    echo "<script>
        alert('Kredit berhasil ditambahkan! Nomor Kontrak: $no_kontrak');
        window.location='dashboard'; // Kembali ke dashboard karyawan
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kredit - Global Motor</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #334155; }
        h3, h4, h5, h6 { font-family: 'Montserrat', sans-serif; }
        .form-control, .form-select, textarea { border-radius: 8px; padding: 10px 15px; font-size: 14px; }
        .form-control:focus, .form-select:focus, textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25); }
        .section-title { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 700; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
        .form-label { font-weight: 600; font-size: 13px; color: #475569; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5" style="max-width: 1000px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark m-0">Tambah Kontrak Kredit</h3>
                <p class="text-secondary m-0">Lengkapi data nasabah, motor, dan rincian angsuran.</p>
            </div>
            <a href="dashboard" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i>Kembali
            </a>
        </div>

        <form method="POST" action="" class="needs-validation" novalidate>
            
            <div class="row g-4">
                
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 rounded-4 h-100 p-4 bg-white">
                        <div class="section-title">
                            <i class="fa-solid fa-address-card me-2 text-primary"></i> Data Nasabah
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" placeholder="Sesuai KTP" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor HP</label>
                                <input type="number" name="no_hp" class="form-control" placeholder="08..." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Aktif</label>
                                <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="">-- Pilih Jenis Kelamin --</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pekerjaan</label>
                            <input type="text" name="pekerjaan" class="form-control" placeholder="Pekerjaan saat ini" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Nama Jalan, Blok, dsb." required></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">RT/RW</label>
                                <input type="text" name="rt_rw" class="form-control" placeholder="001/002" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kelurahan</label>
                                <input type="text" name="kelurahan" class="form-control" placeholder="Desa/Kel." required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kecamatan</label>
                                <input type="text" name="kecamatan" class="form-control" placeholder="Kecamatan" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 rounded-4 h-100 p-4 bg-white">
                        <div class="section-title">
                            <i class="fa-solid fa-motorcycle me-2 text-primary"></i> Data Motor (Baru)
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Merek Motor</label>
                                <input type="text" name="merk" class="form-control" placeholder="Honda, Yamaha, dll" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe/Model</label>
                                <input type="text" name="tipe" class="form-control" placeholder="Contoh: Beat CBS" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Warna</label>
                            <input type="text" name="warna" class="form-control" placeholder="Warna kendaraan" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nomor Rangka</label>
                            <input type="text" name="no_rangka" class="form-control text-uppercase" placeholder="17 Digit No. Rangka" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nomor Mesin</label>
                            <input type="text" name="no_mesin" class="form-control text-uppercase" placeholder="No. Mesin" required>
                        </div>

                        <div class="mb-3 mt-auto">
                            <label class="form-label">Harga OTR (Rp)</label>
                            <input type="text" name="harga_otr" id="otr" class="form-control fw-bold text-success" placeholder="Rp 0" required>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                        <div class="section-title">
                            <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i> Rincian Kredit
                        </div>
                        
                        <div class="row g-4 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Uang Muka (DP)</label>
                                <input type="text" name="dp" id="dp" class="form-control fw-bold" placeholder="Rp 0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tenor (Bulan)</label>
                                <input type="number" name="tenor" class="form-control" placeholder="Cth: 12" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jumlah Angsuran/Bulan</label>
                                <input type="text" name="angsuran" id="angsuran" class="form-control fw-bold text-primary" placeholder="Rp 0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Mulai Jatuh Tempo</label>
                                <input type="date" name="jatuh_tempo" class="form-control" required>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" name="simpan_kredit" class="btn btn-primary rounded-pill px-5 fw-bold py-2 shadow-sm">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Kontrak Kredit
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>

    <script>
        // Navigasi input menggunakan tombol Enter
        document.querySelectorAll('input, select, textarea').forEach((field, index, fields) => {
            field.addEventListener('keydown', function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    let nextField = fields[index + 1];
                    if (nextField) { nextField.focus(); }
                }
            });
        });

        // Validasi Form Bootstrap
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

        // Auto-Format Rupiah
        function formatRupiah(input) {
            input.addEventListener('input', function() {
                let angka = this.value.replace(/[^0-9]/g, '');
                if (angka === '') {
                    this.value = '';
                    return;
                }
                this.value = 'Rp ' + angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            });
        }

        formatRupiah(document.getElementById('otr'));
        formatRupiah(document.getElementById('dp'));
        formatRupiah(document.getElementById('angsuran'));
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>