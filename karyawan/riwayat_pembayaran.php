<?php
session_start();
require '../config/security.php';

if ($_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/database.php';

$no_kontrak = $_SESSION['username'] ?? null;

if (!$no_kontrak) {
    $uid = $_SESSION['user_id'];
    $u = mysqli_fetch_assoc(mysqli_query($conn,"SELECT username FROM users WHERE id='$uid'"));
    $no_kontrak = $u['username'];
}

$data = mysqli_query($conn, "
    SELECT 
        pb.id,
        a.bulan_ke,
        a.jumlah AS jumlah_angsuran,
        pb.kode_unik,
        pb.status,
        pb.created_at AS waktu_upload,
        pb.validated_at
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id = a.id
    JOIN penjualan p ON a.penjualan_id = p.id
    WHERE p.no_kontrak = '$no_kontrak'
    ORDER BY pb.created_at DESC
");

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Pembayaran</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4 mb-5">
    <h4 class="mb-3 fw-bold">Riwayat Pembayaran</h4>

    <div class="table-responsive bg-white shadow-sm rounded-3">
        <table class="table table-bordered table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Bulan Ke</th>
                    <th>Tagihan</th>
                    <th>Kode Unik</th>
                    <th>Total Transfer</th>
                    <th>Status</th>
                    <th>Upload</th>
                    <th>Validasi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($data && mysqli_num_rows($data) > 0) {
                $no = 1;
                while($r = mysqli_fetch_assoc($data)){
                    $total = $r['jumlah_angsuran'] + $r['kode_unik'];
            ?>
                <tr class="<?php echo $r['status'] == 'VALID' ? 'table-success' : 'table-warning'; ?>">
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($r['bulan_ke']); ?></td>
                    <td><?php echo rupiah($r['jumlah_angsuran']); ?></td>
                    <td><?php echo htmlspecialchars($r['kode_unik']); ?></td>
                    <td class="fw-bold"><?php echo rupiah($total); ?></td>
                    <td>
                        <?php if($r['status'] == 'VALID'){ ?>
                            <span class="badge bg-success">VALID</span>
                        <?php } else { ?>
                            <span class="badge bg-warning text-dark">MENUNGGU</span>
                        <?php } ?>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($r['waktu_upload'])); ?></td>
                    <td><?php echo $r['validated_at'] ? date('d/m/Y H:i', strtotime($r['validated_at'])) : '-'; ?></td>
                </tr>
            <?php 
                } 
            } else {
                echo '<tr><td colspan="8" class="text-center text-muted p-4">Belum ada riwayat pembayaran yang diupload.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
    
    <a href="dashboard.php" class="btn btn-secondary mt-3 rounded-pill px-4">Kembali</a>
</div>

</body>
</html>