<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_tables_exist(): void
    {
        foreach ([
            'users', 'kategori_barang', 'satuan', 'lokasi', 'barang', 'transactions',
            'transaction_details', 'stok_mutasi', 'penyesuaian_stok', 'aktivitas_log',
            'pengaturan', 'nomor_urut',
        ] as $tabel) {
            $this->assertTrue(Schema::hasTable($tabel), "Tabel {$tabel} tidak ada");
        }
    }

    public function test_master_tables_use_soft_deletes(): void
    {
        foreach (['barang', 'kategori_barang', 'satuan', 'lokasi'] as $tabel) {
            $this->assertTrue(Schema::hasColumn($tabel, 'deleted_at'), "{$tabel} belum memakai soft delete");
        }
    }

    public function test_barang_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('barang', [
            'kode_barang', 'barcode', 'nama_barang', 'kategori_id', 'satuan_id', 'lokasi_id',
            'stok', 'stok_minimum', 'harga', 'deskripsi', 'foto', 'is_active',
        ]));
    }

    public function test_kode_barang_must_be_unique(): void
    {
        $this->buatBarang(['kode_barang' => 'ATK-001', 'barcode' => '2000000000011']);

        $this->expectException(QueryException::class);
        $this->buatBarang(['kode_barang' => 'ATK-001', 'barcode' => '2000000000028']);
    }

    public function test_barcode_must_be_unique(): void
    {
        $this->buatBarang(['kode_barang' => 'ATK-001', 'barcode' => '2000000000011']);

        $this->expectException(QueryException::class);
        $this->buatBarang(['kode_barang' => 'ATK-002', 'barcode' => '2000000000011']);
    }

    public function test_category_in_use_cannot_be_hard_deleted(): void
    {
        $barang = $this->buatBarang();

        $this->expectException(QueryException::class);
        DB::table('kategori_barang')->where('id', $barang['kategori_id'])->delete();
    }

    public function test_nomor_transaksi_must_be_unique(): void
    {
        $this->buatTransaksi('IN-20261005-0001');

        $this->expectException(QueryException::class);
        $this->buatTransaksi('IN-20261005-0001');
    }

    public function test_barang_appears_only_once_per_transaction(): void
    {
        $barang = $this->buatBarang();
        $transaksiId = $this->buatTransaksi('OUT-20261005-0001');

        $detail = [
            'transaction_id' => $transaksiId,
            'barang_id' => $barang['id'],
            'qty' => 1,
            'stok_sebelum' => 10,
            'stok_sesudah' => 9,
        ];
        DB::table('transaction_details')->insert($detail);

        $this->expectException(QueryException::class);
        DB::table('transaction_details')->insert($detail);
    }

    public function test_nomor_urut_is_unique_per_type_and_date(): void
    {
        DB::table('nomor_urut')->insert(['tipe' => 'masuk', 'tanggal' => '2026-10-05', 'terakhir' => 1]);

        // Jenis berbeda pada tanggal yang sama diperbolehkan.
        DB::table('nomor_urut')->insert(['tipe' => 'keluar', 'tanggal' => '2026-10-05', 'terakhir' => 1]);

        $this->expectException(QueryException::class);
        DB::table('nomor_urut')->insert(['tipe' => 'masuk', 'tanggal' => '2026-10-05', 'terakhir' => 2]);
    }

    public function test_initial_stock_can_be_recorded_without_a_transaction(): void
    {
        $barang = $this->buatBarang(['stok' => 25]);
        $user = User::factory()->create();

        DB::table('stok_mutasi')->insert([
            'barang_id' => $barang['id'],
            'transaction_id' => null,
            'jenis' => 'stok_awal',
            'qty_masuk' => 25,
            'qty_keluar' => 0,
            'stok_sebelum' => 0,
            'stok_sesudah' => 25,
            'tanggal' => now(),
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('stok_mutasi', ['barang_id' => $barang['id'], 'jenis' => 'stok_awal']);
    }

    /**
     * Buat satu barang beserta kategori, satuan, dan lokasi pendukungnya.
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function buatBarang(array $override = []): array
    {
        $sekarang = now();

        $kategoriId = DB::table('kategori_barang')->where('nama', 'ATK Uji')->value('id')
            ?? DB::table('kategori_barang')->insertGetId(['nama' => 'ATK Uji', 'created_at' => $sekarang, 'updated_at' => $sekarang]);
        $satuanId = DB::table('satuan')->where('nama', 'Pcs Uji')->value('id')
            ?? DB::table('satuan')->insertGetId(['nama' => 'Pcs Uji', 'created_at' => $sekarang, 'updated_at' => $sekarang]);
        $lokasiId = DB::table('lokasi')->where('nama', 'Rak Uji')->value('id')
            ?? DB::table('lokasi')->insertGetId(['nama' => 'Rak Uji', 'created_at' => $sekarang, 'updated_at' => $sekarang]);

        $data = array_merge([
            'kode_barang' => 'BRG-001',
            'barcode' => '2000000000004',
            'nama_barang' => 'Barang Uji',
            'kategori_id' => $kategoriId,
            'satuan_id' => $satuanId,
            'lokasi_id' => $lokasiId,
            'stok' => 10,
            'stok_minimum' => 2,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ], $override);

        $data['id'] = DB::table('barang')->insertGetId($data);

        return $data;
    }

    private function buatTransaksi(string $nomor): int
    {
        $user = User::query()->first() ?? User::factory()->create();

        return DB::table('transactions')->insertGetId([
            'nomor_transaksi' => $nomor,
            'tipe' => str_starts_with($nomor, 'IN') ? 'masuk' : 'keluar',
            'tanggal' => '2026-10-05',
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
