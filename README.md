# Etalase Digital

Aplikasi PHP dan MySQL untuk memamerkan project siswa. Skema `users` dan `projects` mengikuti struktur database pada repositori referensi Etalase Digital; data akun contoh dan password dari dump sumber tidak disalin.

## Menjalankan di XAMPP

1. Buka XAMPP Control Panel dan klik **Start** pada Apache dan MySQL. Keduanya harus berstatus **Running**.
2. Buka `http://localhost/etalase_digital%202/` (atau nama folder yang digunakan).
3. Database `db_stella` dan tabelnya dibuat otomatis saat aplikasi dibuka. Pengaturan koneksi ada di `config/database.php`; koneksi, penyiapan tabel, dan skema SQL masing-masing ada di `database/connection.php`, `database/setup.php`, dan `database/schema.sql`.
4. Pilih **Daftar** untuk membuat akun pertama. Akun pertama menjadi **Super Admin**; pendaftar berikutnya otomatis menjadi **Siswa**.
5. Masuk sebagai Super Admin, lalu buka menu Pengguna untuk membuat akun Admin atau mengelola akun siswa.

Jika MySQL belum berjalan atau gagal dihubungi, aplikasi menampilkan panduan untuk memperbaikinya. Kredensial database default XAMPP adalah `root` tanpa password. Untuk konfigurasi lain, sesuaikan nilai pada `config/database.php` atau atur environment `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, dan `DB_NAME`. Untuk instalasi manual, buat/pilih database terlebih dahulu di phpMyAdmin lalu impor `database/schema.sql`. Gambar project JPG, PNG, atau WebP maksimal 5 MB disimpan di `uploads/`.

## Struktur folder

- `config/database.php` — pengaturan koneksi database saja.
- `database/connection.php` — membuka koneksi ke database.
- `database/setup.php` — menerapkan skema awal dari file SQL.
- `database/schema.sql` — struktur tabel, terpisah dari kode koneksi.
- `includes/` — fungsi autentikasi, validasi, dan operasi CRUD.
- `views/` — halaman antarmuka autentikasi, aplikasi, dan pesan kesalahan.
- `assets/css/` — stylesheet responsif.
- `uploads/` — gambar project yang diunggah.
- `index.php` — pengendali rute dan pengiriman formulir.

## Hak akses

- **Siswa:** melihat semua project, menambahkan project, serta mengubah dan menghapus project sendiri.
- **Admin:** seluruh akses project dan CRUD data akun siswa.
- **Super Admin:** seluruh akses Admin serta pengelolaan semua peran pengguna.

Perubahan project dan pengguna memakai POST dengan token CSRF. Link project/demo hanya menerima HTTP atau HTTPS.
