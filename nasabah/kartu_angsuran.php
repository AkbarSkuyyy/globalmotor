<?php
ob_start();
session_start();

date_default_timezone_set('Asia/Jakarta'); // PERBAIKI ZONA WAKTU

require '../config/security.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'nasabah') {
    header('Location: ../auth/login.php');
    exit;
}

require '../config/database.php';
require '../fpdf/fpdf.php';
require '../phpqrcode/qrlib.php';

$user_id = $_SESSION['user_id'];

/* ================= AMBIL DATA ================= */
$data = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT 
        p.id,
        p.no_kontrak,
        p.tenor,
        p.angsuran,
        k.merk, k.tipe, k.warna
    FROM penjualan p
    JOIN kendaraan k ON p.kendaraan_id = k.id
    JOIN users u ON u.username = p.no_kontrak
    WHERE u.id = '$user_id'
"));

if (!$data) {
    die("Data kredit tidak ditemukan.");
}

$angsuran = mysqli_query($conn,"
    SELECT bulan_ke, jatuh_tempo, jumlah, status
    FROM angsuran
    WHERE penjualan_id = '{$data['id']}'
    ORDER BY bulan_ke ASC
");


/* ================= CLASS PDF ================= */
class PDF extends FPDF {

    function Header(){

        $this->Image('../assets/logohitam.png',10,8,25);

        $this->SetFont('Arial','B',14);
        $this->Cell(0,7,'KARTU ANGSURAN GLOBAL MOTOR',0,1,'C');

        $this->SetFont('Arial','',9);
        $this->Cell(0,5,'Jl. Bakrie Entong Kec. Hanau Kel. Pembuang Hulu | Telp: 085252930293',0,1,'C');

        $this->Ln(5);
        $this->Line(10,32,200,32);
        $this->Ln(8);
    }

    function Footer(){

        $this->SetY(-15);
        $this->SetFont('Arial','I',8);

        $this->Cell(
            0,
            10,
            'Dicetak pada '.date('d-m-Y H:i:s').' WIB - Halaman '.$this->PageNo(),
            0,
            0,
            'C'
        );
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);


/* ================= INFO ================= */

$pdf->Cell(0,6,'No Kontrak : '.$data['no_kontrak'],0,1);
$pdf->Cell(0,6,'Motor      : '.$data['merk'].' '.$data['tipe'].' ('.$data['warna'].')',0,1);
$pdf->Cell(0,6,'Tenor      : '.$data['tenor'].' Bulan',0,1);
$pdf->Cell(0,6,'Angsuran   : Rp '.number_format($data['angsuran'],0,',','.'),0,1);
$pdf->Ln(6);


/* ================= TABEL ================= */

$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(230,230,230);

$pdf->Cell(20,8,'Bulan',1,0,'C',true);
$pdf->Cell(40,8,'Jatuh Tempo',1,0,'C',true);
$pdf->Cell(45,8,'Jumlah',1,0,'C',true);
$pdf->Cell(40,8,'Status',1,1,'C',true);

$pdf->SetFont('Arial','',9);

$total_lunas = 0;

while($a = mysqli_fetch_assoc($angsuran)){

    if($a['status'] == 'SUDAH LUNAS'){
        $pdf->SetTextColor(0,128,0);
        $total_lunas++;
    }
    elseif($a['status'] == 'BELUM LUNAS'){
        $pdf->SetTextColor(200,0,0);
    }
    else{
        $pdf->SetTextColor(0,0,0);
    }

    $pdf->Cell(20,8,$a['bulan_ke'],1,0,'C');
    $pdf->Cell(40,8,date('d-m-Y',strtotime($a['jatuh_tempo'])),1);
    $pdf->Cell(45,8,'Rp '.number_format($a['jumlah'],0,',','.'),1,0,'R');
    $pdf->Cell(40,8,$a['status'],1,1,'C');
}

$pdf->SetTextColor(0,0,0);


/* ================= RINGKASAN ================= */

$pdf->Ln(5);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'Ringkasan Pembayaran',0,1);

$pdf->SetFont('Arial','',9);
$pdf->Cell(0,6,'Total Lunas : '.$total_lunas.' dari '.$data['tenor'].' Bulan',0,1);

$pdf->Ln(8);


/* ================= QR ================= */

$qrText =
"KARTU ANGSURAN GLOBAL MOTOR\n".
"No Kontrak: ".$data['no_kontrak']."\n".
"Tenor: ".$data['tenor']." Bulan\n".
"Dicetak: ".date('d-m-Y H:i:s')." WIB";

$qrFile = '../assets/qrcode_angsuran.png';

QRcode::png($qrText, $qrFile, QR_ECLEVEL_L, 3);

$pdf->Image($qrFile,160,230,30);


/* ================= OUTPUT PDF ================= */
/* D = langsung download (lebih cocok untuk APK) */

$pdf->Output('D','Kartu_Angsuran_'.$data['no_kontrak'].'.pdf');
exit;