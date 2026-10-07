# SI-LOGISTIK — Overlay Kumulatif Tahap 1–6

Aplikasi inventaris logistik untuk Bagian Logistik SMA Taruna Nusantara IKN. Paket ini melanjutkan proyek yang sudah memiliki setup/autentikasi, skema database, model, CRUD master barang, kategori, satuan, lokasi, barcode internal, soft delete, unggah foto, dan audit log. Tahap 4 menambahkan transaksi stok masuk/keluar dan POS barcode. Tahap 5 menambahkan scan kamera, kartu stok, histori terfilter, dan penyesuaian stok. Tahap 6 menambahkan dashboard grafik, empat laporan, ekspor PDF/Excel, audit log server-side, dan peringatan stok.

## Kondisi proyek dan keputusan stack

Workspace saat ini memakai Laravel 13, PHP 8.3+, Bootstrap 5.3, Alpine.js, Vite, DataTables 3.1.3 (`datatables.net-bs5`), dan SQLite untuk PHPUnit. Proyek belum memakai Yajra atau jQuery. Ini berbeda dari rencana awal Laravel 12, Yajra DataTables 12, jQuery, dan DataTables 2.3.8; pengguna telah memilih melanjutkan memakai stack yang terpasang. Pertahankan keputusan ini untuk pekerjaan berikutnya.

Frontend dibundel lokal oleh Vite, tanpa CDN. `jsbarcode` menghasilkan barcode Code 128, `qrcode` menghasilkan QR, `html5-qrcode` memindai barcode/QR lewat kamera, dan Chart.js menggambar grafik dashboard. PDF memakai `barryvdh/laravel-dompdf`; Excel memakai `maatwebsite/excel`. Tidak ada migration baru pada Tahap 4–6: laporan/audit/dashboard membaca tabel yang sudah ada.

## Isi overlay

| Bagian | Cakupan |
|---|---|
| Setup dan autentikasi | Login admin tunggal, profil, konfigurasi aplikasi dan bahasa Indonesia |
| Database | 11 migration domain, tabel bawaan Laravel, seeder idempotent |
| Tahap 3 | Model/relasi, enum, factory, CRUD master, barcode internal, foto, DataTables, filter, audit |
| Tahap 4 | `NomorTransaksiService`, `StokService`, Form Request, POS masuk/keluar, pencarian barcode, daftar/detail transaksi, ledger, audit, label Code 128 + QR |
| Tahap 5 | Scan kamera, kartu stok, histori transaksi terfilter, riwayat barang, penyesuaian stok ADJ |
| Tahap 6 | Ringkasan dashboard, grafik 12 bulan dan stok per kategori, peringatan stok, laporan stok/masuk/keluar/mutasi, PDF/Excel, audit log terfilter |
| Frontend | Blade, Bootstrap SCSS, Bootstrap Icons, Alpine, Axios, DataTables, Chart.js, JsBarcode, QRCode |
| Pemeriksaan | PHPUnit, `tools/audit.ps1`, `tools/audit.py` (Python diperlukan untuk skrip ini) |

## Instalasi baru

Instruksi dasar overlay awal ditulis untuk skeleton Laravel 12. Karena workspace sekarang mengikuti Laravel 13, gunakan versi yang sama dengan proyek ini untuk instalasi ulang agar dependensi dan lockfile konsisten. Jangan menyalin `vendor/` atau `node_modules/` ke paket overlay.

```powershell
# Di folder proyek
Copy-Item .env.example .env
composer install
php artisan key:generate
# Buat database MySQL/MariaDB si_logistik dan sesuaikan DB_* di .env
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

`.env.example` menetapkan `APP_LOCALE=id` dan `APP_TIMEZONE=Asia/Makassar`. Buat database dengan charset `utf8mb4`, lalu atur `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`. Seeder membuat akun awal `admin@logistik.local`; atur `ADMIN_EMAIL`, `ADMIN_NAME`, dan `ADMIN_PASSWORD` sebelum `php artisan db:seed`. Jika kata sandi kosong, seeder membuat kata sandi acak dan menampilkannya satu kali di terminal. Nilai nama aplikasi/instansi awal dapat diatur lewat `LOGISTIK_NAMA_APLIKASI`, `LOGISTIK_NAMA_SINGKAT`, dan `LOGISTIK_NAMA_INSTANSI`.

```sql
CREATE DATABASE si_logistik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Jika file overlay ditempel ke checkout yang sudah ada, salin berkas aplikasi dan migrasi kumulatif tanpa menimpa `.env`, `vendor/`, atau `node_modules/`. Lalu jalankan:

