<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Buat akun Admin / Petugas Logistik jika belum ada.
     * Jika akun sudah ada, tidak diubah (kata sandi yang sudah diganti admin tetap aman).
     */
    public function run(): void
    {
        $email = (string) config('logistik.admin.email');

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info("Akun admin {$email} sudah ada, dilewati.");

            return;
        }

        $password = config('logistik.admin.password');
        $dibuatAcak = blank($password);

        if ($dibuatAcak) {
            $password = Str::password(16, symbols: false);
        }

        // Cast 'hashed' pada model User meng-hash kata sandi saat disimpan.
        User::query()->create([
            'name' => (string) config('logistik.admin.name'),
            'email' => $email,
            'password' => $password,
        ]);

        $this->command?->info("Akun admin dibuat: {$email}");

        if ($dibuatAcak) {
            $this->command?->warn("Kata sandi acak (catat sekarang, tidak ditampilkan lagi): {$password}");
        }
    }
}
