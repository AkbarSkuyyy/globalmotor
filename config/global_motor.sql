-- USERS TABLE (Admin, Karyawan, Nasabah)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  role ENUM('admin','karyawan','nasabah'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CUSTOMER TABLE
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100),
  nik VARCHAR(20),
  alamat TEXT,
  pekerjaan VARCHAR(100),
  no_hp VARCHAR(20)
);

-- KENDARAAN TABLE
CREATE TABLE kendaraan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  merk VARCHAR(50),
  tipe VARCHAR(50),
  warna VARCHAR(30),
  no_polisi VARCHAR(20),
  no_rangka VARCHAR(50),
  no_mesin VARCHAR(50),
  harga_cash INT,
  status ENUM('READY','TERJUAL') DEFAULT 'READY'
);

-- PENJUALAN TABLE
CREATE TABLE penjualan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_kontrak VARCHAR(30),
  customer_id INT,
  kendaraan_id INT,
  jenis ENUM('CASH','KREDIT'),
  dp INT,
  tenor INT,
  angsuran INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ANGSURAN TABLE
CREATE TABLE angsuran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  penjualan_id INT,
  bulan_ke INT,
  jumlah INT,
  status ENUM('BELUM','LUNAS') DEFAULT 'BELUM',
  jatuh_tempo DATE
);

-- PEMBAYARAN TABLE
CREATE TABLE pembayaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  angsuran_id INT,
  bukti VARCHAR(255),
  status ENUM('PENDING','VALID') DEFAULT 'PENDING',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