```powershell
composer install
npm install
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan test
```

Seeder dapat dijalankan ulang. Untuk reset data pengembangan saja: `php artisan migrate:fresh --seed`.

## Aturan transaksi Tahap 4

- Nomor transaksi memakai awalan `IN`, `OUT`, tanggal aplikasi (`Asia/Makassar`), dan urutan empat digit. Urutan kembali ke `0001` untuk jenis dan tanggal baru.
- Baris `nomor_urut` dibuat dengan `insertOrIgnore`, dikunci `lockForUpdate`, lalu dinaikkan di dalam transaksi database yang sama. Indeks unik dan `transactions.nomor_transaksi` menjadi lapisan pengaman tambahan.
- Semua barang yang terlibat dikunci dalam urutan ID naik. Barang nonaktif/terhapus ditolak.
- Jumlah dari barcode yang sama digabung, baik di keranjang browser maupun di server. Satu barang hanya memiliki satu baris detail per transaksi.
- Stok baru berubah sesudah Simpan ditekan. Transaksi, detail, stok, ledger, nomor, dan audit log disimpan di satu `DB::transaction()`; satu barang gagal berarti seluruh perubahan dibatalkan.
- Barang keluar tidak boleh melampaui stok. Pesan bisnis: `Stok tidak mencukupi. Stok tersedia: N.`
- Setiap barang dalam transaksi mendapat tepat satu baris `stok_mutasi`. Transaksi bersifat immutable; Tahap 4 tidak menyediakan edit atau hapus transaksi.
- Scanner USB bertindak seperti keyboard: fokus pada input barcode, pindai, lalu Enter. Label detail barang mencetak Code 128 dan QR tanpa layanan eksternal.

## Aturan Tahap 5

