<?php

namespace Tests\Feature\Transaksi;

use App\Models\AktivitasLog;
use App\Models\Barang;
use App\Models\PenyesuaianStok;
use App\Models\StokMutasi;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenyesuaianStokTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_penyesuaian_menambah_stok_membuat_adj_detail_ledger_dan_audit_atomik(): void
    {
        $barang = Barang::factory()->create(['stok' => '10.25']);
        Carbon::setTestNow(Carbon::parse('2026-10-07 10:00:00', config('app.timezone')));

        try {
            $response = $this->actingAs($this->admin)->postJson(route('penyesuaian.store'), [
                'barang_id' => $barang->id,
                'stok_sistem' => '10.25',
                'stok_fisik' => '13.50',
                'alasan' => 'Hasil stok opname.',
            ]);
        } finally {
            Carbon::setTestNow();
        }

        $response->assertCreated()->assertJsonPath('redirect', route('transaksi.show', 1));
        $this->assertSame('13.50', $barang->refresh()->stok);
        $this->assertDatabaseHas('transactions', ['nomor_transaksi' => 'ADJ-20261007-0001', 'tipe' => 'penyesuaian']);
        $this->assertDatabaseHas('penyesuaian_stok', [
            'barang_id' => $barang->id,
            'stok_sistem' => '10.25',
            'stok_fisik' => '13.50',
            'selisih' => '3.25',
            'alasan' => 'Hasil stok opname.',
        ]);
        $this->assertDatabaseHas('transaction_details', [
            'barang_id' => $barang->id,
            'qty' => '3.25',
            'stok_sebelum' => '10.25',
            'stok_sesudah' => '13.50',
        ]);
        $this->assertDatabaseHas('stok_mutasi', [
            'barang_id' => $barang->id,
            'jenis' => 'penyesuaian',
            'qty_masuk' => '3.25',
            'qty_keluar' => '0.00',
            'stok_sesudah' => '13.50',
        ]);
        $this->assertSame(1, AktivitasLog::where('modul', 'penyesuaian_stok')->count());
    }

    public function test_penyesuaian_mengurangi_stok_dan_mencatat_selisih_negatif(): void
    {
        $barang = Barang::factory()->create(['stok' => '10.50']);

        $this->actingAs($this->admin)->postJson(route('penyesuaian.store'), [
            'barang_id' => $barang->id,
            'stok_sistem' => '10.50',
            'stok_fisik' => '8.25',
            'alasan' => 'Barang rusak.',
        ])->assertCreated();

        $this->assertSame('8.25', $barang->refresh()->stok);
        $this->assertDatabaseHas('penyesuaian_stok', ['barang_id' => $barang->id, 'selisih' => '-2.25']);
        $this->assertDatabaseHas('stok_mutasi', [
            'barang_id' => $barang->id,
            'qty_masuk' => '0.00',
            'qty_keluar' => '2.25',
            'stok_sebelum' => '10.50',
            'stok_sesudah' => '8.25',
        ]);
    }

    public function test_stok_fisik_sama_ditolak_dan_tidak_mencatat_transaksi(): void
    {
        $barang = Barang::factory()->create(['stok' => '5.00']);

        $this->actingAs($this->admin)->postJson(route('penyesuaian.store'), [
            'barang_id' => $barang->id,
            'stok_sistem' => '5.00',
            'stok_fisik' => '5',
            'alasan' => 'Hitung stok.',
        ])->assertUnprocessable()->assertJsonPath('message', 'Stok fisik sama dengan stok sistem. Tidak ada penyesuaian yang perlu disimpan.');

        $this->assertSame('5.00', $barang->refresh()->stok);
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, PenyesuaianStok::count());
        $this->assertSame(0, StokMutasi::count());
    }

    public function test_barang_nonaktif_dan_input_invalid_ditolak(): void
    {
        $barang = Barang::factory()->nonaktif()->create();

        $this->actingAs($this->admin)->postJson(route('penyesuaian.store'), [
            'barang_id' => $barang->id,
            'stok_sistem' => $barang->stok,
            'stok_fisik' => 12,
            'alasan' => 'Hasil hitung.',
        ])->assertUnprocessable()->assertJsonPath('message', 'Barang tidak ditemukan atau sudah nonaktif.');

        $this->postJson(route('penyesuaian.store'), [
            'barang_id' => $barang->id,
            'stok_sistem' => $barang->stok,
            'stok_fisik' => -1,
            'alasan' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors(['stok_fisik', 'alasan']);
        $this->assertSame(0, Transaction::count());
    }

    public function test_penyesuaian_ditolak_jika_stok_berubah_sejak_barang_dipilih(): void
    {
        $barang = Barang::factory()->create(['stok' => 8]);

        $this->actingAs($this->admin)->postJson(route('penyesuaian.store'), [
            'barang_id' => $barang->id,
            'stok_sistem' => 7,
            'stok_fisik' => 10,
            'alasan' => 'Hasil hitung.',
        ])->assertUnprocessable()->assertJsonPath(
            'message',
            'Stok sistem berubah sejak barang dipilih (terbaru: 8). Muat ulang barang dan tinjau ulang penyesuaian.',
        );

        $this->assertSame('8.00', $barang->refresh()->stok);
        $this->assertSame(0, Transaction::count());
    }

    public function test_kartu_stok_riwayat_barang_dan_daftar_penyesuaian_tampil(): void
    {
        $barang = Barang::factory()->create(['stok' => 2]);
        $this->actingAs($this->admin)->postJson(route('penyesuaian.store'), [
            'barang_id' => $barang->id,
            'stok_sistem' => 2,
            'stok_fisik' => 4,
            'alasan' => 'Hasil hitung.',
        ])->assertCreated();
        $transaksi = Transaction::firstOrFail();

        $this->get(route('master.barang.show', $barang))->assertOk()->assertSee('Riwayat transaksi barang')->assertSee($transaksi->nomor_transaksi);
        $this->get(route('master.barang.kartu-stok', $barang))->assertOk()->assertSee('Kartu stok')->assertSee($transaksi->nomor_transaksi);
        $this->get(route('penyesuaian.index'))->assertOk()->assertSee($transaksi->nomor_transaksi)->assertSee('Hasil hitung.');
        $this->get(route('transaksi.show', $transaksi))->assertOk()->assertSee('Rincian penyesuaian stok')->assertSee('Stok fisik');
        $this->get(route('penyesuaian.create'))->assertOk()->assertSee('data-keyboard-target="#cariBarangPenyesuaian"', false);
    }

    public function test_histori_bisa_difilter_berdasarkan_barang_petugas_jenis_dan_periode(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 12:00:00', config('app.timezone')));
        $barang1 = Barang::factory()->create(['stok' => 20]);
        $barang2 = Barang::factory()->create(['stok' => 20]);
        $lain = User::factory()->create();

        try {
            $cocok = $this->actingAs($this->admin)->postJson(route('transaksi.masuk.simpan'), [
                'items' => [['barang_id' => $barang1->id, 'qty' => 1]],
            ])->assertCreated()->json('nomor_transaksi');
            $this->actingAs($lain)->postJson(route('transaksi.masuk.simpan'), [
                'items' => [['barang_id' => $barang2->id, 'qty' => 1]],
            ])->assertCreated();
        } finally {
            Carbon::setTestNow();
        }

        $this->actingAs($this->admin)->get(route('transaksi.index', [
            'tipe' => 'masuk', 'barang_id' => $barang1->id, 'user_id' => $this->admin->id,
            'bulan' => 10, 'tahun' => 2026, 'dari' => '2026-10-01', 'sampai' => '2026-10-31',
        ]))->assertOk()->assertSee($cocok)->assertDontSee('IN-20261007-0002');
    }
}
