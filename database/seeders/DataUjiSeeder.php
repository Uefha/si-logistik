<?php

namespace Database\Seeders;

use App\Enums\TipeTransaksi;
use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Lokasi;
use App\Models\Satuan;
use App\Models\User;
use App\Services\StokService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Data contoh operasional untuk mencoba fitur inventaris. Jalankan secara eksplisit. */
class DataUjiSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            KategoriSeeder::class,
            SatuanSeeder::class,
            LokasiSeeder::class,
        ]);

        $user = User::query()->first();
        if (! $user) {
            $this->command?->error('Akun belum tersedia. Jalankan php artisan db:seed terlebih dahulu.');
            return;
        }

        $kategori = KategoriBarang::query()->where('nama', 'ATK')->firstOrFail();
        $elektronik = KategoriBarang::query()->where('nama', 'Peralatan Elektronik')->firstOrFail();
        $kebersihan = KategoriBarang::query()->where('nama', 'Peralatan Kebersihan')->firstOrFail();
        $pcs = Satuan::query()->where('nama', 'Pcs')->firstOrFail();
        $unit = Satuan::query()->where('nama', 'Unit')->firstOrFail();
        $botol = Satuan::query()->where('nama', 'Botol')->firstOrFail();
        $rakA = Lokasi::query()->where('nama', 'Rak A')->firstOrFail();
        $gudang = Lokasi::query()->where('nama', 'Gudang Utama')->firstOrFail();
        $ruang = Lokasi::query()->where('nama', 'Ruang Logistik')->firstOrFail();

        $items = [
            ['DUMMY-ATK-001', '8999000000011', 'Kertas A4 80 gsm', $kategori, $pcs, $rakA, 12, 50, 42000],
            ['DUMMY-ATK-002', '8999000000028', 'Pulpen gel hitam', $kategori, $pcs, $rakA, 25, 100, 3500],
            ['DUMMY-ELK-001', '8999000000035', 'Mouse USB optik', $elektronik, $unit, $ruang, 0, 5, 65000],
            ['DUMMY-KBR-001', '8999000000042', 'Sabun pembersih lantai 1 L', $kebersihan, $botol, $gudang, 4, 20, 18000],
            ['DUMMY-ELK-002', '8999000000059', 'Kabel ekstensi 5 m', $elektronik, $unit, $gudang, 8, 3, 85000],
        ];

        $barang = [];
        foreach ($items as [$kode, $barcode, $nama, $kat, $satuan, $lokasi, $stok, $minimum, $harga]) {
            $item = Barang::query()->firstOrCreate(
                ['kode_barang' => $kode],
                [
                    'barcode' => $barcode,
                    'nama_barang' => $nama,
                    'kategori_id' => $kat->id,
                    'satuan_id' => $satuan->id,
                    'lokasi_id' => $lokasi->id,
                    'stok_minimum' => $minimum,
                    'harga' => $harga,
                    'deskripsi' => 'Data dummy untuk pengujian fitur SI Logistik.',
                    'is_active' => true,
                ]
            );
            if ($item->wasRecentlyCreated) {
                // Stok tidak boleh diisi lewat mass assignment; seed awal secara eksplisit.
                $item->stok = $stok;
                $item->save();
            }
            $barang[] = $item;
        }

        // Stok awal dibuatkan baris ledger agar kartu stok dimulai dengan saldo yang benar.
        foreach ($barang as $item) {
            if ($item->wasRecentlyCreated) {
                DB::table('stok_mutasi')->insert([
                    'barang_id' => $item->id,
                    'transaction_id' => null,
                    'transaction_detail_id' => null,
                    'jenis' => 'stok_awal',
                    'qty_masuk' => $item->stok,
                    'qty_keluar' => 0,
                    'stok_sebelum' => 0,
                    'stok_sesudah' => $item->stok,
                    'tanggal' => now(),
                    'user_id' => $user->id,
                    'keterangan' => 'Stok awal data dummy',
                    'created_at' => now(),
                ]);
            }
        }

        // Transaksi contoh hanya dicatat sekali. Ini menguji laporan masuk/keluar,
        // kartu stok, audit, serta skenario stok menipis dan habis.
        $sudahAda = DB::table('transactions')->where('keterangan', 'Data dummy pengujian SI Logistik')->exists();
        if (! $sudahAda) {
            $service = app(StokService::class);
            $service->simpan(TipeTransaksi::Masuk, [
                ['barang_id' => $barang[0]->id, 'qty' => 15],
                ['barang_id' => $barang[1]->id, 'qty' => 40],
                ['barang_id' => $barang[2]->id, 'qty' => 8],
            ], $user, keterangan: 'Data dummy pengujian SI Logistik');

            $service->simpan(TipeTransaksi::Keluar, [
                ['barang_id' => $barang[0]->id, 'qty' => 5],
                ['barang_id' => $barang[1]->id, 'qty' => 15],
                ['barang_id' => $barang[2]->id, 'qty' => 8],
            ], $user, 'Simulasi permintaan Bagian Tata Usaha', 'Data dummy pengujian SI Logistik');

            $service->sesuaikan($barang[3], $barang[3]->stok, 2, 'Simulasi stok fisik kurang', $user);
        }

        $this->command?->info('Data uji siap: 5 barang, contoh transaksi masuk/keluar, dan penyesuaian stok.');
    }
}
