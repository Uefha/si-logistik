<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ledger perubahan stok: sumber histori transaksi dan kartu stok.
     * Baris tidak pernah diubah atau dihapus (hanya created_at, tanpa updated_at).
     */
    public function up(): void
    {
        Schema::create('stok_mutasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->restrictOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->restrictOnDelete(); // NULL = stok awal
            $table->foreignId('transaction_detail_id')->nullable()->constrained('transaction_details')->restrictOnDelete();
            $table->enum('jenis', ['stok_awal', 'masuk', 'keluar', 'penyesuaian']);
            $table->decimal('qty_masuk', 15, 2)->default(0);
            $table->decimal('qty_keluar', 15, 2)->default(0);
            $table->decimal('stok_sebelum', 15, 2);
            $table->decimal('stok_sesudah', 15, 2);
            $table->dateTime('tanggal');
            $table->foreignId('user_id')->index()->constrained('users')->restrictOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['barang_id', 'tanggal', 'id'], 'stok_mutasi_barang_tanggal_idx');
            $table->index(['jenis', 'tanggal'], 'stok_mutasi_jenis_tanggal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_mutasi');
    }
};
