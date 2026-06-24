<?php
// admin/rekening_koran.php 
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'karyawan'])) {
    die("Akses Ditolak.");
}

$no_kontrak = $_GET['no_kontrak'] ?? '';
if(!$no_kontrak) die("Nomor Kontrak tidak ditemukan.");

// Ambil Data Utama
$q = mysqli_query($conn, "
    SELECT p.*, np.nama, np.alamat, np.no_hp, k.merk, k.tipe, k.no_rangka
    FROM penjualan p
    JOIN nasabah_profile np ON p.no_kontrak = np.no_kontrak
    JOIN kendaraan k ON p.kendaraan_id = k.id
    WHERE p.no_kontrak = '$no_kontrak'
");
$data = mysqli_fetch_assoc($q);
if(!$data) die("Data kontrak tidak valid.");

// Ambil Detail Angsuran
$q_angsuran = mysqli_query($conn, "
    SELECT a.*, 
           (SELECT validated_at FROM pembayaran pb WHERE pb.angsuran_id = a.id AND pb.status='VALID' ORDER BY pb.id DESC LIMIT 1) as tgl_bayar
    FROM angsuran a
    WHERE a.penjualan_id = '{$data['id']}'
    ORDER BY a.bulan_ke ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Rekening Koran - <?= $no_kontrak ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #333; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; }
        .info-table { width: 100%; margin-bottom: 25px; font-size: 13px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 8px 6px; text-align: center; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right !important; }
        
        /* Warna Status */
        .status-lunas { color: #15803d; font-weight: bold; }
        .status-sebagian { color: #d97706; font-weight: bold; } /* Oranye untuk bayar kurang */
        .status-pending { color: #2563eb; font-weight: bold; }
        .status-belum { color: #dc2626; font-weight: bold; }
        
        @media print { 
            body { margin: 0; padding: 20px; } 
            @page { size: A4 portrait; margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2>REKENING KORAN NASABAH</h2>
        <p style="margin: 5px 0 0 0; font-size: 14px; color: #555;">GLOBAL MOTOR - Laporan Histori Pembayaran Angsuran</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>No. Kontrak</strong></td>
            <td width="35%">: <?= htmlspecialchars($data['no_kontrak']) ?></td>
            <td width="15%"><strong>Kendaraan</strong></td>
            <td width="35%">: <?= htmlspecialchars($data['merk'] . ' ' . $data['tipe']) ?></td>
        </tr>
        <tr>
            <td><strong>Nama Nasabah</strong></td>
            <td>: <?= htmlspecialchars($data['nama']) ?></td>
            <td><strong>No. Rangka</strong></td>
            <td>: <?= htmlspecialchars($data['no_rangka']) ?></td>
        </tr>
        <tr>
            <td><strong>No. Handphone</strong></td>
            <td>: <?= htmlspecialchars($data['no_hp']) ?></td>
            <td><strong>Tgl Pengambilan</strong></td>
            <td>: <?= !empty($data['tgl_pengambilan']) ? date('d/m/Y', strtotime($data['tgl_pengambilan'])) : '-' ?></td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td>: <?= htmlspecialchars($data['alamat']) ?></td>
            <td><strong>Uang Muka (DP)</strong></td>
            <td>: Rp <?= number_format($data['dp'],0,',','.') ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">Bln</th>
                <th width="14%">Jatuh Tempo</th>
                <th width="16%">Tagihan Asli (Rp)</th>
                <th width="16%">Telah Bayar (Rp)</th>
                <th width="16%">Kurang Bayar (Rp)</th>
                <th width="17%">Tgl Bayar Terakhir</th>
                <th width="16%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while($a = mysqli_fetch_assoc($q_angsuran)): 
                
                $tagihan_asli = (int)$a['jumlah'];
                $telah_dibayar = (int)$a['uang_bayar'];
                $kurang_bayar = (int)$a['kurang_bayar'];

                // ==========================================
                // TRIK BACKWARD COMPATIBILITY DATA LAMA
                // ==========================================
                if ($a['status'] === 'LUNAS' && $telah_dibayar === 0) {
                    $telah_dibayar = $tagihan_asli;
                    $kurang_bayar  = 0;
                } elseif ($a['status'] === 'BELUM' && $kurang_bayar === 0) {
                    $kurang_bayar  = $tagihan_asli;
                    $telah_dibayar = 0;
                }
            ?>
            <tr>
                <td><?= $a['bulan_ke'] ?></td>
                <td><?= date('d/m/Y', strtotime($a['jatuh_tempo'])) ?></td>
                <td class="text-right"><?= number_format($tagihan_asli, 0, ',', '.') ?></td>
                <td class="text-right" style="color:#15803d; font-weight:500;">
                    <?= number_format($telah_dibayar, 0, ',', '.') ?>
                </td>
                <td class="text-right" style="color: <?= $kurang_bayar > 0 ? '#dc2626' : '#333' ?>; font-weight: <?= $kurang_bayar > 0 ? 'bold' : 'normal' ?>;">
                    <?= number_format($kurang_bayar, 0, ',', '.') ?>
                </td>
                <td><?= $a['tgl_bayar'] ? date('d/m/Y H:i', strtotime($a['tgl_bayar'])) : '-' ?></td>
                <td>
                    <?php 
                        if ($a['status'] === 'LUNAS') {
                            echo '<span class="status-lunas">LUNAS</span>';
                        } elseif ($a['status'] === 'SEBAGIAN') {
                            echo '<span class="status-sebagian">BAYAR SEBAGIAN</span>';
                        } elseif ($a['status'] === 'PENDING') {
                            echo '<span class="status-pending">PROSES VALIDASI</span>';
                        } else {
                            echo '<span class="status-belum">BELUM BAYAR</span>';
                        }
                    ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: right; font-size: 11px; color: #666;">
        <p>Dokumen ini dicetak otomatis oleh sistem.<br>Waktu cetak: <?= date('d M Y, H:i') ?> WIB</p>
    </div>

</body>
</html>