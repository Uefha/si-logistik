<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Aplikasi
    |--------------------------------------------------------------------------
    | Nilai bawaan. Pada tahap Master Data nilai ini dibaca dari tabel
    | `pengaturan` sehingga dapat diubah admin tanpa menyentuh kode.
    */

    'nama_aplikasi' => env('LOGISTIK_NAMA_APLIKASI', 'SI-Logistik SMA Taruna Nusantara IKN'),
    'nama_singkat' => env('LOGISTIK_NAMA_SINGKAT', 'SI-Logistik'),
    'nama_instansi' => env('LOGISTIK_NAMA_INSTANSI', 'SMA Taruna Nusantara IKN'),

    // Kata kunci rahasia untuk membuka pemulihan sandi. Atur melalui .env.
    'kata_kunci_pemulihan' => env('LOGISTIK_KATA_KUNCI_PEMULIHAN'),

    /*
    |--------------------------------------------------------------------------
    | Akun Admin Awal (hanya dipakai oleh AdminSeeder)
    |--------------------------------------------------------------------------
    | Jika ADMIN_PASSWORD kosong, seeder membuat kata sandi acak dan
    | menampilkannya satu kali di terminal.
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin Logistik'),
        'email' => env('ADMIN_EMAIL', 'admin@logistik.local'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
