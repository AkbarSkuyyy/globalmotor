<?php
// Tidak perlu session_start() karena sudah di dalam dashboard.php

include '../config/database.php';

$pembayaran = mysqli_query($conn,"
    SELECT pb.*, a.bulan_ke, a.jumlah, a.id AS angsuran_id,
           p.no_kontrak,
           np.nama
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id = a.id
    JOIN penjualan p ON a.penjualan_id = p.id
    LEFT JOIN nasabah_profile np ON np.no_kontrak = p.no_kontrak
    WHERE pb.status='PENDING'
    ORDER BY pb.created_at ASC
");

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Styling khusus modal SweetAlert agar gambar bukti terlihat proporsional */
    .swal-image-bukti {
        max-height: 70vh;
        object-fit: contain;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
</style>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex align-items-center mb-4">
        <h4 class="fw-bold m-0"><i class="fa-solid fa-shield-check text-warning me-2"></i>Validasi Pembayaran</h4>
        <span class="badge bg-warning text-dark ms-3 rounded-pill shadow-sm"><?= mysqli_num_rows($pembayaran) ?> Menunggu</span>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">No Kontrak</th>
                            <th>Nasabah</th>
                            <th>Bulan</th>
                            <th>Tagihan</th>
                            <th>Kode Unik</th>
                            <th>Total Transfer</th>
                            <th class="text-center">Bukti</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($pembayaran) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-circle-check fs-2 d-block mb-2 text-success"></i>
                                Tidak ada data pembayaran yang menunggu validasi.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php while ($p = mysqli_fetch_assoc($pembayaran)): 
                            $total = $p['jumlah'] + $p['kode_unik'];
                            $path_bukti = "../assets/bukti/" . $p['bukti'];
                        ?>
                        <tr>
                            <td class="ps-4 font-monospace fw-bold text-secondary"><?= htmlspecialchars($p['no_kontrak']) ?></td>
                            <td class="fw-medium text-dark"><?= htmlspecialchars($p['nama']) ?></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info border border-info rounded-pill px-3">Ke-<?= $p['bulan_ke'] ?></span></td>
                            <td class="text-muted"><?= rupiah($p['jumlah']) ?></td>
                            <td class="text-secondary"><?= $p['kode_unik'] ?></td>
                            <td class="fw-bold text-primary"><?= rupiah($total) ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-light btn-sm rounded-3 border px-3 fw-medium" onclick="lihatBukti('<?= $path_bukti ?>')">
                                    <i class="fa-regular fa-image text-primary me-1"></i> Lihat
                                </button>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group shadow-sm" role="group">
                                    <button type="button" onclick="tolakBayar('<?= $p['id'] ?>', '<?= $p['angsuran_id'] ?>')" class="btn btn-outline-danger btn-sm px-3" title="Tolak Pembayaran">
                                        <i class="fa-solid fa-xmark"></i> Tolak
                                    </button>
                                    <button type="button" onclick="terimaBayar('<?= $p['id'] ?>', '<?= $p['angsuran_id'] ?>')" class="btn btn-success btn-sm px-3 fw-bold" title="Terima & Validasi">
                                        <i class="fa-solid fa-check"></i> Validasi
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // FUNGSI 1: LIHAT BUKTI GAMBAR (POP-UP)
    function lihatBukti(urlGambar) {
        Swal.fire({
            title: 'Bukti Pembayaran',
            imageUrl: urlGambar,
            imageAlt: 'Bukti Transfer',
            customClass: {
                image: 'swal-image-bukti'
            },
            showCloseButton: true,
            showConfirmButton: false,
            width: '600px',
            padding: '1em'
        });
    }

    // FUNGSI 2: TERIMA PEMBAYARAN
    function terimaBayar(idBayar, idAngsuran) {
        Swal.fire({
            title: 'Validasi Pembayaran?',
            text: "Pastikan nominal yang ditransfer sudah sesuai dengan tagihan.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981', // Emerald 500
            cancelButtonColor: '#94a3b8',  // Slate 400
            confirmButtonText: 'Ya, Validasi!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // DIPERBAIKI: Mengarah ke pembayaran_valid
                window.location.href = `dashboard?page=pembayaran_valid&aksi=terima&id=${idBayar}&angsuran=${idAngsuran}`;
            }
        });
    }

    // FUNGSI 3: TOLAK PEMBAYARAN
    function tolakBayar(idBayar, idAngsuran) {
        Swal.fire({
            title: 'Tolak Pembayaran?',
            text: "Nasabah harus mengupload ulang bukti pembayaran jika ditolak.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444', // Red 500
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Tolak!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // DIPERBAIKI: Mengarah ke pembayaran_valid
                window.location.href = `dashboard?page=pembayaran_valid&aksi=tolak&id=${idBayar}&angsuran=${idAngsuran}`;
            }
        });
    }
</script>