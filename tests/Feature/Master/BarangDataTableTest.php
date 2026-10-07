<?php

namespace Tests\Feature\Master;

use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarangDataTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    private function ambil(array $query = []): array
    {
        $respons = $this->actingAs($this->admin)->getJson(route('master.barang.data', $query + ['draw' => 1, 'start' => 0, 'length' => 100]));
        $respons->assertOk()->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        return $respons->json('data');
    }

    /**
     * @param  array<int, array<string, mixed>>  $baris
     * @return list<string>
     */
    private function kode(array $baris): array
    {
        return collect($baris)->pluck('kode_barang')->sort()->values()->all();
    }

    public function test_guest_ditolak(): void
    {
        $this->getJson(route('master.barang.data'))->assertUnauthorized();
    }

    public function test_struktur_baris_dan_kolom_yang_ditampilkan(): void
    {
        Barang::factory()->aman()->create(['kode_barang' => 'A-1', 'nama_barang' => 'Kertas <b>x</b>']);

        $baris = $this->ambil()[0];

        foreach (['DT_RowIndex', 'kode_barang', 'barcode', 'nama_barang', 'kategori_nama', 'lokasi_nama', 'stok', 'stok_minimum', 'status', 'aksi'] as $kunci) {
            $this->assertArrayHasKey($kunci, $baris);
        }
        $this->assertArrayNotHasKey('foto', $baris);
        $this->assertStringContainsString('AMAN', $baris['status']);
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $baris['nama_barang'], 'nama harus di-escape pada kolom HTML');
        $this->assertStringNotContainsString('<b>x</b>', $baris['nama_barang']);
    }

    public function test_filter_status_stok(): void
    {
        Barang::factory()->aman()->create(['kode_barang' => 'AMAN-1']);
        Barang::factory()->menipis()->create(['kode_barang' => 'TIPIS-1']);
        Barang::factory()->habis()->create(['kode_barang' => 'HABIS-1']);

        $this->assertSame(['AMAN-1'], $this->kode($this->ambil(['status_stok' => 'aman'])));
        $this->assertSame(['TIPIS-1'], $this->kode($this->ambil(['status_stok' => 'menipis'])));
        $this->assertSame(['HABIS-1'], $this->kode($this->ambil(['status_stok' => 'habis'])));
        $this->assertSame(['AMAN-1', 'HABIS-1', 'TIPIS-1'], $this->kode($this->ambil(['status_stok' => ''])));
    }

    public function test_filter_kategori_lokasi_dan_status_aktif(): void
    {
        $kategori = KategoriBarang::factory()->create();
        $lokasi = Lokasi::factory()->create();
        Barang::factory()->create(['kode_barang' => 'K-1', 'kategori_id' => $kategori->id]);
        Barang::factory()->create(['kode_barang' => 'L-1', 'lokasi_id' => $lokasi->id]);
        Barang::factory()->nonaktif()->create(['kode_barang' => 'N-1']);

        $this->assertSame(['K-1'], $this->kode($this->ambil(['kategori_id' => $kategori->id])));
        $this->assertSame(['L-1'], $this->kode($this->ambil(['lokasi_id' => $lokasi->id])));
        $this->assertSame(['N-1'], $this->kode($this->ambil(['is_active' => '0'])));
        $this->assertSame(['K-1', 'L-1'], $this->kode($this->ambil(['is_active' => '1'])));
    }

    public function test_pencarian_kode_barcode_nama_kategori_lokasi(): void
    {
        $kategori = KategoriBarang::factory()->create(['nama' => 'Kategori Spesial']);
        $lokasi = Lokasi::factory()->create(['nama' => 'Rak Spesial']);
        Barang::factory()->create(['kode_barang' => 'CARI-1', 'barcode' => '8991111111111', 'nama_barang' => 'Spidol Papan', 'kategori_id' => $kategori->id, 'lokasi_id' => $lokasi->id]);
        Barang::factory()->create(['kode_barang' => 'LAIN-1', 'barcode' => '8992222222222', 'nama_barang' => 'Penghapus']);

        foreach (['CARI-1', '8991111111111', 'spidol', 'Kategori Spesial', 'Rak Spesial'] as $kata) {
            $this->assertSame(['CARI-1'], $this->kode($this->ambil(['search' => ['value' => $kata]])), "pencarian '$kata'");
        }
        $this->assertSame([], $this->ambil(['search' => ['value' => 'tidak-ada-xyz']]));
        $this->assertSame([], $this->ambil(['search' => ['value' => '%']]), 'wildcard harus dianggap teks biasa');
    }

    public function test_barang_terhapus_tidak_muncul_dan_paginasi_bekerja(): void
    {
        Barang::factory()->count(12)->create();
        Barang::factory()->habis()->create(['kode_barang' => 'HAPUS-1'])->delete();

        $halaman = $this->actingAs($this->admin)->getJson(route('master.barang.data', ['draw' => 3, 'start' => 10, 'length' => 10]));

        $halaman->assertOk()->assertJsonPath('draw', 3)->assertJsonPath('recordsTotal', 12)->assertJsonCount(2, 'data');
        $this->assertNotContains('HAPUS-1', $this->kode($this->ambil()));
    }

    public function test_urut_berdasarkan_nama_barang(): void
    {
        Barang::factory()->create(['kode_barang' => 'B', 'nama_barang' => 'Bbb']);
        Barang::factory()->create(['kode_barang' => 'A', 'nama_barang' => 'Aaa']);

        $baris = $this->ambil([
            'columns' => [
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'nama_barang', 'name' => 'nama_barang', 'searchable' => 'true', 'orderable' => 'true'],
            ],
            'order' => [['column' => 1, 'dir' => 'asc']],
        ]);

        $this->assertSame(['A', 'B'], collect($baris)->pluck('kode_barang')->all());
    }
}
