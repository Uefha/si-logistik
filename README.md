# SI-Logistik SMA Taruna Nusantara IKN

Aplikasi inventaris untuk pendataan barang, stok, transaksi masuk/keluar, penyesuaian stok, laporan, dan audit log. Aplikasi dibuat dengan Laravel dan dapat dipasang sebagai PWA di ponsel.

## Persyaratan

- Windows 10/11 (atau OS lain yang mendukung perangkat di bawah).
- PHP 8.3 atau lebih baru, Composer 2, Node.js 20.19+ (atau 22.12+) dan npm.
- MySQL 8.0.16+ atau MariaDB 10.2.1+, serta ekstensi PHP `pdo_mysql`.
- Ekstensi PHP Laravel yang umum: `fileinfo`, `mbstring`, `openssl`, `pdo`, `tokenizer`, dan `xml`.

## Instalasi di PC baru

1. Unduh ZIP dari GitHub atau clone repositori, lalu buka PowerShell di folder project:

```powershell
git clone <URL-REPOSITORY>
cd <NAMA-FOLDER-PROJECT>
```

2. Buat database MySQL/MariaDB:

```sql
CREATE DATABASE si_logistik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Pasang dependency dan siapkan konfigurasi:

```powershell
composer install
Copy-Item .env.example .env
notepad .env
```

Pada `.env`, isi `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai database PC tersebut. Atur juga `ADMIN_EMAIL` dan `ADMIN_PASSWORD`. Isi `LOGISTIK_KATA_KUNCI_PEMULIHAN` dengan kata kunci rahasia untuk fitur lupa sandi. Jangan unggah `.env` ke GitHub.

4. Buat kunci aplikasi, siapkan tabel dan akun admin, lalu bangun aset frontend:

```powershell
php artisan key:generate
php artisan migrate --seed
npm ci
npm run build
```

Jika `ADMIN_PASSWORD` dikosongkan, seeder membuat kata sandi acak dan menampilkannya di terminal. Catat saat itu juga. Akun awal memakai `ADMIN_EMAIL` dari `.env`.

5. Jalankan aplikasi:

```powershell
php artisan serve
```

Buka <http://127.0.0.1:8000> di browser. Untuk membuat data contoh tambahan setelah instalasi, jalankan `php artisan db:seed --class=DataUjiSeeder`.

## Pasang di ponsel sebagai PWA

PWA memerlukan HTTPS dengan sertifikat yang dipercaya ponsel (localhost hanya untuk PC itu sendiri). Untuk akses lewat jaringan, host aplikasi pada web server HTTPS dan atur `APP_URL` di `.env` ke alamat tersebut. Setelah aplikasi dibuka melalui HTTPS:

- **Android:** tekan **Pasang aplikasi** atau pilih **Instal aplikasi** pada menu browser.
- **iPhone/iPad:** buka di Safari, tekan **Bagikan**, lalu pilih **Tambahkan ke Layar Utama**.

Service worker menyediakan halaman offline dan menyimpan aset publik. Data akun, laporan, serta transaksi tetap memerlukan koneksi internet.

## Konfigurasi identitas

Nama aplikasi dan sekolah dapat diubah melalui `LOGISTIK_NAMA_APLIKASI`, `LOGISTIK_NAMA_SINGKAT`, dan `LOGISTIK_NAMA_INSTANSI` di `.env`. Identitas bawaan: **SI-Logistik SMA Taruna Nusantara IKN**.

© Muhammad Nur Fadila
