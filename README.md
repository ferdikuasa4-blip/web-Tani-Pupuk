# TANI PUPUK - Aplikasi UTS Pengembangan Web IF501

Aplikasi pengelolaan dan pemesanan pupuk berbasis PHP Native, PDO, MySQL, dan Bootstrap 5.3. Tampilan hijau, sidebar berdasarkan peran, halaman admin/petani/petugas, serta kartu produk dipertahankan.

## Fitur utama
- CRUD Petani, Pupuk, dan Kategori Pupuk.
- Relasi enam tabel: `petani`, `users`, `kategori_pupuk`, `pupuk`, `pemesanan`, dan `pembayaran`.
- Pencarian dan pagination 10 data per halaman pada daftar petani, pupuk, dan pesanan.
- Partial template untuk head, topbar, sidebar, navigasi publik, dan footer di `app/views/partials/`.
- Login berbasis `password_hash()` / `password_verify()` dengan migrasi otomatis untuk password lama berbentuk teks biasa.
- Gambar produk memakai berkas yang tersedia di `public/assets/png/`.

## Menjalankan dengan Laragon
1. Ekstrak folder proyek ke `C:\laragon\www\tani-pupuk`.
2. Jalankan Apache dan MySQL dari Laragon.
3. Buka phpMyAdmin, buat/import database menggunakan `database/tani_pupuk.sql`. **Catatan: file SQL melakukan DROP dan membuat ulang tabel, jadi jangan diimpor ke database berisi data penting.**
4. Periksa koneksi pada `config/database.php` (default Laragon: host `127.0.0.1`, database `tani_pupuk`, user `root`, password kosong).
5. Buka `http://localhost/tani-pupuk/public/`.

## Akun contoh
Akun contoh pada SQL menggunakan password awal `password`:
- Admin: username `admin`
- Petugas: username `petugas`
- Petani: username/NIK `7201010101000001`

Ganti password akun contoh setelah login sebelum aplikasi digunakan sungguhan.

## Struktur penting
```text
app/
  controllers/Controller.php
  models/Repository.php
  views/partials/
config/database.php
database/tani_pupuk.sql
public/index.php
public/assets/css/
public/assets/js/
public/assets/png/
```

## Catatan pengujian
Pemeriksaan `php -l` perlu dijalankan setelah perubahan. Uji alur login, tambah/ubah/hapus petani, pupuk, kategori, pencarian, pagination, pemesanan, pembayaran, dan validasi pengambilan melalui Laragon. Pembayaran di aplikasi ini merupakan simulasi.

Video demonstrasi dan unggahan GitHub merupakan tahapan pengumpulan yang harus dilakukan mahasiswa setelah aplikasi diuji.
