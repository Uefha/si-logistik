<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penghitung nomor transaksi per jenis dan per tanggal. Dikunci dengan
     * lockForUpdate() saat dipakai agar aman dari race condition.
     */
    public function up(): void
    {
        Schema::create('nomor_urut', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['masuk', 'keluar', 'penyesuaian']);
            $table->date('tanggal');
            $table->unsignedInteger('terakhir')->default(0);

            $table->unique(['tipe', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomor_urut');
    }
};
