<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Aplikasi
    |--------------------------------------------------------------------------
    | Nilai bawaan. Pada tahap Master Data nilai ini dibaca dari tabel
    | `pengaturan` sehingga dapat diubah admin tanpa menyentuh kode.
    */

    'nama_aplikasi' => env('LOGISTIK_NAMA_APLIKASI', 'Sistem Informasi Logistik'),
    'nama_singkat' => env('LOGISTIK_NAMA_SINGKAT', 'SI-LOGISTIK'),
    'nama_instansi' => env('LOGISTIK_NAMA_INSTANSI', 'Bagian Logistik'),

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
