<?php
session_start();
require '../config/security.php';

// Pastikan hanya admin yang bisa akses
if ($_SESSION['role'] !== 'admin') {
    exit('Akses ditolak.');
}

include '../config/database.php';
date_default_timezone_set('Asia/Jakarta');

// Tangkap filter tanggal
$tgl_awal  = isset($_GET['tgl_awal']) ? mysqli_real_escape_string($conn, $_GET['tgl_awal']) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? mysqli_real_escape_string($conn, $_GET['tgl_akhir']) : '';

// Atur Header agar dibaca sebagai Excel oleh Browser
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Keuangan_GlobalMotor_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Menyusun Query bersinkronisasi dengan halaman web utama
$where_clause = " WHERE p.status='VALID' ";
if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $where_clause .= " AND p.created_at >= '$tgl_awal 00:00:00' AND p.created_at <= '$tgl_akhir 23:59:59' ";
}

$query = mysqli_query($conn, "
    SELECT 
        p.id AS id_bayar,
        p.created_at AS tgl_bayar,
        p.kode_unik,
        pj.no_kontrak,
        COALESCE(np.nama, '-') AS nama_nasabah,
        a.bulan_ke,
        a.jumlah AS angsuran_pokok
    FROM pembayaran p
    JOIN angsuran a ON p.angsuran_id = a.id
    JOIN penjualan pj ON a.penjualan_id = pj.id
    LEFT JOIN nasabah_profile np ON pj.no_kontrak = np.no_kontrak
    $where_clause
    ORDER BY p.id DESC
");
?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <style>
        .text-string { mso-number-format:"\@"; } /* Memaksa Excel membaca kolom sebagai Text */
        .currency { mso-number-format:"Rp\#\,\#\#0"; text-align:right; } /* Format Rupiah asli di Excel */
        .header-tabel { background-color: #1e293b; color: #ffffff; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th colspan="7" style="font-size: 16px; font-weight: bold; text-align: center; height: 35px;">LAPORAN KEUANGAN GLOBAL MOTOR</th>
            </tr>
            <?php if (!empty($tgl_awal)) { ?>
                <tr>
                    <th colspan="7" style="text-align: center; height: 30px;">
                        Periode: <?php echo date('d-m-Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d-m-Y', strtotime($tgl_akhir)); ?>
                    </th>
                </tr>
            <?php } ?>
            <tr class="header-tabel">
                <th>No</th>
                <th>Tanggal Pembayaran</th>
                <th>No Kontrak</th>
                <th>Nama Nasabah</th>
                <th>Angsuran Bulan Ke</th>
                <th>Nominal Pokok</th>
                <th>Total (+ Kode Unik)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_pendapatan = 0;
            while ($row = mysqli_fetch_assoc($query)) {
                $total_transfer = $row['angsuran_pokok'] + $row['kode_unik'];
                $total_pendapatan += $total_transfer;
            ?>
                <tr>
                    <td style="text-align: center;"><?php echo $no++; ?></td>
                    <td style="text-align: center;"><?php echo date('d-m-Y H:i', strtotime($row['tgl_bayar'])); ?> WIB</td>
                    <td class="text-string"><?php echo htmlspecialchars($row['no_kontrak']); ?></td> 
                    <td><?php echo htmlspecialchars($row['nama_nasabah']); ?></td>
                    <td style="text-align: center;"><?php echo $row['bulan_ke']; ?></td>
                    <td class="currency"><?php echo $row['angsuran_pokok']; ?></td>
                    <td class="currency"><?php echo $total_transfer; ?></td>
                </tr>
            <?php } ?>
            <tr>
                <th colspan="6" style="text-align: right; font-weight: bold;">TOTAL PENDAPATAN KAS :</th>
                <th class="currency" style="font-weight: bold;"><?php echo $total_pendapatan; ?></th>
            </tr>
        </tbody>
    </table>
</body>
</html>