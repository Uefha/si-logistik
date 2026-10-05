# SI-LOGISTIK - Tahap 2: Setup, Autentikasi, Migration, Seeder

Paket ini adalah **overlay**: ditempel di atas skeleton Laravel 12 yang baru dibuat (tanpa `vendor/` dan `node_modules/`).
Cakupan hanya Tahap 2. Model/relasi (Tahap 3), master data CRUD, dashboard, dan transaksi belum ada.

## Isi paket

| Bagian | Berkas |
|---|---|
| Konfigurasi | `config/app.php` (timezone dari `.env`), `config/logistik.php`, `.env.example` |
| Bahasa | `lang/id/` (auth, validation, passwords, pagination) |
| Autentikasi (turunan Breeze, stack Blade) | `routes/auth.php`, `AuthenticatedSessionController`, `PasswordController`, `LoginRequest`, `ProfileController`, `ProfileUpdateRequest` |
| Tampilan Bootstrap 5 | layout `x-layouts.app` dan `x-layouts.guest`, `x-sidebar-link`, `x-flash` (toast), halaman masuk, dashboard sementara, Profil Admin |
| Frontend | `package.json`, `vite.config.js`, `resources/sass/app.scss`, `resources/js/app.js` (Bootstrap 5, Bootstrap Icons, Alpine.js, tanpa Tailwind) |
| Database | 11 migration (urutan sesuai ERD), 7 seeder idempotent |
| Pengujian | `tests/TestCase.php`, `tests/Feature/*` (autentikasi, profil, skema database, seeder) |
| Alat | `tools/audit.py` (audit pra-serah) |

Tabel yang dibuat: `kategori_barang`, `satuan`, `lokasi`, `barang`, `transactions`, `transaction_details`,
`stok_mutasi`, `penyesuaian_stok`, `aktivitas_log`, `pengaturan`, `nomor_urut` (ditambah tabel bawaan Laravel).

## Instalasi

Prasyarat: PHP 8.3+, Composer, Node.js 20+, MySQL/MariaDB (XAMPP), dan ekstensi PHP `pdo_mysql` serta `pdo_sqlite` (SQLite dipakai oleh `php artisan test`).

```powershell
# 1. Buat proyek Laravel 12 baru
composer create-project laravel/laravel si-logistik "12.*"
cd si-logistik

# 2. Salin SELURUH isi paket ini ke folder si-logistik (pilih "timpa/replace" bila ditanya)

# 3. Hapus berkas skeleton yang tidak dipakai lagi
Remove-Item resources\views\welcome.blade.php
Remove-Item resources\css\app.css

# 4. Siapkan .env (menimpa .env bawaan, lalu buat APP_KEY baru)
Copy-Item .env.example .env -Force
php artisan key:generate
```

Linux/macOS: ganti langkah 3 dengan `rm resources/views/welcome.blade.php resources/css/app.css` dan langkah 4 dengan `cp .env.example .env && php artisan key:generate`.

5. Buat database kosong `si_logistik` (utf8mb4_unicode_ci) lewat phpMyAdmin, atau:

```powershell
mysql -u root -e "CREATE DATABASE si_logistik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

6. Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` di `.env`. Isi `ADMIN_PASSWORD` bila ingin kata sandi admin tertentu;
   bila dikosongkan, seeder membuat kata sandi acak dan menampilkannya **satu kali** di terminal.

```powershell
php artisan migrate --seed
npm install
npm run build        # atau: npm run dev (selama pengembangan)
php artisan serve    # buka http://localhost:8000
```

Masuk dengan email `ADMIN_EMAIL` (bawaan `admin@logistik.local`) dan kata sandi dari langkah 6, lalu segera ganti lewat menu Profil Admin.

Reset total saat pengembangan: `php artisan migrate:fresh --seed`.

## Keputusan teknis Tahap 2

