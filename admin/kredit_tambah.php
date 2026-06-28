<?php
// admin/kredit_tambah.php (Di-include dari dashboard.php)

// Tidak perlu session_start() karena sudah di dashboard.php
// PHPMailer telah dihapus sepenuhnya

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_kredit'])){

    /* ================= NASABAH ================= */
    $nama        = mysqli_real_escape_string($conn, $_POST['nama']);
    $alamat      = mysqli_real_escape_string($conn, $_POST['alamat']);
    $rt_rw       = mysqli_real_escape_string($conn, $_POST['rt_rw']);
    $kelurahan   = mysqli_real_escape_string($conn, $_POST['kelurahan']);
    $kecamatan   = mysqli_real_escape_string($conn, $_POST['kecamatan']);
    $no_hp       = mysqli_real_escape_string($conn, $_POST['no_hp']); // Email diganti No HP
    $jk          = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $pekerjaan   = mysqli_real_escape_string($conn, $_POST['pekerjaan']);

    /* ================= MOTOR ================= */
    $merk       = mysqli_real_escape_string($conn, $_POST['merk']);
    $tipe       = mysqli_real_escape_string($conn, $_POST['tipe']);
    $warna      = mysqli_real_escape_string($conn, $_POST['warna']);
    $no_rangka  = !empty($_POST['no_rangka']) ? mysqli_real_escape_string($conn, $_POST['no_rangka']) : '-';
    $no_mesin   = !empty($_POST['no_mesin']) ? mysqli_real_escape_string($conn, $_POST['no_mesin']) : '-';
    $harga_otr  = preg_replace('/[^0-9]/', '', $_POST['harga_otr']);

    /* ================= KREDIT & TANGGAL PENGAMBILAN ================= */
    $dp               = preg_replace('/[^0-9]/', '', $_POST['dp']);
    $tenor            = (int)$_POST['tenor'];
    $angsuran         = preg_replace('/[^0-9]/', '', $_POST['angsuran']);
    $tgl_pengambilan  = mysqli_real_escape_string($conn, $_POST['tgl_pengambilan']);
    $jatuh_tempo      = mysqli_real_escape_string($conn, $_POST['jatuh_tempo']);

    /* ================= GENERATE NO KONTRAK ================= */
    $kode_dealer  = "GM";
    $kode_wilayah = "706201"; // Kode wilayah baku
    $prefix       = $kode_dealer . $kode_wilayah; // Hasil: GM706201

    // Cari nomor kontrak terakhir di database yang berawalan 'GM706201'
    $q_kontrak = mysqli_query($conn, "SELECT no_kontrak FROM penjualan WHERE no_kontrak LIKE '$prefix%' ORDER BY id DESC LIMIT 1");
    $row_kontrak = mysqli_fetch_assoc($q_kontrak);

    if ($row_kontrak) {
        // Ambil 5 digit angka terakhir dari kontrak sebelumnya
        $urutan_lama = (int)substr($row_kontrak['no_kontrak'], -5);
        $urutan_baru = $urutan_lama + 1;
    } else {
        // Jika belum ada nasabah sama sekali di database, mulai dari 871
        $urutan_baru = 871;
    }

    // Gabungkan kembali: GM + 706201 + 00871 (5 digit otomatis dengan angka 0 di depan)
    $no_kontrak = $prefix . str_pad($urutan_baru, 5, "0", STR_PAD_LEFT);

    /* ================= SIMPAN KENDARAAN ================= */
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

    // PERBAIKAN: Menambahkan kolom no_hp ke tabel users
    mysqli_query($conn,"
        INSERT INTO users (username, password, role, status, created_at, no_hp)
        VALUES ('$no_kontrak', '$password_hashed', 'nasabah', 'AKTIF', NOW(), '$no_hp')
    ");

    /* ================= PROFIL NASABAH (Tanpa Email) ================= */
    mysqli_query($conn,"
        INSERT INTO nasabah_profile (no_kontrak,nama,alamat,rt_rw,kelurahan,kecamatan,no_hp,jenis_kelamin,pekerjaan)
        VALUES ('$no_kontrak','$nama','$alamat','$rt_rw','$kelurahan','$kecamatan','$no_hp','$jk','$pekerjaan')
    ");

    /* ================= DATA PENJUALAN (Dengan Tgl Pengambilan) ================= */
    mysqli_query($conn,"
        INSERT INTO penjualan (no_kontrak, kendaraan_id, dp, tenor, angsuran, tgl_pengambilan, created_at)
        VALUES ('$no_kontrak', '$kendaraan_id', '$dp', '$tenor', '$angsuran', '$tgl_pengambilan', NOW())
    ");
    $penjualan_id = mysqli_insert_id($conn);

    /* ================= GENERATE ANGSURAN ================= */
    for($i = 0; $i < $tenor; $i++){
        $tempo = date('Y-m-d', strtotime("+$i month", strtotime($jatuh_tempo)));
        // sisa_tagihan diisi nilai yang sama dengan angsuran
        mysqli_query($conn,"
            INSERT INTO angsuran (penjualan_id, bulan_ke, jumlah, sisa_tagihan, jatuh_tempo, status)
            VALUES ('$penjualan_id', '".($i+1)."', '$angsuran', '$angsuran', '$tempo', 'BELUM')
        ");
    }

    $show_alert = true;
    $alert_user = $no_kontrak;
    $alert_pass = $password_plain;
}
?>

<div class="container-fluid mt-4 mb-5" style="max-width: 1000px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Tambah Kontrak Kredit Baru</h3>
            <p class="text-secondary m-0">Lengkapi data nasabah, unit, dan rincian angsuran.</p>
        </div>
        <a href="dashboard" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-arrow-left me-2"></i>Kembali
        </a>
    </div>

    <form method="POST" action="" class="needs-validation" novalidate>
        <div class="row g-4">
            
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-4 h-100 p-4 bg-white">
                    <h6 class="mb-3 fw-bold text-primary border-bottom pb-2"><i class="fa-solid fa-address-card me-2"></i> Data Nasabah</h6>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Sesuai KTP" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Nomor WhatsApp/HP</label>
                            <input type="tel" inputmode="numeric" name="no_hp" class="form-control" placeholder="08..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Pekerjaan</label>
                        <input type="text" name="pekerjaan" class="form-control" placeholder="Pekerjaan saat ini" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Nama Jalan, Blok, dsb." required></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">RT/RW</label>
                            <input type="text" name="rt_rw" class="form-control" placeholder="001/002" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Kelurahan</label>
                            <input type="text" name="kelurahan" class="form-control" placeholder="Desa/Kel." required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Kecamatan</label>
                            <input type="text" name="kecamatan" class="form-control" placeholder="Kecamatan" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-4 h-100 p-4 bg-white">
                    <h6 class="mb-3 fw-bold text-primary border-bottom pb-2"><i class="fa-solid fa-motorcycle me-2"></i> Data Motor & Pengambilan</h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-danger">Tanggal Pengambilan Unit</label>
                        <input type="date" name="tgl_pengambilan" class="form-control fw-bold border-danger text-danger" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Merek Motor</label>
                            <input type="text" name="merk" class="form-control" placeholder="Honda, Yamaha, dll" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Tipe/Model</label>
                            <input type="text" name="tipe" class="form-control" placeholder="Contoh: Beat CBS" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Warna</label>
                        <input type="text" name="warna" class="form-control" placeholder="Warna kendaraan" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor Rangka <small class="text-muted fw-normal">(Opsional)</small></label>
                        <input type="text" name="no_rangka" class="form-control text-uppercase font-monospace" placeholder="Boleh dikosongkan sementara">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nomor Mesin <small class="text-muted fw-normal">(Opsional)</small></label>
                        <input type="text" name="no_mesin" class="form-control text-uppercase font-monospace" placeholder="Boleh dikosongkan sementara">
                    </div>

                    <div class="mb-3 mt-auto">
                        <label class="form-label fw-bold small text-secondary">Harga OTR (Rp)</label>
                        <input type="text" name="harga_otr" id="otr" class="form-control fw-bold text-success" placeholder="Rp 0" required>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <h6 class="mb-3 fw-bold text-primary border-bottom pb-2"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Rincian Kredit</h6>
                    
                    <div class="row g-4 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-secondary">Uang Muka (DP)</label>
                            <input type="text" name="dp" id="dp" class="form-control fw-bold" placeholder="Rp 0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-secondary">Tenor (Bulan)</label>
                            <input type="number" name="tenor" class="form-control" placeholder="Cth: 12" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-secondary">Jumlah Angsuran/Bulan</label>
                            <input type="text" name="angsuran" id="angsuran" class="form-control fw-bold text-primary" placeholder="Rp 0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-secondary">Mulai Jatuh Tempo</label>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Auto-Format Rupiah
    function formatRupiah(input) {
        input.addEventListener('input', function() {
            let angka = this.value.replace(/[^0-9]/g, '');
            if (angka === '') { this.value = ''; return; }
            this.value = 'Rp ' + angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    }
    formatRupiah(document.getElementById('otr'));
    formatRupiah(document.getElementById('dp'));
    formatRupiah(document.getElementById('angsuran'));

    // Validasi form bootstrap
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
    })()
</script>

<?php if (isset($show_alert) && $show_alert): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'success',
            title: 'Kontrak Dibuat!',
            html: `
                <div style="text-align: center; font-size: 14px;">
                    <p class="mb-3 text-secondary">Data nasabah dan jadwal angsuran berhasil dimasukkan. Salin data akun di bawah ini:</p>
                    <div style="background-color: #f1f5f9; padding: 15px; border-radius: 12px; border: 1px dashed #94a3b8; text-align: left; max-width: 300px; margin: 0 auto;">
                        <span style="font-size: 12px; font-weight: bold; color:#64748b; text-transform:uppercase;">Username / No. Kontrak:</span><br>
                        <span style="font-size: 18px; font-weight: bold; color: #0f172a; font-family: monospace;"><?= htmlspecialchars($alert_user) ?></span>
                        <hr style="margin: 10px 0; border-color: #cbd5e1;">
                        <span style="font-size: 12px; font-weight: bold; color:#64748b; text-transform:uppercase;">Password Nasabah:</span><br>
                        <span style="font-size: 18px; font-weight: bold; color: #ef4444; font-family: monospace;"><?= htmlspecialchars($alert_pass) ?></span>
                    </div>
                    <p class="mt-3 mb-0" style="font-size: 12px; color: #64748b;">Kirim screenshot ini ke WhatsApp Nasabah.</p>
                </div>
            `,
            confirmButtonText: 'Selesai & Tutup',
            confirmButtonColor: '#3b82f6',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'dashboard';
            }
        });
    });
</script>
<?php endif; ?>