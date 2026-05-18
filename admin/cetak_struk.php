<?php
require '../config/security.php';
if (!in_array($_SESSION['role'], ['admin','karyawan'])) exit;

include '../config/database.php';

$data = mysqli_query($conn,"
SELECT 
pb.id,
pb.created_at,
p.no_kontrak,
COALESCE(np.nama,'-') nama,
a.bulan_ke,
a.jumlah,
pb.kode_unik
FROM pembayaran pb
JOIN angsuran a ON pb.angsuran_id=a.id
JOIN penjualan p ON a.penjualan_id=p.id
LEFT JOIN nasabah_profile np ON np.no_kontrak=p.no_kontrak
WHERE pb.status='VALID'
ORDER BY pb.created_at DESC
");
?>

<h4>Cetak Struk Pembayaran</h4>

<div class="table-responsive">

<table class="table table-bordered table-sm bg-white">

<thead>
<tr>
<th>No</th>
<th>Tanggal</th>
<th>Nama</th>
<th>No Kontrak</th>
<th>Bulan</th>
<th>Total</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php $no=1; while($r=mysqli_fetch_assoc($data)){ 
$total = $r['jumlah'] + $r['kode_unik'];
?>

<tr>

<td><?= $no++ ?></td>

<td><?= date('d-m-Y H:i',strtotime($r['created_at'])) ?> WIB</td>

<td><?= $r['nama'] ?></td>

<td><?= $r['no_kontrak'] ?></td>

<td><?= $r['bulan_ke'] ?></td>

<td><b>Rp <?= number_format($total,0,',','.') ?></b></td>

<td>

<a 
href="struk_print.php?id=<?= $r['id'] ?>"
class="btn btn-success btn-sm"
onclick="printStruk(this.href); return false;">

🖨 Cetak

</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>


<script>

function printStruk(url){

var iframe = document.createElement('iframe');

iframe.style.display = "none";
iframe.src = url;

document.body.appendChild(iframe);

}

</script>