<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 50)->unique();
            $table->string('barcode', 64)->unique();
            $table->string('nama_barang', 191)->index();
            $table->foreignId('kategori_id')->index()->constrained('kategori_barang')->restrictOnDelete();
            $table->foreignId('satuan_id')->index()->constrained('satuan')->restrictOnDelete();
            $table->foreignId('lokasi_id')->index()->constrained('lokasi')->restrictOnDelete();
            // Stok hanya boleh diubah oleh StokService (lihat Tahap 6 dan seterusnya).
            $table->decimal('stok', 15, 2)->default(0);
            $table->decimal('stok_minimum', 15, 2)->default(0);
            $table->decimal('harga', 15, 2)->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('stok');
        });

        // Pengaman tambahan di level database: stok tidak boleh negatif.
        // DDL statis tanpa input pengguna. CHECK didukung MySQL >= 8.0.16 dan MariaDB >= 10.2.1
        // (MySQL lama mengabaikannya tanpa error); dilewati di SQLite yang dipakai pada pengujian.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE barang ADD CONSTRAINT chk_barang_stok_tidak_negatif CHECK (stok >= 0)');
            DB::statement('ALTER TABLE barang ADD CONSTRAINT chk_barang_stok_minimum_tidak_negatif CHECK (stok_minimum >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
