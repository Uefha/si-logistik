<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transaksi', 30)->unique(); // IN/OUT/ADJ-YYYYMMDD-0001
            $table->enum('tipe', ['masuk', 'keluar', 'penyesuaian']);
            $table->date('tanggal');
            $table->foreignId('user_id')->index()->constrained('users')->restrictOnDelete();
            $table->string('tujuan', 191)->nullable(); // tujuan/penerima (barang keluar)
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['tipe', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
