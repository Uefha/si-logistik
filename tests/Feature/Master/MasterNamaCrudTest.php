<?php

namespace Tests\Feature\Master;

use App\Models\AktivitasLog;
use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Lokasi;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kategori, satuan, dan lokasi memakai pola yang sama; dijalankan lewat data provider.
 */
class MasterNamaCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: class-string, 1: string, 2: string, 3: string}>
     */
    public static function master(): array
    {
        return [
            'kategori' => [KategoriBarang::class, 'master.kategori', 'kategori_id', 'kategori'],
            'satuan' => [Satuan::class, 'master.satuan', 'satuan_id', 'satuan'],
            'lokasi' => [Lokasi::class, 'master.lokasi', 'lokasi_id', 'lokasi'],
        ];
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    #[DataProvider('master')]
    public function test_guest_diarahkan_ke_login(string $kelas, string $rute, string $kolomFk, string $modul): void
    {
        $this->get(route($rute.'.index'))->assertRedirect(route('login'));
        $this->postJson(route($rute.'.store'), ['nama' => 'X'])->assertUnauthorized();
    }

    #[DataProvider('master')]
    public function test_halaman_index_tampil(string $kelas, string $rute, string $kolomFk, string $modul): void
    {
        $this->actingAs($this->admin())->get(route($rute.'.index'))->assertOk()->assertSee('data-crud-create', false);
    }

    #[DataProvider('master')]
    public function test_tambah_data_dan_audit_log(string $kelas, string $rute, string $kolomFk, string $modul): void
    {
        $this->actingAs($this->admin())
            ->postJson(route($rute.'.store'), ['nama' => 'Contoh Uji'])
            ->assertCreated()
            ->assertJsonPath('message', fn ($pesan) => str_contains($pesan, 'berhasil ditambahkan'));

        $this->assertDatabaseHas((new $kelas)->getTable(), ['nama' => 'Contoh Uji']);
        $this->assertDatabaseHas('aktivitas_log', ['modul' => $modul, 'ip_address' => '127.0.0.1']);
        $this->assertSame(1, AktivitasLog::where('modul', $modul)->where('aktivitas', 'like', 'Menambahkan%Contoh Uji')->count());
    }

    #[DataProvider('master')]
    public function test_nama_wajib_diisi_dan_unik(string $kelas, string $rute, string $kolomFk, string $modul): void
    {
        $admin = $this->admin();
        $kelas::factory()->create(['nama' => 'Sudah Ada']);

        $this->actingAs($admin)->postJson(route($rute.'.store'), ['nama' => '   '])
            ->assertStatus(422)->assertJsonValidationErrors('nama');

        $this->actingAs($admin)->postJson(route($rute.'.store'), ['nama' => 'Sudah Ada'])
            ->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    #[DataProvider('master')]
    public function test_nama_milik_data_yang_sudah_dihapus_tetap_ditolak(string $kelas, string $rute, string $kolomFk, string $modul): void
    {
        $model = $kelas::factory()->create(['nama' => 'Pernah Ada']);
        $model->delete();

        $this->actingAs($this->admin())->postJson(route($rute.'.store'), ['nama' => 'Pernah Ada'])
            ->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    #[DataProvider('master')]
    public function test_ubah_data_boleh_mempertahankan_nama_sendiri(string $kelas, string $rute, string $kolomFk, string $modul): void
    {
        $model = $kelas::factory()->create(['nama' => 'Nama Lama']);

        $this->actingAs($this->admin())->putJson(route($rute.'.update', $model->id), ['nama' => 'Nama Lama'])->assertOk();
        $this->actingAs($this->admin())->putJson(route($rute.'.update', $model->id), ['nama' => 'Nama Baru'])->assertOk();

        $this->assertSame('Nama Baru', $model->refresh()->nama);
    }

    #[DataProvider('master')]
    public function test_ubah_ke_nama_milik_data_lain_ditolak(string $kelas, string $rute, string $kolomFk, string $modul): void
    {
        $lain = $kelas::factory()->create(['nama' => 'Milik Lain']);
        $model = $kelas::factory()->create(['nama' => 'Milik Saya']);

        $this->actingAs($this->admin())->putJson(route($rute.'.update', $model->id), ['nama' => 'Milik Lain'])
            ->assertStatus(422)->assertJsonValidationErrors('nama');
        $this->assertNotNull($lain->fresh());
    }

    #[DataProvider('master')]
    public function test_hapus_data_tidak_terpakai_adalah_soft_delete(string $kelas, string $rute, string $kolomFk, string $modul): void
    {
        $model = $kelas::factory()->create(['nama' => 'Akan Dihapus']);

        $this->actingAs($this->admin())->deleteJson(route($rute.'.destroy', $model->id))->assertOk();

        $this->assertSoftDeleted($model);
        $this->assertSame(1, AktivitasLog::where('modul', $modul)->where('aktivitas', 'like', 'Menghapus%Akan Dihapus')->count());
    }

    #[DataProvider('master')]
    public function test_hapus_data_yang_dipakai_barang_ditolak(string $kelas, string $rute, string $kolomFk, string $modul): void
    {
        $model = $kelas::factory()->create();
        Barang::factory()->create([$kolomFk => $model->id]);

        $this->actingAs($this->admin())->deleteJson(route($rute.'.destroy', $model->id))
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($pesan) => str_contains($pesan, 'masih dipakai'));

        $this->assertNotSoftDeleted($model);
    }

    public function test_lokasi_menyimpan_keterangan(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('master.lokasi.store'), ['nama' => 'Rak Z', 'keterangan' => 'Dekat pintu'])
            ->assertCreated();

        $this->assertDatabaseHas('lokasi', ['nama' => 'Rak Z', 'keterangan' => 'Dekat pintu']);
    }

    #[DataProvider('master')]
    public function test_datatables_mengembalikan_data_dan_jumlah_barang(string $kelas, string $rute, string $kolomFk, string $modul): void
    {
        $model = $kelas::factory()->create(['nama' => 'Berisi Barang']);
        Barang::factory()->count(2)->create([$kolomFk => $model->id]);

        $respons = $this->actingAs($this->admin())->getJson(route($rute.'.data', ['draw' => 1, 'start' => 0, 'length' => 10]));

        $respons->assertOk()->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
        $baris = collect($respons->json('data'))->firstWhere('nama', 'Berisi Barang');
        $this->assertNotNull($baris);
        $this->assertSame(2, $baris['jumlah_barang']);
        $this->assertStringContainsString('data-crud-edit', $baris['aksi']);
    }

    public function test_datatables_tidak_menampilkan_data_yang_sudah_dihapus(): void
    {
        KategoriBarang::factory()->create(['nama' => 'Tampil']);
        KategoriBarang::factory()->create(['nama' => 'Tersembunyi'])->delete();

        $respons = $this->actingAs($this->admin())->getJson(route('master.kategori.data', ['draw' => 1, 'start' => 0, 'length' => 10]));

        $nama = collect($respons->json('data'))->pluck('nama')->all();
        $this->assertContains('Tampil', $nama);
        $this->assertNotContains('Tersembunyi', $nama);
    }
}
