<?php
// File ini akan di-include di dashboard.php, jadi tidak perlu session_start() lagi.

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_data_lama'])) {
    
    // 1. DATA NASABAH & KONTRAK LAMA
    $no_kontrak_lama = mysqli_real_escape_string($conn, $_POST['no_kontrak_lama']);
    $nama            = mysqli_real_escape_string($conn, $_POST['nama']);
    $no_hp           = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $alamat          = mysqli_real_escape_string($conn, $_POST['alamat']);
    
    // 2. DATA MOTOR
    $merk = mysqli_real_escape_string($conn, $_POST['merk']);
    $tipe = mysqli_real_escape_string($conn, $_POST['tipe']);
    
    // 3. DATA KREDIT LAMA
    $angsuran       = preg_replace('/[^0-9]/', '', $_POST['angsuran']); // Hilangkan Rp
    $tenor_total    = (int)$_POST['tenor_total'];
    $tenor_lunas    = (int)$_POST['tenor_lunas'];
    $tgl_mulai      = mysqli_real_escape_string($conn, $_POST['tgl_mulai']);
    
    // Validasi Cepat: Tenor lunas tidak boleh lebih dari total tenor
    if($tenor_lunas > $tenor_total) {
        echo "<script>alert('Error: Tenor lunas tidak boleh lebih besar dari total tenor!'); history.back();</script>";
        exit;
    }

    /* ================= Cek Apakah No Kontrak Sudah Ada ================= */
    $cek_kontrak = mysqli_query($conn, "SELECT no_kontrak FROM penjualan WHERE no_kontrak = '$no_kontrak_lama'");
    if(mysqli_num_rows($cek_kontrak) > 0) {
        echo "<script>alert('Nomor Kontrak Lama sudah terdaftar di sistem!'); history.back();</script>";
        exit;
    }

    /* ================= SIMPAN KENDARAAN ================= */
    mysqli_query($conn, "
        INSERT INTO kendaraan (merk, tipe, warna, no_rangka, no_mesin, harga_cash, status)
        VALUES ('$merk', '$tipe', '-', '-', '-', '0', 'TERJUAL')
    ");
    $kendaraan_id = mysqli_insert_id($conn);

    /* ================= BUAT AKUN NASABAH ================= */
    $nama_clean = strtolower(preg_replace("/[^a-z]/", "", $nama));
    $nama_part  = substr($nama_clean, 0, 4);
    $random     = rand(1000, 9999);
    $password_plain = ucfirst($nama_part) . $random;
    $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

    mysqli_query($conn, "
        INSERT INTO users (username, password, role, status, created_at)
        VALUES ('$no_kontrak_lama', '$password_hashed', 'nasabah', 'AKTIF', NOW())
    ");

    /* ================= PROFIL NASABAH ================= */
    mysqli_query($conn, "
        INSERT INTO nasabah_profile (no_kontrak, nama, alamat, no_hp)
        VALUES ('$no_kontrak_lama', '$nama', '$alamat', '$no_hp')
    ");

    /* ================= PENJUALAN KREDIT ================= */
    mysqli_query($conn, "
        INSERT INTO penjualan (no_kontrak, kendaraan_id, dp, tenor, angsuran, created_at)
        VALUES ('$no_kontrak_lama', '$kendaraan_id', '0', '$tenor_total', '$angsuran', NOW())
    ");
    $penjualan_id = mysqli_insert_id($conn);

    /* ================= GENERATE ANGSURAN & SINKRONISASI ================= */
    for ($i = 0; $i < $tenor_total; $i++) {
        $tempo = date('Y-m-d', strtotime("+$i month", strtotime($tgl_mulai)));
        // Jika bulan ke-N lebih kecil atau sama dengan tenor lunas, statusnya LUNAS
        $status_angsuran = ($i < $tenor_lunas) ? 'LUNAS' : 'BELUM';
        
        $bulan_ke = $i + 1;
        mysqli_query($conn, "
            INSERT INTO angsuran (penjualan_id, bulan_ke, jumlah, jatuh_tempo, status)
            VALUES ('$penjualan_id', '$bulan_ke', '$angsuran', '$tempo', '$status_angsuran')
        ");
    }

    // Variabel untuk trigger SweetAlert2 di bagian bawah HTML
    $show_alert = true;
    $alert_user = $no_kontrak_lama;
    $alert_pass = $password_plain;
}
?>

<style>
    .form-control, .form-select, textarea { border-radius: 8px; padding: 10px 15px; font-size: 14px; border: 1px solid #cbd5e1; }
    .form-control:focus, .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 0.25rem rgba(59,130,246,0.25); }
    .section-title { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 700; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="bg-dark text-white p-3 mb-4 shadow-sm rounded-3">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-clock-rotate-left me-2"></i> Mode Migrasi: Kontrak Lama</h5>
        <a href="dashboard" class="btn btn-outline-light btn-sm rounded-pill px-3">Batal & Kembali</a>
    </div>
</div>

<div style="max-width: 900px; margin: 0 auto;">
    
    <div class="alert alert-warning border-warning border-start border-4 rounded-3 mb-4 shadow-sm">
        <i class="fa-solid fa-triangle-exclamation text-warning fs-4 me-2 align-middle"></i>
        <strong>Perhatian:</strong> Halaman ini khusus untuk memasukkan nasabah lama. Sistem akan menganggap bulan yang "Sudah Lunas" sebagai tagihan yang telah diselesaikan.
    </div>

    <form method="POST" action="" class="card shadow-sm border-0 rounded-4 p-4 bg-white needs-validation" novalidate>
        
        <div class="section-title text-dark">
            <i class="fa-solid fa-address-book me-2 text-primary"></i> Data Identitas & Nomor Kontrak
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Nomor Kontrak Lama (Manual)</label>
                <input type="text" name="no_kontrak_lama" class="form-control bg-light text-uppercase fw-bold text-danger" placeholder="Ketik nomor kontrak lama..." required>
                <small class="text-muted" style="font-size:11px;">*Ini akan menjadi Username login nasabah.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Nama Nasabah</label>
                <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Nomor WhatsApp/HP</label>
                <input type="text" name="no_hp" class="form-control" placeholder="08..." required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Alamat Saat Ini</label>
                <textarea name="alamat" class="form-control" rows="1" placeholder="Alamat lengkap" required></textarea>
            </div>
        </div>

        <div class="section-title text-dark mt-2">
            <i class="fa-solid fa-motorcycle me-2 text-primary"></i> Unit Kendaraan
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Merek Motor</label>
                <input type="text" name="merk" class="form-control" placeholder="Contoh: Honda" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">Tipe Motor</label>
                <input type="text" name="tipe" class="form-control" placeholder="Contoh: Beat CBS" required>
            </div>
        </div>

        <div class="section-title text-dark mt-2">
            <i class="fa-solid fa-scale-balanced me-2 text-primary"></i> Sinkronisasi Tagihan & Angsuran
        </div>
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-secondary">Angsuran /Bulan (Rp)</label>
                <input type="text" name="angsuran" id="angsuran" class="form-control fw-bold text-success" placeholder="Rp 0" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small text-secondary">Total Tenor (Bulan)</label>
                <div class="input-group">
                    <input type="number" name="tenor_total" class="form-control" placeholder="Cth: 12" required>
                    <span class="input-group-text bg-light text-secondary">Bln</span>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small text-secondary">Tenor Sudah Lunas</label>
                <div class="input-group">
                    <input type="number" name="tenor_lunas" class="form-control text-primary fw-bold" placeholder="Cth: 5" required>
                    <span class="input-group-text bg-primary text-white border-primary">Bln</span>
                </div>
            </div>
            <div class="col-md-12 mt-3">
                <label class="form-label fw-bold small text-secondary">Tanggal Angsuran Pertama Dulu Dibayar</label>
                <input type="date" name="tgl_mulai" class="form-control" required>
                <small class="text-muted" style="font-size:11px;">*Sistem akan menghitung jadwal bulan selanjutnya secara otomatis berdasarkan tanggal ini.</small>
            </div>
        </div>

        <div class="mt-5 text-end border-top pt-4">
            <button type="submit" name="simpan_data_lama" class="btn btn-primary rounded-pill px-5 fw-bold py-2 shadow-sm">
                <i class="fa-solid fa-server me-2"></i> Sinkronkan Data Kontrak Lama
            </button>
        </div>
        
    </form>
</div>

<script>
    // Validasi form bawaan bootstrap
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

    // Format Rupiah
    const angsuranInput = document.getElementById('angsuran');
    angsuranInput.addEventListener('input', function() {
        let angka = this.value.replace(/[^0-9]/g, '');
        if (angka === '') {
            this.value = '';
            return;
        }
        this.value = 'Rp ' + angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    });
</script>

<?php 
// JIKA DATA BERHASIL DISIMPAN, MUNCULKAN POP-UP SWEETALERT INI
if (isset($show_alert) && $show_alert): 
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil Disinkronkan!',
            html: `
                <div style="text-align: center; font-size: 14px;">
                    <p class="mb-3 text-secondary">Data kontrak lama dan jadwal angsuran berhasil dimasukkan ke sistem. Silakan salin data akun Nasabah di bawah ini:</p>
                    <div style="background-color: #f1f5f9; padding: 15px; border-radius: 12px; border: 1px dashed #94a3b8; text-align: left; margin: 0 auto; max-width: 300px;">
                        <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold;">Username / No. Kontrak:</span><br>
                        <span style="font-size: 18px; font-weight: bold; color: #0f172a; font-family: monospace;" id="copyUser"><?= htmlspecialchars($alert_user) ?></span>
                        <hr style="margin: 10px 0; border-color: #cbd5e1;">
                        <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold;">Password Nasabah:</span><br>
                        <span style="font-size: 18px; font-weight: bold; color: #ef4444; font-family: monospace;" id="copyPass"><?= htmlspecialchars($alert_pass) ?></span>
                    </div>
                    <p class="mt-3 mb-0" style="font-size: 12px; color: #64748b;">Kirim screenshot ini ke Nasabah via WhatsApp.</p>
                </div>
            `,
            confirmButtonText: 'Selesai & Tutup',
            confirmButtonColor: '#3b82f6',
            allowOutsideClick: false // Mencegah pop-up tertutup kalau user klik area luar
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect ke Dashboard SETELAH tombol selesai diklik
                window.location = 'dashboard';
            }
        });
    });
</script>
<?php endif; ?>