1. **Breeze tanpa memasang paketnya.** Kode controller dan request diambil dari scaffolding Breeze (stack Blade) lalu disederhanakan untuk satu admin.
   Dihapus: registrasi, verifikasi email, reset kata sandi via email, hapus akun. Tailwind tidak dipakai; seluruh tampilan Bootstrap 5.
2. **`config/app.php` diubah satu baris**: `'timezone' => env('APP_TIMEZONE', 'UTC')`. Pada skeleton Laravel 12 nilai ini di-hardcode `UTC`,
   sehingga `APP_TIMEZONE` di `.env` tidak berpengaruh tanpa perubahan ini. Bawaan paket: `Asia/Makassar` (WITA).
3. **Seeder memakai query builder, bukan Model**, karena Model baru dibuat di Tahap 3. Semua seeder aman dijalankan berulang:
   akun admin tidak ditimpa, pengaturan yang diubah admin tidak ditimpa, dan baris master yang sudah di-soft-delete tidak dibuat ulang.
4. **CHECK constraint** `stok >= 0` dan `stok_minimum >= 0` ditambahkan pada tabel `barang` hanya untuk MySQL/MariaDB
   (MySQL lama di bawah 8.0.16 mengabaikannya tanpa error; dilewati di SQLite saat test). Ini pengaman tambahan; validasi utama tetap di `StokService`.
5. **Unique index mencakup baris soft-delete** (sesuai keputusan Tahap 1): kode barang, barcode, dan nama master yang sudah dihapus tidak bisa dipakai ulang.
6. **Tanpa CDN.** Bootstrap, Bootstrap Icons, dan Alpine dibundel Vite sehingga aplikasi berjalan di jaringan sekolah tanpa internet.
7. **Dashboard sementara** menampilkan diagnostik (versi, database, zona waktu, bahasa, waktu server) untuk memverifikasi instalasi. Diganti pada Tahap 5.
8. **Menu sidebar** hanya berisi Dashboard dan Profil Admin; menu modul lain ditambahkan pada tahap masing-masing agar tidak ada tautan ke halaman yang belum ada.

## Skenario uji manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Buka `http://localhost:8000` sebelum masuk | Diarahkan ke halaman Masuk |
| 2 | Masuk dengan kata sandi salah | Pesan "Email atau kata sandi salah." di bawah kolom email, tetap di halaman Masuk |
| 3 | Salah 5 kali berturut-turut, lalu coba kata sandi benar | Ditolak dengan pesan terlalu banyak percobaan; akses pulih setelah hitungan detik berakhir |
| 4 | Masuk dengan akun admin | Dashboard tampil; diagnostik menunjukkan zona waktu `Asia/Makassar`, bahasa `id`, database `mysql / si_logistik` |
| 5 | Buka `/register` dan `/forgot-password` | Halaman 404 |
| 6 | Profil Admin: ubah nama lalu simpan | Toast hijau "Profil berhasil diperbarui."; nama di pojok kanan atas berubah |
| 7 | Profil Admin: ubah email ke email yang belum dipakai | Berhasil; masuk ulang memakai email baru |
| 8 | Ubah kata sandi dengan kata sandi saat ini salah | Pesan kesalahan pada kolom "Kata sandi saat ini" |
| 9 | Ubah kata sandi: baru kurang dari 8 karakter, atau konfirmasi tidak sama | Pesan kesalahan pada kolom kata sandi baru |
| 10 | Ubah kata sandi dengan benar, keluar, masuk lagi | Toast sukses; masuk berhasil dengan kata sandi baru |
| 11 | Perkecil jendela ke lebar ponsel | Sidebar tersembunyi; tombol menu membuka sidebar dari kiri |
| 12 | Klik menu pengguna, pilih Keluar | Kembali ke halaman Masuk |

Pemeriksaan database:

```powershell
php artisan migrate:status          # semua migration berstatus Ran
php artisan db:table barang         # kolom, indeks, dan foreign key barang
php artisan db:seed                 # jalankan lagi: jumlah data tidak bertambah
```

