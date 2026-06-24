<?php
// admin/pembayaran_valid.php
// File ini di-include dari dashboard.php

$id_bayar = $_GET['id'] ?? '';
$id_angsuran = $_GET['angsuran'] ?? '';
$aksi = $_GET['aksi'] ?? 'terima'; 

if(empty($id_bayar) || empty($id_angsuran)) {
    echo "<script>window.location='dashboard?page=pembayaran';</script>";
    exit;
}

/* =========================================
   Ambil data pembayaran untuk nominal kas
=========================================*/
$data = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT pb.*, a.jumlah, a.sisa_tagihan, p.no_kontrak
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id = a.id
    JOIN penjualan p ON a.penjualan_id = p.id
    WHERE pb.id='$id_bayar'
"));

if(!$data){
    echo "<script>window.location='dashboard?page=pembayaran';</script>";
    exit;
}

// LOGIKA CERDAS UANG KURANG (Menghitung Total Transfer Aktual)
$sisa_aktual = is_null($data['sisa_tagihan']) ? $data['jumlah'] : $data['sisa_tagihan'];
$total_transfer = $sisa_aktual + $data['kode_unik'];
$no_kontrak     = $data['no_kontrak'];

if ($aksi === 'terima') {
    /* ========================================================
       AKSI 1: VALIDASI & MASUK JURNAL KAS
       ======================================================== */
    // 1. Update tabel pembayaran jadi VALID
    mysqli_query($conn,"UPDATE pembayaran SET status='VALID', validated_at=NOW() WHERE id='$id_bayar'");
    
    // 2. Update tabel angsuran jadi LUNAS dan sisa_tagihan = 0
    mysqli_query($conn,"UPDATE angsuran SET status='LUNAS', sisa_tagihan='0' WHERE id='$id_angsuran'");

    // 3. Masukkan uang ke Jurnal Kas
    mysqli_query($conn,"
        INSERT INTO jurnal_kas (tanggal, jenis, sumber, keterangan, jumlah)
        VALUES (NOW(), 'MASUK', 'Angsuran Transfer', '$no_kontrak', '$total_transfer')
    ");

    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Berhasil Divalidasi!',
                text: 'Pembayaran disetujui, tagihan lunas, dan dana sebesar Rp ".number_format($total_transfer,0,',','.')." masuk ke Jurnal Kas.',
                icon: 'success',
                confirmButtonColor: '#10b981',
                allowOutsideClick: false
            }).then(() => {
                window.location.href = 'dashboard?page=pembayaran';
            });
        });
    </script>
    ";
    
} elseif ($aksi === 'tolak') {
    /* ========================================================
       AKSI 2: TOLAK PEMBAYARAN (TIDAK MASUK KAS)
       ======================================================== */
    // 1. Update tabel pembayaran jadi DITOLAK
    mysqli_query($conn,"UPDATE pembayaran SET status='DITOLAK' WHERE id='$id_bayar'");
    
    // 2. Kembalikan tabel angsuran jadi BELUM
    mysqli_query($conn,"UPDATE angsuran SET status='BELUM' WHERE id='$id_angsuran'");

    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Pembayaran Ditolak!',
                text: 'Data pembayaran telah ditolak. Nasabah dapat melakukan upload ulang.',
                icon: 'error',
                confirmButtonColor: '#ef4444',
                allowOutsideClick: false
            }).then(() => {
                window.location.href = 'dashboard?page=pembayaran';
            });
        });
    </script>
    ";
}
?>