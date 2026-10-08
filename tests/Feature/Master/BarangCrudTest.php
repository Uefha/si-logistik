<?php

namespace Tests\Feature\Master;

use App\Models\AktivitasLog;
use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Lokasi;
use App\Models\Satuan;
use App\Models\StokMutasi;
use App\Models\User;
use App\Services\BarcodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BarangCrudTest extends TestCase
{
    use RefreshDatabase;

    /** PNG 1x1 piksel (gambar sungguhan, tanpa butuh ekstensi GD). */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        Storage::fake('public');
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return array_merge([
            'kode_barang' => 'ATK-001',
            'barcode' => '',
            'nama_barang' => 'Kertas A4',
            'kategori_id' => KategoriBarang::factory()->create()->id,
            'satuan_id' => Satuan::factory()->create()->id,
            'lokasi_id' => Lokasi::factory()->create()->id,
            'stok_awal' => '100',
            'stok_minimum' => '20',
            'harga' => '55000',
            'deskripsi' => 'Kertas HVS',
            'is_active' => '1',
        ], $override);
    }

    private function foto(string $nama = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, base64_decode(self::PNG));
    }

    public function test_guest_tidak_dapat_membuka_halaman_barang(): void
    {
        $this->get(route('master.barang.index'))->assertRedirect(route('login'));
        $this->get(route('master.barang.create'))->assertRedirect(route('login'));
        $this->post(route('master.barang.store'), [])->assertRedirect(route('login'));
    }

    public function test_halaman_index_create_edit_show_tampil(): void
    {
        $barang = Barang::factory()->create();

        $this->actingAs($this->admin)->get(route('master.barang.index'))->assertOk()->assertSee('tabel-barang', false);
        $this->actingAs($this->admin)->get(route('master.barang.create'))->assertOk()->assertSee('name="stok_awal"', false);
        $this->actingAs($this->admin)->get(route('master.barang.edit', $barang))->assertOk()->assertDontSee('name="stok_awal"', false);
        $this->actingAs($this->admin)->get(route('master.barang.show', $barang))->assertOk()->assertSee($barang->nama_barang);
    }

    public function test_tambah_barang_membuat_stok_awal_ledger_barcode_otomatis_dan_audit(): void
    {
        $respons = $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload());

        $barang = Barang::where('kode_barang', 'ATK-001')->firstOrFail();
        $respons->assertRedirect(route('master.barang.show', $barang))->assertSessionHas('success', 'Barang berhasil ditambahkan.');

        $this->assertSame('100.00', $barang->stok);
        $this->assertTrue(BarcodeService::validEan13($barang->barcode));

        $mutasi = StokMutasi::where('barang_id', $barang->id)->get();
        $this->assertCount(1, $mutasi);
        $this->assertSame('stok_awal', $mutasi[0]->jenis->value);
        $this->assertSame('100.00', $mutasi[0]->stok_sesudah);
        $this->assertSame($this->admin->id, $mutasi[0]->user_id);

        $log = AktivitasLog::where('modul', 'barang')->latest('id')->first();
        $this->assertSame('Menambahkan barang: Kertas A4', $log->aktivitas);
        $this->assertSame($this->admin->id, $log->user_id);
    }

    public function test_barcode_manual_dipertahankan(): void
    {
        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['barcode' => '8991234567890']));

        $this->assertDatabaseHas('barang', ['kode_barang' => 'ATK-001', 'barcode' => '8991234567890']);
    }

    public function test_validasi_field_wajib(): void
    {
        $this->actingAs($this->admin)->post(route('master.barang.store'), [])
            ->assertSessionHasErrors(['kode_barang', 'nama_barang', 'kategori_id', 'satuan_id', 'lokasi_id', 'stok_awal', 'stok_minimum']);
    }

    public function test_stok_tidak_boleh_negatif(): void
    {
        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['stok_awal' => '-1', 'stok_minimum' => '-5']))
            ->assertSessionHasErrors(['stok_awal', 'stok_minimum']);
    }

    public function test_kode_dan_barcode_harus_unik_termasuk_barang_terhapus(): void
    {
        $lama = Barang::factory()->create(['kode_barang' => 'ATK-001', 'barcode' => '8990000000001']);

        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['kode_barang' => 'ATK-001', 'barcode' => '8990000000002']))
            ->assertSessionHasErrors('kode_barang');
        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['kode_barang' => 'ATK-009', 'barcode' => '8990000000001']))
            ->assertSessionHasErrors('barcode');

        $lama->delete();

        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['kode_barang' => 'ATK-001', 'barcode' => '8990000000002']))
            ->assertSessionHasErrors('kode_barang');
    }

    public function test_barcode_hanya_karakter_aman(): void
    {
        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['barcode' => 'ab cd<script>']))
            ->assertSessionHasErrors('barcode');
    }

    public function test_master_yang_sudah_dihapus_tidak_bisa_dipilih(): void
    {
        $kategori = KategoriBarang::factory()->create();
        $kategori->delete();

        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['kategori_id' => $kategori->id]))
            ->assertSessionHasErrors('kategori_id');
    }

    public function test_upload_foto_valid_tersimpan_di_public_disk(): void
    {
        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['foto' => $this->foto()]))
            ->assertSessionHasNoErrors();

        $barang = Barang::where('kode_barang', 'ATK-001')->firstOrFail();
        $this->assertNotNull($barang->foto);
        $this->assertStringStartsWith('barang/', $barang->foto);
        Storage::disk('public')->assertExists($barang->foto);
    }

    public function test_foto_dengan_tipe_atau_ukuran_salah_ditolak(): void
    {
        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['foto' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('foto');

        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['foto' => UploadedFile::fake()->create('besar.png', 3000, 'image/png')]))
            ->assertSessionHasErrors('foto');

        $this->assertDatabaseMissing('barang', ['kode_barang' => 'ATK-001']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_ubah_barang_tidak_mengubah_stok_walau_dikirim(): void
    {
        $barang = Barang::factory()->create(['stok' => 100, 'stok_minimum' => 10, 'barcode' => '8990000000009']);

        $this->actingAs($this->admin)->put(route('master.barang.update', $barang), $this->payload([
            'kode_barang' => $barang->kode_barang,
            'barcode' => '',
            'nama_barang' => 'Nama Diubah',
            'kategori_id' => $barang->kategori_id,
            'satuan_id' => $barang->satuan_id,
            'lokasi_id' => $barang->lokasi_id,
            'stok' => '9999',
            'stok_awal' => '9999',
            'stok_minimum' => '30',
        ]))->assertRedirect(route('master.barang.show', $barang));

        $barang->refresh();
        $this->assertSame('Nama Diubah', $barang->nama_barang);
        $this->assertSame('30.00', $barang->stok_minimum);
        $this->assertSame('100.00', $barang->stok);
        $this->assertSame('8990000000009', $barang->barcode);
        $this->assertSame(0, StokMutasi::where('barang_id', $barang->id)->count());
    }

    public function test_ganti_dan_hapus_foto(): void
    {
        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['foto' => $this->foto()]));
        $barang = Barang::where('kode_barang', 'ATK-001')->firstOrFail();
        $lama = $barang->foto;

        $this->actingAs($this->admin)->put(route('master.barang.update', $barang), $this->payload([
            'kode_barang' => 'ATK-001',
            'kategori_id' => $barang->kategori_id,
            'satuan_id' => $barang->satuan_id,
            'lokasi_id' => $barang->lokasi_id,
            'foto' => $this->foto('baru.png'),
        ]))->assertSessionHasNoErrors();

        $barang->refresh();
        $this->assertNotSame($lama, $barang->foto);
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($barang->foto);

        $this->actingAs($this->admin)->put(route('master.barang.update', $barang), $this->payload([
            'kode_barang' => 'ATK-001',
            'kategori_id' => $barang->kategori_id,
            'satuan_id' => $barang->satuan_id,
            'lokasi_id' => $barang->lokasi_id,
            'hapus_foto' => '1',
        ]))->assertSessionHasNoErrors();

        $this->assertNull($barang->fresh()->foto);
    }

    public function test_hapus_barang_dengan_stok_ditolak(): void
    {
        $barang = Barang::factory()->create(['stok' => 5]);

        $this->actingAs($this->admin)->deleteJson(route('master.barang.destroy', $barang))
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($pesan) => str_contains($pesan, 'masih memiliki stok'));

        $this->assertNotSoftDeleted($barang);
    }

    public function test_hapus_barang_stok_nol_adalah_soft_delete_dan_histori_tetap(): void
    {
        $this->actingAs($this->admin)->post(route('master.barang.store'), $this->payload(['stok_awal' => '0']));
        $barang = Barang::where('kode_barang', 'ATK-001')->firstOrFail();

        $this->actingAs($this->admin)->deleteJson(route('master.barang.destroy', $barang))->assertOk()->assertJsonMissingPath('redirect');

        $this->assertSoftDeleted($barang);
        $this->assertSame(1, StokMutasi::where('barang_id', $barang->id)->count());
        $this->assertSame(1, AktivitasLog::where('aktivitas', 'Menghapus barang: Kertas A4')->count());
    }

    public function test_hapus_dari_halaman_detail_mengembalikan_url_redirect(): void
    {
        $barang = Barang::factory()->habis()->create();

        $this->actingAs($this->admin)->deleteJson(route('master.barang.destroy', $barang), ['redirect' => 1])
            ->assertOk()
            ->assertJsonPath('redirect', route('master.barang.index'));
    }

    public function test_barang_terhapus_tidak_dapat_dibuka(): void
    {
        $barang = Barang::factory()->habis()->create();
        $barang->delete();

        $this->actingAs($this->admin)->get(route('master.barang.show', $barang->id))->assertNotFound();
    }
}
