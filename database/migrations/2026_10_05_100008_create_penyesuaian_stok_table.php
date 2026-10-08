<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyesuaian_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->unique()->constrained('transactions')->restrictOnDelete();
            $table->foreignId('barang_id')->index()->constrained('barang')->restrictOnDelete();
            $table->decimal('stok_sistem', 15, 2);
            $table->decimal('stok_fisik', 15, 2);
            $table->decimal('selisih', 15, 2); // bertanda: stok_fisik - stok_sistem
            $table->text('alasan');
            $table->date('tanggal');
            $table->foreignId('user_id')->index()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyesuaian_stok');
    }
};