```sql
SELECT (SELECT COUNT(*) FROM kategori_barang) AS kategori,   -- 10
       (SELECT COUNT(*) FROM satuan)          AS satuan,     -- 12
       (SELECT COUNT(*) FROM lokasi)          AS lokasi,     -- 10
       (SELECT COUNT(*) FROM pengaturan)      AS pengaturan, -- 3
       (SELECT COUNT(*) FROM users)           AS users;      -- 1
```

## Pengujian otomatis

```powershell
php artisan test
```

Mencakup: alur masuk/keluar, penguncian setelah 5 percobaan, pengalihan tamu, rute registrasi yang tidak ada, pembaruan profil dan kata sandi,
keberadaan seluruh tabel, constraint unik dan foreign key, serta sifat idempotent seeder.

## Audit pra-serah

Dijalankan terhadap pohon gabungan (skeleton Laravel 12 + paket ini) sebelum paket dibuat. Dapat dijalankan ulang:

```powershell
python tools/audit.py .
```

| Langkah | Hasil |
|---|---|
| 1. `php -l` seluruh berkas PHP | 66 berkas, 0 gagal |
| 2. Keseimbangan direktif Blade | 7 berkas, 0 tidak seimbang |
| 3. Referensi `@include` dan `<x-...>` | 7 referensi, 0 hilang |
| 4. Seluruh `route()` dan `routeIs()` cocok dengan rute terdaftar | 16 pemanggilan, 0 tidak cocok |
| 5. Seluruh `view()` punya berkas view | 3 pemanggilan, 0 hilang |
| Tambahan: `use` kelas proyek, migration, `$request->all()` | 24 statement, 14 migration, 0 temuan |
| Build frontend (`vite build`) di proyek uji | Berhasil (SCSS dan JS terkompilasi) |

Audit dites dengan menyisipkan kesalahan (rute salah, `@endforeach` hilang, sintaks PHP rusak); ketiganya terdeteksi.

### Temuan dan koreksi selama pembuatan

| # | Temuan | Perbaikan |
|---|---|---|
| 1 | Dokumen Tahap 1 menyebut zona waktu diatur lewat `APP_TIMEZONE`; pada skeleton Laravel 12 timezone di-hardcode `UTC` | `config/app.php` diubah membaca `APP_TIMEZONE` (keputusan teknis nomor 2) |
| 2 | Opsi Sass `mixed-decls` pada konfigurasi Vite sudah usang dan memunculkan peringatan | Dihapus dari `vite.config.js` |
| 3 | Sidebar responsif: kelas `offcanvas-lg` Bootstrap tidak membuat sidebar tetap di layar lebar | Gaya `position: fixed` pada layar besar ditambahkan di `app.scss`; CSS hasil build diperiksa |
| 4 | Test profil memakai email berhuruf besar yang ditolak aturan `lowercase` | Test diperbaiki memakai email huruf kecil |
| 5 | Breadcrumb menandai item tengah sebagai halaman aktif | Percabangan diubah: item terakhir aktif, item bertautan jadi tautan, sisanya teks biasa |

### Batas verifikasi (harap dibaca)

Lingkungan pembuatan tidak punya akses ke Packagist, sehingga Laravel belum dapat dipasang di sana. Artinya
**`php artisan migrate`, `php artisan test`, dan tampilan di browser belum dijalankan oleh saya.** Yang sudah diperiksa: sintaks PHP, audit statis di atas,
dan build Vite. Mohon jalankan `php artisan migrate:fresh --seed` dan `php artisan test`, lalu kirim pesan error persisnya bila ada.

## Berikutnya: Tahap 3

Model Eloquent dan relasi (User, KategoriBarang, Satuan, Lokasi, Barang, Transaction, TransactionDetail, StokMutasi, PenyesuaianStok, AktivitasLog, Pengaturan),
Enum (TipeTransaksi, JenisMutasi, StatusStok), dan factory.
