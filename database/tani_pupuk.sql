CREATE DATABASE IF NOT EXISTS tani_pupuk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tani_pupuk;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS pembayaran,pemesanan,pupuk,kategori_pupuk,users,petani;
SET FOREIGN_KEY_CHECKS=1;
CREATE TABLE petani(
 id_petani INT AUTO_INCREMENT PRIMARY KEY, nik VARCHAR(25) NOT NULL UNIQUE,
 nama_petani VARCHAR(120) NOT NULL, alamat TEXT NOT NULL, no_hp VARCHAR(25),
 status ENUM('Menunggu','Aktif','Ditolak') DEFAULT 'Menunggu',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE users(
 id_user INT AUTO_INCREMENT PRIMARY KEY,nama_lengkap VARCHAR(120) NOT NULL,
 username VARCHAR(60) NOT NULL UNIQUE,password VARCHAR(255) NOT NULL,
 role ENUM('Admin','Petani','Petugas') NOT NULL,id_petani INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(id_petani) REFERENCES petani(id_petani) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE kategori_pupuk(
 id_kategori INT AUTO_INCREMENT PRIMARY KEY,nama_kategori VARCHAR(100) NOT NULL UNIQUE,keterangan VARCHAR(255)
) ENGINE=InnoDB;
CREATE TABLE pupuk(
 id_pupuk INT AUTO_INCREMENT PRIMARY KEY,id_kategori INT NOT NULL,nama_pupuk VARCHAR(120) NOT NULL,
 harga DECIMAL(12,2) NOT NULL DEFAULT 0,stok INT NOT NULL DEFAULT 0,satuan VARCHAR(20) NOT NULL DEFAULT 'Sak',
 FOREIGN KEY(id_kategori) REFERENCES kategori_pupuk(id_kategori)
) ENGINE=InnoDB;
CREATE TABLE pemesanan(
 id_pemesanan INT AUTO_INCREMENT PRIMARY KEY,kode_pemesanan VARCHAR(40) NOT NULL UNIQUE,
 qr_token VARCHAR(80) NOT NULL UNIQUE,id_petani INT NOT NULL,id_pupuk INT NOT NULL,
 tanggal_pemesanan DATE NOT NULL,jumlah INT NOT NULL,total_harga DECIMAL(12,2) NOT NULL,
 status_pemesanan VARCHAR(40) NOT NULL DEFAULT 'Menunggu Pembayaran',tanggal_ambil DATETIME NULL,
 FOREIGN KEY(id_petani) REFERENCES petani(id_petani),FOREIGN KEY(id_pupuk) REFERENCES pupuk(id_pupuk)
) ENGINE=InnoDB;
CREATE TABLE pembayaran(
 id_pembayaran INT AUTO_INCREMENT PRIMARY KEY,id_pemesanan INT NOT NULL UNIQUE,
 tanggal_bayar DATE NULL,metode_pembayaran VARCHAR(40) NULL,jumlah_bayar DECIMAL(12,2) NOT NULL,
 status_pembayaran ENUM('Belum Dibayar','Lunas') DEFAULT 'Belum Dibayar',
 FOREIGN KEY(id_pemesanan) REFERENCES pemesanan(id_pemesanan) ON DELETE CASCADE
) ENGINE=InnoDB;
INSERT INTO petani(nik,nama_petani,alamat,no_hp,status) VALUES
('7201010101000001','Budi Santoso','Desa Sejahtera','081234560001','Aktif'),
('7201010101000002','Made Putra','Desa Tani Jaya','081234560002','Aktif'),
('7201010101000003','Wayan Adi','Desa Tani Sejahtera','081234560003','Aktif');
INSERT INTO petani(nik,nama_petani,alamat,no_hp,status) VALUES
('7201010101000004','Petani Contoh 4','Desa Tani 4','081234560004','Aktif'),
('7201010101000005','Petani Contoh 5','Desa Tani 5','081234560005','Aktif'),
('7201010101000006','Petani Contoh 6','Desa Tani 6','081234560006','Aktif'),
('7201010101000007','Petani Contoh 7','Desa Tani 7','081234560007','Aktif'),
('7201010101000008','Petani Contoh 8','Desa Tani 8','081234560008','Aktif'),
('7201010101000009','Petani Contoh 9','Desa Tani 9','081234560009','Aktif'),
('7201010101000010','Petani Contoh 10','Desa Tani 10','081234560010','Aktif'),
('7201010101000011','Petani Contoh 11','Desa Tani 11','081234560011','Aktif'),
('7201010101000012','Petani Contoh 12','Desa Tani 12','081234560012','Aktif'),
('7201010101000013','Petani Contoh 13','Desa Tani 13','081234560013','Aktif'),
('7201010101000014','Petani Contoh 14','Desa Tani 14','081234560014','Aktif'),
('7201010101000015','Petani Contoh 15','Desa Tani 15','081234560015','Aktif'),
('7201010101000016','Petani Contoh 16','Desa Tani 16','081234560016','Aktif'),
('7201010101000017','Petani Contoh 17','Desa Tani 17','081234560017','Aktif'),
('7201010101000018','Petani Contoh 18','Desa Tani 18','081234560018','Aktif'),
('7201010101000019','Petani Contoh 19','Desa Tani 19','081234560019','Aktif'),
('7201010101000020','Petani Contoh 20','Desa Tani 20','081234560020','Aktif'),
('7201010101000021','Petani Contoh 21','Desa Tani 21','081234560021','Aktif');
INSERT INTO users(nama_lengkap,username,password,role,id_petani) VALUES
('Administrator TANI PUPUK','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Admin',NULL),
('Petugas Kios','petugas','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Petugas',NULL),
('Budi Santoso','7201010101000001','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Petani',1);
INSERT INTO kategori_pupuk(nama_kategori,keterangan) VALUES
('Pupuk Anorganik','Pupuk berbahan mineral'),('Pupuk Organik','Pupuk dari bahan alami'),('Pupuk Hayati','Pupuk dengan mikroorganisme');
INSERT INTO pupuk(id_kategori,nama_pupuk,harga,stok,satuan) VALUES
(1,'Urea',90000,100,'Sak'),(1,'NPK 15-15-15',100000,80,'Sak'),(1,'ZA (Nitrogen)',95000,60,'Sak'),(2,'Organik',60000,50,'Sak');
