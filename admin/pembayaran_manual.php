<?php
// Pastikan variabel $conn sudah ada karena file ini di-include dari dashboard.php

$script_sweetalert = ''; // Variabel untuk menampung script alert

// ====== PROSES BAYAR MANUAL ======
if (isset($_POST['bayar_manual'])) {
    $id_angsuran  = (int)$_POST['id_angsuran'];
    $jumlah       = (int)$_POST['jumlah'];
    $no_kontrak   = mysqli_real_escape_string($conn, $_POST['no_kontrak']);
    $tgl_sekarang = date('Y-m-d H:i:s');

    // 1. Update status angsuran menjadi LUNAS (Ini yang akan dilihat Nasabah di akun mereka)
    $update_angsuran = mysqli_query($conn, "UPDATE angsuran SET status = 'LUNAS' WHERE id = '$id_angsuran'");

    if ($update_angsuran) {
        // 2. Catat ke tabel pembayaran (sebagai bukti historis sistem)
        mysqli_query($conn, "INSERT INTO pembayaran (angsuran_id, bukti, status, validated_at, kode_unik) 
                             VALUES ('$id_angsuran', 'MANUAL/TUNAI', 'VALID', '$tgl_sekarang', 0)");

        // 3. Masukkan ke jurnal kas (agar total pemasukan bertambah)
        mysqli_query($conn, "INSERT INTO jurnal_kas (tanggal, jenis, sumber, keterangan, jumlah) 
                             VALUES ('$tgl_sekarang', 'MASUK', 'Angsuran (Tunai)', '$no_kontrak', '$jumlah')");

        // Trigger Alert Sukses
        $script_sweetalert = "
            Swal.fire({
                title: 'Pembayaran Berhasil!',
                text: 'Status cicilan di akun nasabah otomatis menjadi LUNAS.',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Oke, Mengerti'
            });
        ";
    } else {
        // Trigger Alert Gagal
        $error_msg = mysqli_error($conn);
        $script_sweetalert = "
            Swal.fire({
                title: 'Oops! Gagal Memproses',
                text: 'Terjadi kesalahan sistem: $error_msg',
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
        ";
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
        // Mencari nasabah berdasarkan NAMA atau NO KONTRAK
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
                        <p class="text-muted small m-0">Coba gunakan kata kunci lain (nama atau nomor kontrak yang lebih spesifik).</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    
    <?php
    // Ambil data detail penjualan untuk kontrak yang dipilih
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
                        <h5 class="fw-bold text-dark m-0"><?= $data_penjualan['nama_nasabah'] ?: 'Tidak ada di profil' ?></h5>
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
                                <th class="py-3">Nominal (Rp)</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 pe-4 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $id_penjualan = $data_penjualan['id'];
                            $q_angsuran = mysqli_query($conn, "SELECT * FROM angsuran WHERE penjualan_id = '$id_penjualan' ORDER BY bulan_ke ASC");
                            
                            while($row = mysqli_fetch_assoc($q_angsuran)):
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark">Ke-<?= $row['bulan_ke'] ?></td>
                                <td><?= date('d M Y', strtotime($row['jatuh_tempo'])) ?></td>
                                <td class="fw-semibold text-primary">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if($row['status'] == 'LUNAS'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check me-1"></i> LUNAS</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-xmark me-1"></i> BELUM</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if($row['status'] == 'BELUM'): ?>
                                        <form method="POST" action="" onsubmit="konfirmasiBayar(event, this, <?= $row['bulan_ke'] ?>);">
                                            <input type="hidden" name="id_angsuran" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="jumlah" value="<?= $row['jumlah'] ?>">
                                            <input type="hidden" name="no_kontrak" value="<?= $kontrak_pilih ?>">
                                            <input type="hidden" name="bayar_manual" value="1">
                                            
                                            <button type="submit" class="btn btn-sm btn-primary fw-bold px-3 rounded-pill shadow-sm">
                                                <i class="fa-solid fa-money-bill-wave me-1"></i> Bayar Tunai
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light text-muted px-3 rounded-pill fw-bold" disabled>
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

    <?php else: ?>
        <div class="alert alert-danger border-0 shadow-sm fw-bold rounded-4 p-4 text-center">
            Terjadi Kesalahan: Kontrak tidak valid.
        </div>
    <?php endif; ?>

<?php endif; ?>

<script>
    // 1. Eksekusi Script Alert Sukses/Gagal dari PHP (Jika ada post submit)
    <?php if(!empty($script_sweetalert)){ echo $script_sweetalert; } ?>

    // 2. Fungsi SweetAlert untuk Konfirmasi Sebelum Membayar
    function konfirmasiBayar(event, form, bulan_ke) {
        // Mencegah form langsung tersubmit
        event.preventDefault();

        Swal.fire({
            title: 'Konfirmasi Pembayaran',
            text: "Terima pembayaran tunai untuk angsuran bulan ke-" + bulan_ke + "? Status akan langsung LUNAS.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#ef4444',
            confirmButtonText: '<i class="fa-solid fa-check"></i> Ya, Proses',
            cancelButtonText: 'Batal'
        }).then((result) => {
            // Jika admin klik "Ya, Proses"
            if (result.isConfirmed) {
                // Tampilkan loading sebelum form tersubmit
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Menyimpan data pembayaran ke sistem',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });
                
                // Submit form
                form.submit();
            }
        });
    }
</script>