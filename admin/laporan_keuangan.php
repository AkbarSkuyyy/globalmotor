<?php
if (!in_array($_SESSION['role'], ['admin','karyawan'])) exit;
include '../config/database.php';

/*
|--------------------------------------------------------------------------
| FILTER TANGGAL
|--------------------------------------------------------------------------
| Sinkron dengan Dashboard (pakai created_at)
*/

$tgl_awal  = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

if ($tgl_awal && $tgl_akhir) {

    $where = "WHERE pb.status='VALID'
              AND pb.created_at BETWEEN
              '$tgl_awal 00:00:00'
              AND '$tgl_akhir 23:59:59'";

} else {

    $where = "WHERE pb.status='VALID'
              AND DATE_FORMAT(pb.created_at,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m')";
}

$q = mysqli_query($conn,"
    SELECT 
        pb.created_at AS tanggal_transaksi,
        p.no_kontrak,
        COALESCE(np.nama, '-') AS nama,
        a.jumlah,
        pb.kode_unik
    FROM pembayaran pb
    JOIN angsuran a ON pb.angsuran_id=a.id
    JOIN penjualan p ON a.penjualan_id=p.id
    LEFT JOIN nasabah_profile np ON np.no_kontrak=p.no_kontrak
    $where
    ORDER BY pb.created_at DESC
");

$total = 0;
?>

<h4>Laporan Keuangan</h4>

<form class="row g-2 mb-3">
    <input type="hidden" name="page" value="laporan_keuangan">
    <div class="col-md-3">
        <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>" class="form-control">
    </div>
    <div class="col-md-3">
        <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>" class="form-control">
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100">Filter</button>
    </div>
</form>

<div class="card shadow-sm border-0">
<div class="card-body">

<div class="table-responsive">
<table class="table table-bordered table-sm align-middle bg-white">
<thead class="table-light">
<tr>
<th>No</th>
<th>Tanggal</th>
<th>No Kontrak</th>
<th>Nama</th>
<th>Jumlah</th>
</tr>
</thead>

<tbody>

<?php
$no=1;
while($r=mysqli_fetch_assoc($q)){

    $jumlah = $r['jumlah'] + $r['kode_unik'];
    $total += $jumlah;
?>

<tr>
<td><?= $no++ ?></td>
<td><?= date('d-m-Y H:i', strtotime($r['tanggal_transaksi'])) ?></td>
<td><?= $r['no_kontrak'] ?></td>
<td><?= $r['nama'] ?></td>
<td>Rp <?= number_format($jumlah,0,',','.') ?></td>
</tr>

<?php } ?>

<?php if($no==1){ ?>
<tr>
<td colspan="5" class="text-center text-muted">
Belum ada transaksi pada periode ini
</td>
</tr>
<?php } ?>

</tbody>

<tfoot>
<tr class="table-success">
<th colspan="4">TOTAL</th>
<th>Rp <?= number_format($total,0,',','.') ?></th>
</tr>
</tfoot>

</table>
</div>

</div>
</div>

<br>

<a target="_blank"
href="laporan_keuangan_pdf.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>"
class="btn btn-danger">📄 Download PDF</a>

<a target="_blank"
href="laporan_keuangan_excel.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>"
class="btn btn-success">📊 Export Excel</a>