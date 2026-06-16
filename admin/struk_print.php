<?php
include '../config/database.php';

date_default_timezone_set('Asia/Jakarta');

$id = $_GET['id'];

$data = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT 
pb.created_at,
p.no_kontrak,
p.tenor,
np.nama,
a.bulan_ke,
a.jatuh_tempo,
a.jumlah,
pb.kode_unik
FROM pembayaran pb
JOIN angsuran a ON pb.angsuran_id=a.id
JOIN penjualan p ON a.penjualan_id=p.id
LEFT JOIN nasabah_profile np ON np.no_kontrak=p.no_kontrak
WHERE pb.id='$id'
"));

$total = $data['jumlah'] + $data['kode_unik'];

$tanggal_bayar = date('d-m-Y',strtotime($data['created_at']));
$jam_bayar = date('H:i',strtotime($data['created_at'])) . " WIB";

$angsuran_ke = str_pad($data['bulan_ke'],3,"0",STR_PAD_LEFT);
$tenor = str_pad($data['tenor'],3,"0",STR_PAD_LEFT);
?>

<html>
<head>

<style>

body{
font-family: monospace;
background:#eee;
margin:0;
padding:0;
}

.struk{
width:58mm;
margin:auto;
background:white;
padding:3mm;
}

.print-area{
width:48mm;
margin:auto;
text-align:center;
}

.logo{
width:24mm;
margin-bottom:2mm;
}

hr{
border:0;
border-top:1px dashed #000;
margin:2mm 0;
}

table{
width:100%;
font-size:12px;
border-collapse:collapse;
}

td{
padding:1px 0;
}

.label{
width:45%;
text-align:left;
}

.titik{
width:5%;
text-align:center;
}

.value{
width:50%;
text-align:left;
}

.total-box{
text-align:center;
font-weight:bold;
font-size:14px;
margin-top:4px;
}

.pesan{
font-size:11px;
margin-top:4px;
}

@media print{

body{
background:white;
}

.struk{
width:58mm;
padding:0;
}

.print-area{
width:48mm;
}

@page{
size:58mm auto;
margin:0;
}

}

</style>

</head>

<body onload="window.print()">

<div class="struk">

<div class="print-area">

<img src="../assets/logohitam.png" class="logo">

<div><b>GLOBAL MOTOR</b></div>
<div>Jl.Bakrie Entong, Kec.Hanau, Kel.Pembuang Hulu 1</div>

<hr>

<table>

<tr>
<td class="label">No Kontrak</td>
<td class="titik">:</td>
<td class="value"><?= $data['no_kontrak'] ?></td>
</tr>

<tr>
<td class="label">Nama</td>
<td class="titik">:</td>
<td class="value"><?= $data['nama'] ?></td>
</tr>

<tr>
<td class="label">Angsuran ke</td>
<td class="titik">:</td>
<td class="value"><?= $angsuran_ke ?>/<?= $tenor ?></td>
</tr>

<tr>
<td class="label">Tanggal Bayar</td>
<td class="titik">:</td>
<td class="value"><?= $tanggal_bayar ?></td>
</tr>

<tr>
<td class="label">Waktu</td>
<td class="titik">:</td>
<td class="value"><?= $jam_bayar ?></td>
</tr>

<tr>
<td class="label">Jatuh Tempo</td>
<td class="titik">:</td>
<td class="value"><?= date('d-m-Y',strtotime($data['jatuh_tempo'])) ?></td>
</tr>

</table>

<hr>

<table>

<tr>
<td class="label">Jumlah Tagihan</td>
<td class="titik">:</td>
<td class="value">Rp <?= number_format($data['jumlah'],0,',','.') ?></td>
</tr>

</table>

<hr>

<div class="total-box">
TOTAL DIBAYAR<br>
Rp <?= number_format($total,0,',','.') ?>
</div>

<hr>

<div class="pesan">
Simpan tanda terima ini,<br>
sebagai bukti transaksi sah
</div>

<br>

<div class="pesan">
Informasi lebih lanjut<br>
085252930293
</div>

<div>Terima Kasih</div>

</div>

</div>

</body>
</html>