- Scan kamera memakai `html5-qrcode` yang dibundel melalui Vite. Kamera terus aktif setelah kode terbaca sampai tombol **Selesai** ditekan; pembacaan berulang atas barcode yang sama saat masih di depan kamera tidak menambah kuantitas berulang kali.
- Kamera dan scanner USB dapat dipakai bersamaan. Setelah kamera dimulai dan setiap hasil kamera diproses, fokus keyboard dikembalikan ke input barcode.
- Kamera memerlukan secure context. Di localhost HTTP dapat dipakai untuk pengembangan; untuk ponsel melalui jaringan sekolah, layani aplikasi dengan HTTPS dan sertifikat yang dipercaya perangkat. [MDN](https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia) menjelaskan syarat secure context; pemindai menggunakan API start/stop dari [html5-qrcode](https://github.com/mebjas/html5-qrcode).
- Kartu stok hanya membaca ledger `stok_mutasi`, urut tanggal lalu ID. Saldo setiap baris berasal dari `stok_sesudah`; halaman mendukung cetak.
- Histori transaksi bisa difilter jenis transaksi, barang (termasuk barang terhapus), rentang tanggal, bulan/tahun, nomor, dan petugas.
- Penyesuaian mengunci ulang barang, mengambil stok sistem terbaru, menghitung `selisih = stok_fisik - stok_sistem`, dan menyimpan transaksi `ADJ`, detail, baris `penyesuaian_stok`, ledger, dan audit dalam satu DB transaction.
- Selisih nol ditolak karena tidak ada perubahan stok. Selisih negatif disimpan sebagai `qty_keluar`; positif sebagai `qty_masuk`. Nilai selisih pada tabel penyesuaian tetap bertanda.

## Aturan Tahap 6

- Dashboard menghitung ringkasan dari barang aktif, transaksi, dan ledger. Grafik menampilkan barang masuk/keluar dan jumlah transaksi per bulan selama 12 bulan terakhir serta stok per kategori.
- Peringatan stok menampilkan barang aktif dengan stok nol atau sama/di bawah minimum. Peringatan bersifat live; tidak ada status dibaca dan tidak ada tabel notifikasi baru.
- Laporan terdiri dari stok saat ini, barang masuk, barang keluar, dan mutasi stok. Laporan pergerakan menerima periode hari/minggu/bulan/tahun atau rentang kustom serta filter barang, kategori, lokasi, petugas, dan nomor transaksi bila relevan.
- PDF dan Excel dibuat dari layanan query yang sama dengan tabel laporan, sehingga filter yang aktif tetap berlaku. Snapshot stok tidak memakai filter tanggal karena menunjukkan keadaan stok saat ini.
- Audit Log membaca `aktivitas_log` dengan paginasi server-side DataTables, filter tanggal/modul/petugas, pencarian, subjek, detail ringkas, dan IP. Nilai teks di-escape sebelum ditampilkan.
- Tidak ada perubahan skema database Tahap 6. Dependensi baru adalah Dompdf, Laravel Excel, dan Chart.js; grafik dimuat dinamis hanya di halaman dashboard.

### Menyiapkan HTTPS XAMPP untuk ponsel di jaringan sekolah

Browser hanya memberi akses kamera pada origin aman seperti HTTPS atau localhost. Ponsel harus membuka hostname server lewat HTTPS; `localhost` pada ponsel menunjuk ponsel itu sendiri. Gunakan hostname DNS internal, misalnya `si-logistik.intra`, yang diarahkan ke IP server. Untuk sertifikat, minta sertifikat server dari CA sekolah dengan SAN berisi hostname tersebut (dan IP LAN bila pengguna mengakses melalui IP). Pastikan CA penerbit dipercaya di ponsel dan PC; jangan menyalin private key ke perangkat klien.

1. Tempatkan sertifikat/full chain dan private key di direktori Apache XAMPP yang aksesnya hanya untuk administrator server.
2. Aktifkan `mod_ssl` dan `Listen 443` sekali di konfigurasi Apache. Tambahkan virtual host HTTPS berikut ke konfigurasi SSL Apache, ganti hostname dan file sertifikat sesuai lingkungan sekolah:

```apache
Listen 443
<VirtualHost *:443>
    ServerName si-logistik.intra
    DocumentRoot "D:/Pengembangan Aplikasi/si-logistik/public"
    SSLEngine on
    SSLCertificateFile "C:/xampp/apache/conf/ssl.crt/si-logistik-fullchain.crt"
    SSLCertificateKeyFile "C:/xampp/apache/conf/ssl.key/si-logistik.key"

    <Directory "D:/Pengembangan Aplikasi/si-logistik/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Konfigurasi `mod_ssl` dan directive sertifikat mengacu pada [Apache SSL/TLS How-To](https://httpd.apache.org/docs/2.4/ssl/ssl_howto.html). Jika modul SSL atau `Listen 443` sudah dimuat melalui `httpd-ssl.conf`, jangan menambahkan duplikat.

3. Atur `APP_URL=https://si-logistik.intra` di `.env`, lalu jalankan `php artisan config:clear` dan mulai ulang Apache.
4. Izinkan koneksi TCP 443 dari jaringan sekolah di Windows Firewall. Jangan meneruskan port router ke internet untuk aplikasi internal.
5. Di ponsel yang berada pada Wi-Fi sekolah, buka `https://si-logistik.intra`, pastikan sertifikat valid, izinkan akses kamera, lalu buka Barang Masuk/Keluar dan tekan **Pindai dengan kamera**. Pastikan panel kamera berjalan dan tombol **Selesai** menghentikan kamera.

Pemeriksaan cepat di DevTools pada halaman aplikasi: `window.isSecureContext` harus bernilai `true`. Jika browser tetap menolak kamera, pastikan sertifikat dipercaya perangkat, izin kamera origin tersebut aktif, dan kamera tidak sedang dipakai aplikasi lain.

## Skenario uji manual

| Langkah | Hasil yang diharapkan |
|---|---|
| Masuk sebagai admin dan buka menu Barang Masuk | Halaman POS tampil, kursor fokus di input barcode |
| Pindai barcode USB dan tekan Enter | Barang muncul satu kali di keranjang; stok belum berubah |
| Pindai barang yang sama lagi | Jumlah pada baris yang sama bertambah |
| Cari dengan potongan nama/kode lalu pilih hasil | Barang ditambahkan ke keranjang |
| Ubah jumlah dan tekan Simpan Barang Masuk | Detail transaksi tampil; stok bertambah; ada satu detail, satu ledger, satu audit |
| Buka Barang Keluar tanpa penerima | Server menolak input penerima kosong |
| Simpan jumlah barang keluar di bawah stok tersedia | Transaksi sukses; stok turun sesuai jumlah |
| Coba mengeluarkan barang melebihi stok | Pesan stok yang tersedia tampil; transaksi dan ledger tidak dibuat |
| Keranjang berisi beberapa barang, salah satunya kurang stok | Seluruh transaksi batal; stok semua barang tidak berubah |
| Buka detail barang dan cetak label | Barcode Code 128 dan QR tercetak; layar aplikasi tidak ikut tercetak |
| Buka Riwayat Transaksi, filter tanggal/jenis/nomor | Daftar sesuai filter; detail transaksi tidak menyediakan aksi edit/hapus |
| Buka POS lewat localhost lalu mulai scan kamera | Browser meminta izin kamera; hasil menambah barang, kamera tetap hidup sampai Selesai |
| Buka aplikasi lewat IP LAN memakai HTTP dan tekan Pindai dengan kamera | Aplikasi menjelaskan bahwa HTTPS diperlukan |
| Buka kartu stok barang | Semua mutasi tampil berurutan dengan tanggal, transaksi, masuk, keluar, saldo; tombol cetak bekerja |
| Filter histori berdasarkan barang, petugas, jenis, tanggal, bulan dan tahun | Hanya transaksi yang cocok tampil; transaksi penyesuaian juga ada dalam daftar |
| Hitung stok fisik lebih tinggi dari sistem, tinjau, lalu konfirmasi | Nomor ADJ tercipta, stok naik, selisih positif, detail, ledger masuk, dan audit dibuat |
| Hitung stok fisik lebih rendah dari sistem | Stok turun, selisih negatif, ledger keluar mencatat kuantitas positif |
| Masukkan stok fisik yang sama dengan sistem | Konfirmasi menjelaskan tidak ada perubahan dan transaksi tidak disimpan |
| Buka dashboard | Ringkasan menampilkan total barang/stok, pergerakan hari ini, transaksi bulan ini, grafik mutasi dan jumlah transaksi 12 bulan, stok per kategori, serta stok kritis |
| Klik salah satu barang pada peringatan stok | Detail barang yang sesuai terbuka; stok habis/menipis terlihat |
| Buka tiap tab laporan dan pilih periode serta filter barang/kategori/lokasi/petugas | Tabel hanya menampilkan baris yang sesuai filter |
| Unduh PDF lalu Excel dengan filter aktif | Kedua berkas berisi jenis laporan dan hasil filter yang sama dengan tabel |
| Buka Audit Log, cari aktivitas, lalu filter modul/petugas/tanggal | Tabel server-side hanya menampilkan log yang cocok dan detail ringkasnya |

## Query pemeriksaan MySQL

Nomor berurutan per hari dan jenis:

```sql
SELECT tipe, tanggal, terakhir
FROM nomor_urut
ORDER BY tanggal DESC, tipe;
```

Cek saldo barang terhadap ledger terbaru berdasarkan ID (ledger tidak diubah/dihapus):

```sql
SELECT b.id, b.kode_barang, b.stok AS stok_barang,
       sm.stok_sesudah AS saldo_ledger_terakhir
FROM barang b
LEFT JOIN stok_mutasi sm
  ON sm.id = (
      SELECT sm2.id
      FROM stok_mutasi sm2
      WHERE sm2.barang_id = b.id
      ORDER BY sm2.id DESC
      LIMIT 1
  )
WHERE b.deleted_at IS NULL
  AND (sm.id IS NULL OR b.stok <> sm.stok_sesudah);
```

Hasil query invarian seharusnya kosong. Pemeriksaan ringkas volume transaksi:

```sql
SELECT t.nomor_transaksi, COUNT(DISTINCT td.barang_id) AS jenis_barang,
       COUNT(sm.id) AS jumlah_ledger
FROM transactions t
JOIN transaction_details td ON td.transaction_id = t.id
LEFT JOIN stok_mutasi sm ON sm.transaction_detail_id = td.id
GROUP BY t.id, t.nomor_transaksi
HAVING jenis_barang <> jumlah_ledger;
```

Hasil query seharusnya kosong; setiap detail transaksi memiliki satu baris ledger.

## Pengujian otomatis dan audit pra-serah

Perintah:

```powershell
php artisan test
npm run build
powershell.exe -NoProfile -ExecutionPolicy Bypass -File tools\audit.ps1 .
```

Hasil pada workspace ini:

| Pemeriksaan | Hasil |
|---|---|
| `php artisan test --compact` | 119 lulus, 571 asersi |
| `npm run build` | Berhasil; Chart.js dan kamera dimuat dalam chunk terpisah |
| PHP lint | Dijalankan oleh audit PowerShell; 0 masalah |
| Blade dan referensi | Direktif seimbang; semua include dan komponen ditemukan |
| Rute dan view | Semua `route()`, `routeIs()`, controller method, dan `view()` cocok |
| Rute ke controller | Semua controller dan metode yang terdaftar ditemukan |
| `git diff --check` | Bersih |

Lingkungan tidak menyediakan Python, jadi `tools/audit.py` tidak dijalankan. Audit setara dijalankan dengan `tools/audit.ps1`. Migration MySQL dan pemeriksaan browser/kamera langsung belum dijalankan; PHPUnit memakai SQLite memori. Build final berhasil; Chart.js dan kode kamera berada di chunk terpisah. `npm install` melaporkan dua temuan kritis pada seluruh pohon dependensi; audit dan koreksi dependency menjadi pekerjaan hardening Tahap 7.

## Temuan dan koreksi

| Temuan | Koreksi |
|---|---|
| README sebelumnya menyatakan cakupan berhenti di Tahap 2, padahal kode Tahap 3 sudah ada | README diperbarui menjadi dokumentasi kumulatif Tahap 1–4 |
| Nomor urut belum punya layanan transaksi | Ditambahkan `NomorTransaksiService` yang mengunci baris urut per jenis/tanggal |
| Belum ada alur stok masuk/keluar atomik | Ditambahkan `StokService` dengan lock barang berurutan, validasi stok, detail, ledger, dan audit di satu transaksi |
| Belum ada POS, pencarian barcode, daftar transaksi, atau detail | Ditambahkan halaman, rute, validasi server, dan pencarian barang aktif |
| Detail barang belum bisa mencetak barcode/QR | Ditambahkan JsBarcode dan QRCode lokal serta CSS cetak |
| Belum ada penghitungan fisik stok dan riwayat ledger yang dapat dicetak | Ditambahkan penyesuaian ADJ atomik dan halaman kartu stok baca-saja |
| Kamera langsung dimuat bersama app utama sehingga bundle membesar | Modul kamera menggunakan dynamic import saat tombol scan ditekan |
| Dashboard diagnostik belum memberi informasi stok | Diganti kartu ringkasan, grafik, mutasi terbaru, dan daftar peringatan stok |
| Belum ada cara melihat laporan/mengekspor | Ditambahkan empat laporan dengan filter dan PDF/Excel yang berbagi query |
| Audit log belum memiliki halaman pencarian | Ditambahkan DataTables server-side dan filter modul, petugas, tanggal, pencarian |
| Status notifikasi belum tersimpan dan tidak ada skema persetujuan untuk mengubahnya | Peringatan dibuat langsung dari kondisi stok aktif tanpa migration; tidak ada penanda baca |
| Python tidak tersedia untuk audit bawaan | Ditambahkan audit PowerShell `tools/audit.ps1`; false positive cabang `@empty` pada `@forelse` diperbaiki |

## Tahap berikutnya

Tahap 6 selesai dan menunggu pengujian serta persetujuan pengguna. Tahap 7 berikutnya mencakup hardening, pemeriksaan race condition/rollback/invarian, UX responsif, pengaturan, dan dokumentasi akhir; jangan mulai sebelum tahap ini diuji pengguna.
