<?php
// admin/pembayaran_manual.php

$script_sweetalert = ''; // Variabel untuk menampung script alert

// ====== PROSES BAYAR MANUAL (SISTEM UANG KURANG & AKUMULASI) ======
if (isset($_POST['bayar_manual'])) {
    $id_angsuran   = (int)$_POST['id_angsuran'];
    // Hapus format Rupiah (titik/Rp) lalu jadikan integer murni
    $nominal_bayar = (int)preg_replace('/[^0-9]/', '', $_POST['nominal_bayar']); 
    $no_kontrak    = mysqli_real_escape_string($conn, $_POST['no_kontrak']);
    $tgl_sekarang  = date('Y-m-d H:i:s');

    // Cek Sisa Tagihan & Uang yang sudah masuk sebelumnya
    $q_cek = mysqli_query($conn, "SELECT jumlah, sisa_tagihan, uang_bayar FROM angsuran WHERE id = '$id_angsuran'");
    $d_cek = mysqli_fetch_assoc($q_cek);
    
    // Logika cerdas: Jika sisa_tagihan NULL (data lama), maka sisa hutang = jumlah tagihan awal
    $sisa_sebelumnya = is_null($d_cek['sisa_tagihan']) ? (int)$d_cek['jumlah'] : (int)$d_cek['sisa_tagihan'];
    $sudah_dibayar   = (int)$d_cek['uang_bayar'];
    
    // Hitung akumulasi kas aktual
    $sisa_baru        = $sisa_sebelumnya - $nominal_bayar;
    $total_bayar_baru = $sudah_dibayar + $nominal_bayar;
    
    if ($sisa_baru <= 0) {
        $sisa_baru   = 0;
        $status_baru = 'LUNAS';
        $teks_alert  = 'Pembayaran Lunas! Status cicilan di akun nasabah otomatis menjadi LUNAS.';
    } else {
        // PERBAIKAN: Status menjadi SEBAGIAN jika bayar kurang
        $status_baru = 'SEBAGIAN'; 
        $teks_alert  = 'Pembayaran Diterima! Uang kurang, sisa hutang otomatis terakumulasi menjadi Rp ' . number_format($sisa_baru, 0, ',', '.');
    }

    // 1. Update angsuran melingkupi uang_bayar dan kurang_bayar agar sinkron dengan struk & laporan
    $update_angsuran = mysqli_query($conn, "UPDATE angsuran SET 
        status       = '$status_baru', 
        sisa_tagihan = '$sisa_baru', 
        uang_bayar   = '$total_bayar_baru', 
        kurang_bayar = '$sisa_baru' 
        WHERE id = '$id_angsuran'");

    if ($update_angsuran) {
        // 2. Catat ke tabel pembayaran (kode_unik = 0 sebagai penanda bayar kasir offline)
        mysqli_query($conn, "INSERT INTO pembayaran (angsuran_id, bukti, status, validated_at, kode_unik) 
                             VALUES ('$id_angsuran', 'PEMBAYARAN_KASIR', 'VALID', '$tgl_sekarang', 0)");

        // 3. Masukkan ke jurnal kas (hanya sebesar uang fisik yang diserahkan hari ini)
        mysqli_query($conn, "INSERT INTO jurnal_kas (tanggal, jenis, sumber, keterangan, jumlah) 
                             VALUES ('$tgl_sekarang', 'MASUK', 'Bayar Manual (Kasir)', '$no_kontrak', '$nominal_bayar')");

        $script_sweetalert = "
            Swal.fire({
                title: 'Pembayaran Berhasil!',
                text: '$teks_alert',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Oke, Mengerti'
            });
        ";
    } else {
        $error_msg = mysqli_error($conn);
        $script_sweetalert = "Swal.fire({ title: 'Oops! Gagal Memproses', text: 'Terjadi kesalahan sistem: $error_msg', icon: 'error', confirmButtonColor: '#ef4444' });";
    }
}

// ====== PENGATURAN PARAMETER URL ======
$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');
$kontrak_pilih = mysqli_real_escape_string($conn, $_GET['kontrak_pilih'] ?? '');
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark m-0">Input Pembayaran Manual</h3>
        <p class="text-secondary m-0">Catat pembayaran angsuran nasabah secara offline/tunai.</p>
    </div>
</div>

<?php if (empty($kontrak_pilih)): ?>

    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4 p-md-5">
            <form method="GET" action="">
                <input type="hidden" name="page" value="pembayaran_manual">
                
                <div class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label class="form-label fw-bold text-secondary">Cari Nama Nasabah atau Nomor Kontrak</label>
                        <input type="text" name="keyword" class="form-control form-control-lg bg-light" placeholder="Contoh ketik: Budi atau GM2026..." value="<?= htmlspecialchars($keyword) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm rounded-3">
                            <i class="fa-solid fa-magnifying-glass me-2"></i> Cari
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($keyword)): ?>
        <?php
        $query_search = mysqli_query($conn, "
            SELECT p.no_kontrak, np.nama as nama_nasabah, k.merk, k.tipe 
            FROM penjualan p
            LEFT JOIN nasabah_profile np ON p.no_kontrak = np.no_kontrak
            LEFT JOIN kendaraan k ON p.kendaraan_id = k.id
            WHERE np.nama LIKE '%$keyword%' OR p.no_kontrak LIKE '%$keyword%'
            ORDER BY np.nama ASC
        ");
        ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="fw-bold m-0 text-dark">Hasil Pencarian: "<?= htmlspecialchars($keyword) ?>"</h5>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($query_search) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="py-3 ps-4">Nama Nasabah</th>
                                    <th class="py-3">No. Kontrak</th>
                                    <th class="py-3">Kendaraan</th>
                                    <th class="py-3 pe-4 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($r = mysqli_fetch_assoc($query_search)): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= $r['nama_nasabah'] ?: '<span class="text-muted fst-italic">Belum ada profil</span>' ?></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $r['no_kontrak'] ?></span></td>
                                    <td class="text-capitalize"><?= $r['merk'] ?> <?= $r['tipe'] ?></td>
                                    <td class="pe-4 text-end">
                                        <a href="dashboard?page=pembayaran_manual&keyword=<?= urlencode($keyword) ?>&kontrak_pilih=<?= urlencode($r['no_kontrak']) ?>" class="btn btn-sm btn-primary fw-bold px-4 rounded-pill shadow-sm">
                                            Pilih <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-5 text-center">
                        <i class="fa-solid fa-folder-open mb-3 fs-1 text-muted opacity-50 d-block"></i>
                        <h6 class="text-muted fw-bold">Nasabah tidak ditemukan</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    
    <?php
    $query_detail = mysqli_query($conn, "
        SELECT p.*, np.nama as nama_nasabah, k.merk, k.tipe 
        FROM penjualan p
        LEFT JOIN nasabah_profile np ON p.no_kontrak = np.no_kontrak
        LEFT JOIN kendaraan k ON p.kendaraan_id = k.id
        WHERE p.no_kontrak = '$kontrak_pilih'
    ");
    $data_penjualan = mysqli_fetch_assoc($query_detail);
    ?>

    <div class="mb-4">
        <a href="dashboard?page=pembayaran_manual&keyword=<?= urlencode($keyword) ?>" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm fw-bold">
            <i class="fa-solid fa-arrow-left me-2"></i> Kembali ke Pencarian
        </a>
    </div>

    <?php if ($data_penjualan): ?>
        
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-primary bg-white">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Nama Nasabah</h6>
                        <h5 class="fw-bold text-dark m-0"><?= $data_penjualan['nama_nasabah'] ?></h5>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">No. Kontrak</h6>
                        <h5 class="fw-bold text-primary m-0"><?= $data_penjualan['no_kontrak'] ?></h5>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Kendaraan</h6>
                        <h5 class="fw-bold text-dark m-0 text-capitalize"><?= $data_penjualan['merk'] ?> <?= $data_penjualan['tipe'] ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-list-check text-primary me-2"></i> Daftar Angsuran Nasabah</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="py-3 ps-4">Bulan Ke</th>
                                <th class="py-3">Jatuh Tempo</th>
                                <th class="py-3">Sisa Hutang (Rp)</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 pe-4 text-center">Aksi / Input Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $id_penjualan = $data_penjualan['id'];
                            $q_angsuran = mysqli_query($conn, "SELECT * FROM angsuran WHERE penjualan_id = '$id_penjualan' ORDER BY bulan_ke ASC");
                            
                            while($row = mysqli_fetch_assoc($q_angsuran)):
                                $sisa_tagihan = is_null($row['sisa_tagihan']) ? $row['jumlah'] : $row['sisa_tagihan'];
                                if ($row['status'] == 'LUNAS') $sisa_tagihan = 0;
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark">Ke-<?= $row['bulan_ke'] ?></td>
                                <td><?= date('d M Y', strtotime($row['jatuh_tempo'])) ?></td>
                                <td>
                                    <?php if($row['status'] == 'LUNAS'): ?>
                                        <span class="text-success fw-bold">Rp 0</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold">Rp <?= number_format($sisa_tagihan, 0, ',', '.') ?></span>
                                        <?php if($sisa_tagihan != $row['jumlah']): ?>
                                            <br><small class="text-muted" style="font-size:10px;">Tagihan Asli: Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['status'] == 'LUNAS'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check me-1"></i> LUNAS</span>
                                    <?php elseif($row['status'] == 'SEBAGIAN'): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-clock me-1"></i> SEBAGIAN</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-xmark me-1"></i> BELUM</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end" style="min-width: 250px;">
                                    
                                    <?php if($row['status'] != 'LUNAS'): ?>
                                        <form method="POST" action="" onsubmit="konfirmasiBayar(event, this, <?= $row['bulan_ke'] ?>);">
                                            <input type="hidden" name="id_angsuran" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="no_kontrak" value="<?= $kontrak_pilih ?>">
                                            <input type="hidden" name="bayar_manual" value="1">
                                            
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light">Rp</span>
                                                <input type="text" name="nominal_bayar" class="form-control format-rupiah fw-bold text-success" placeholder="Ketik nominal..." required>
                                                <button type="submit" class="btn btn-primary fw-bold px-3">
                                                    <i class="fa-solid fa-money-bill-wave me-1"></i> Bayar
                                                </button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light text-muted px-3 rounded-pill fw-bold w-100" disabled>
                                            <i class="fa-solid fa-check-double me-1"></i> Selesai
                                        </button>
                                    <?php endif; ?>

                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
    <?php if(!empty($script_sweetalert)){ echo $script_sweetalert; } ?>

    // Format Rupiah Otomatis Kasir
    document.querySelectorAll('.format-rupiah').forEach(function(input) {
        input.addEventListener('input', function() {
            let angka = this.value.replace(/[^0-9]/g, '');
            if (angka === '') {
                this.value = '';
                return;
            }
            this.value = angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });

    // Konfirmasi Bayar Kasir
    function konfirmasiBayar(event, form, bulan_ke) {
        event.preventDefault();
        let inputNominal = form.querySelector('.format-rupiah').value;
        if (!inputNominal) {
            Swal.fire('Peringatan', 'Harap masukkan nominal uang yang dibayar.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Kasir',
            html: "Terima pembayaran sebesar <b>Rp " + inputNominal + "</b> untuk angsuran bulan ke-" + bulan_ke + "?<br><br><small>Sistem akan otomatis menghitung sisa hutang (jika kurang).</small>",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#ef4444',
            confirmButtonText: '<i class="fa-solid fa-check"></i> Ya, Proses',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...', text: 'Menyimpan data ke Jurnal Kas', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }
                });
                form.submit();
            }
        });
    }
</script>