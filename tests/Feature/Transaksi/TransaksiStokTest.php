<?php

namespace Tests\Feature\Transaksi;

use App\Models\AktivitasLog;
use App\Models\Barang;
use App\Models\StokMutasi;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiStokTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_barang_masuk_mengubah_stok_dan_mencatat_satu_ledger_per_barang(): void
    {
        $barang = Barang::factory()->create(['stok' => '10.25']);

        $respons = $this->actingAs($this->admin)->postJson(route('transaksi.masuk.simpan'), [
            'items' => [
                ['barang_id' => $barang->id, 'qty' => '2.50'],
                ['barang_id' => $barang->id, 'qty' => '1.25'],
            ],
            'keterangan' => 'Restok rutin',
        ]);

        $respons->assertCreated()->assertJsonPath('nomor_transaksi', fn ($nomor) => preg_match('/^IN-\d{8}-0001$/', $nomor) === 1);
        $this->assertSame('14.00', $barang->refresh()->stok);
        $this->assertSame(1, Transaction::count());
        $this->assertSame(1, TransactionDetail::count());
        $this->assertSame(1, StokMutasi::where('jenis', 'masuk')->count());
        $this->assertDatabaseHas('transaction_details', [
            'barang_id' => $barang->id,
            'qty' => '3.75',
            'stok_sebelum' => '10.25',
            'stok_sesudah' => '14.00',
        ]);
        $this->assertSame(1, AktivitasLog::where('modul', 'transaksi')->count());
    }

    public function test_barang_keluar_mengurangi_stok_dan_menyimpan_tujuan(): void
    {
        $barang = Barang::factory()->create(['stok' => '10.50']);

        $this->actingAs($this->admin)->postJson(route('transaksi.keluar.simpan'), [
            'items' => [['barang_id' => $barang->id, 'qty' => '2.25']],
            'tujuan' => 'Ruang kelas',
        ])->assertCreated();

        $this->assertSame('8.25', $barang->refresh()->stok);
        $this->assertDatabaseHas('transactions', ['tipe' => 'keluar', 'tujuan' => 'Ruang kelas']);
        $this->assertDatabaseHas('stok_mutasi', [
            'barang_id' => $barang->id,
            'jenis' => 'keluar',
            'qty_keluar' => '2.25',
            'stok_sesudah' => '8.25',
        ]);
    }

    public function test_stok_kurang_menolak_transaksi_dengan_pesan_tepat_dan_tidak_mengubah_data(): void
    {
        $barang = Barang::factory()->create(['stok' => '2.50']);

        $this->actingAs($this->admin)->postJson(route('transaksi.keluar.simpan'), [
            'items' => [['barang_id' => $barang->id, 'qty' => '3']],
            'tujuan' => 'Ruang kelas',
        ])->assertUnprocessable()->assertJsonPath('message', 'Stok tidak mencukupi. Stok tersedia: 2,5.');

        $this->assertSame('2.50', $barang->refresh()->stok);
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, TransactionDetail::count());
        $this->assertSame(0, StokMutasi::where('jenis', 'keluar')->count());
        $this->assertSame(0, AktivitasLog::where('modul', 'transaksi')->count());
    }

    public function test_transaksi_multi_barang_gagal_secara_atomik_jika_satu_barang_kurang(): void
    {
        $cukup = Barang::factory()->create(['stok' => 10]);
        $kurang = Barang::factory()->create(['stok' => 1]);

        $this->actingAs($this->admin)->postJson(route('transaksi.keluar.simpan'), [
            'items' => [
                ['barang_id' => $cukup->id, 'qty' => 2],
                ['barang_id' => $kurang->id, 'qty' => 2],
            ],
            'tujuan' => 'Gudang lain',
        ])->assertUnprocessable();

        $this->assertSame('10.00', $cukup->refresh()->stok);
        $this->assertSame('1.00', $kurang->refresh()->stok);
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, StokMutasi::whereIn('jenis', ['masuk', 'keluar'])->count());
    }

    public function test_tujuan_wajib_untuk_barang_keluar(): void
    {
        $barang = Barang::factory()->create();

        $this->actingAs($this->admin)->postJson(route('transaksi.keluar.simpan'), [
            'items' => [['barang_id' => $barang->id, 'qty' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('tujuan');
    }

    public function test_nomor_berurutan_per_jenis_dan_kembali_ke_awal_pada_tanggal_baru(): void
    {
        $barang = Barang::factory()->create(['stok' => 20]);
        Carbon::setTestNow(Carbon::parse('2026-10-07 12:00:00', config('app.timezone')));

        try {
            $pertama = $this->actingAs($this->admin)->postJson(route('transaksi.masuk.simpan'), [
                'items' => [['barang_id' => $barang->id, 'qty' => 1]],
            ])->assertCreated()->json('nomor_transaksi');
            $kedua = $this->postJson(route('transaksi.masuk.simpan'), [
                'items' => [['barang_id' => $barang->id, 'qty' => 1]],
            ])->assertCreated()->json('nomor_transaksi');
            $keluar = $this->postJson(route('transaksi.keluar.simpan'), [
                'items' => [['barang_id' => $barang->id, 'qty' => 1]],
                'tujuan' => 'Ruang kelas',
            ])->assertCreated()->json('nomor_transaksi');

            Carbon::setTestNow(Carbon::parse('2026-10-08 00:01:00', config('app.timezone')));
            $besok = $this->postJson(route('transaksi.masuk.simpan'), [
                'items' => [['barang_id' => $barang->id, 'qty' => 1]],
            ])->assertCreated()->json('nomor_transaksi');

            $this->assertSame('IN-20261007-0001', $pertama);
            $this->assertSame('IN-20261007-0002', $kedua);
            $this->assertSame('OUT-20261007-0001', $keluar);
            $this->assertSame('IN-20261008-0001', $besok);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_halaman_pos_daftar_dan_detail_transaksi_tampil(): void
    {
        $barang = Barang::factory()->create();
        $this->actingAs($this->admin)->get(route('transaksi.masuk.cepat'))
            ->assertOk()
            ->assertSee('Pindai atau cari barang')
            ->assertSee('data-keyboard-target="#inputBarcode"', false);
        $this->actingAs($this->admin)->get(route('transaksi.keluar.cepat'))->assertOk()->assertSee('Tujuan atau penerima');
        $this->actingAs($this->admin)->postJson(route('transaksi.masuk.simpan'), [
            'items' => [['barang_id' => $barang->id, 'qty' => 1]],
        ])->assertCreated();
        $transaksi = Transaction::firstOrFail();
        $this->actingAs($this->admin)->get(route('transaksi.index'))->assertOk()->assertSee($transaksi->nomor_transaksi);
        $this->actingAs($this->admin)->get(route('transaksi.show', $transaksi))->assertOk()->assertSee($barang->nama_barang);
    }
}
