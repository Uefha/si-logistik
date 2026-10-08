<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('barang_id')->index()->constrained('barang')->restrictOnDelete();
            $table->decimal('qty', 15, 2); // selalu positif; arah perubahan ada di stok_mutasi
            $table->decimal('stok_sebelum', 15, 2);
            $table->decimal('stok_sesudah', 15, 2);
            $table->timestamps();

            // Satu barang hanya satu baris per transaksi (keranjang menggabungkan barcode yang sama).
            $table->unique(['transaction_id', 'barang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
