<?php

namespace Tests\Feature;

use App\Models\AktivitasLog;
use App\Models\Barang;
use App\Models\StokMutasi;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanDashboardAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_menampilkan_ringkasan_grafik_dan_peringatan_stok(): void
    {
        $admin = User::factory()->create();
        Barang::factory()->menipis()->create(['nama_barang' => 'Kertas A4']);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()->assertSee('Total Stok')->assertSee('Mutasi Stok 12 Bulan Terakhir')->assertSee('Jumlah Transaksi per Bulan')
            ->assertSee('Kertas A4')->assertSee('Peringatan Stok');
    }

    public function test_laporan_stok_dan_export_pdf_excel_berjalan(): void
    {
        $admin = User::factory()->create();
        Barang::factory()->create(['nama_barang' => 'Barang Laporan Tahap Enam']);
        $this->actingAs($admin);

        $this->get(route('laporan.index', ['jenis' => 'stok']))->assertOk()->assertSee('Barang Laporan Tahap Enam');
        $this->get(route('laporan.pdf', ['jenis' => 'stok']))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('laporan.excel', ['jenis' => 'stok']))->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_laporan_mutasi_menerapkan_filter_barang_dan_periode(): void
    {
        $admin = User::factory()->create();
        $barang = Barang::factory()->create(['nama_barang' => 'Filter Mutasi']);
        $transaksi = Transaction::create(['nomor_transaksi' => 'IN-20261007-0001', 'tipe' => 'masuk', 'tanggal' => now()->toDateString(), 'user_id' => $admin->id]);
        $detail = TransactionDetail::create(['transaction_id' => $transaksi->id, 'barang_id' => $barang->id, 'qty' => 3, 'stok_sebelum' => 4, 'stok_sesudah' => 7]);
        StokMutasi::create(['barang_id' => $barang->id, 'transaction_id' => $transaksi->id, 'transaction_detail_id' => $detail->id, 'jenis' => 'masuk', 'qty_masuk' => 3, 'qty_keluar' => 0, 'stok_sebelum' => 4, 'stok_sesudah' => 7, 'tanggal' => now(), 'user_id' => $admin->id]);

        $this->actingAs($admin)->get(route('laporan.index', ['jenis' => 'mutasi', 'barang_id' => $barang->id, 'periode' => 'bulan']))
            ->assertOk()->assertSee('Filter Mutasi')->assertSee('IN-20261007-0001');
    }

    public function test_audit_log_mendukung_filter_server_side_dan_escape_html(): void
    {
        $admin = User::factory()->create();
        AktivitasLog::create(['user_id' => $admin->id, 'aktivitas' => '<script>alert(1)</script>', 'modul' => 'master', 'data' => [
            'kode_barang' => '8888888',
            'perubahan' => ['foto' => ['dari' => 'ada', 'menjadi' => 'diganti']],
        ]]);

        $this->actingAs($admin)->getJson(route('audit-log.data', ['draw' => 1, 'start' => 0, 'length' => 10, 'modul' => 'master']))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.aktivitas', '&lt;script&gt;alert(1)&lt;/script&gt;')
            ->assertJsonPath('data.0.detail', 'Kode barang: 8888888. Perubahan: Foto berubah dari ada menjadi diganti.');
    }
}
