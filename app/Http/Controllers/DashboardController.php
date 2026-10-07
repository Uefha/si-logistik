<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\StokMutasi;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $hariIni = now(config('app.timezone'))->toDateString();
        $awalBulan = now(config('app.timezone'))->startOfMonth()->toDateString();
        $barangAktif = Barang::query()->aktif();
        $labels = collect(range(11, 0))->map(fn (int $i) => now(config('app.timezone'))->subMonths($i)->translatedFormat('M Y'));
        $labelsKey = collect(range(11, 0))->map(fn (int $i) => now(config('app.timezone'))->subMonths($i)->format('Y-m'));
        $periodeSql = DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y-%m', tanggal)" : "DATE_FORMAT(tanggal, '%Y-%m')";
        $bulanan = StokMutasi::query()
            ->selectRaw("{$periodeSql} as periode, SUM(qty_masuk) as masuk, SUM(qty_keluar) as keluar")
            ->where('tanggal', '>=', now(config('app.timezone'))->subMonths(11)->startOfMonth())
            ->whereIn('jenis', ['masuk', 'keluar'])
            ->groupBy('periode')->get()->keyBy('periode');
        $transaksiBulanan = Transaction::query()
            ->selectRaw("{$periodeSql} as periode, COUNT(*) as jumlah")
            ->where('tanggal', '>=', now(config('app.timezone'))->subMonths(11)->startOfMonth()->toDateString())
            ->groupBy('periode')->get()->keyBy('periode');
        $kategori = DB::table('kategori_barang as k')
            ->join('barang as b', 'b.kategori_id', '=', 'k.id')
            ->whereNull('b.deleted_at')->where('b.is_active', true)
            ->select('k.nama', DB::raw('SUM(b.stok) as stok'))
            ->groupBy('k.id', 'k.nama')->orderBy('k.nama')->get();

        return view('dashboard', [
            'ringkasan' => [
                'jenis_barang' => (clone $barangAktif)->count(),
                'total_stok' => (clone $barangAktif)->sum('stok'),
                'masuk_hari_ini' => StokMutasi::whereDate('tanggal', $hariIni)->where('jenis', 'masuk')->sum('qty_masuk'),
                'keluar_hari_ini' => StokMutasi::whereDate('tanggal', $hariIni)->where('jenis', 'keluar')->sum('qty_keluar'),
                'transaksi_bulan_ini' => Transaction::whereDate('tanggal', '>=', $awalBulan)->count(),
                'stok_kritis' => (clone $barangAktif)->whereColumn('stok', '<=', 'stok_minimum')->count(),
            ],
            'grafik' => [
                'labels' => $labels->values(),
                'masuk' => $labelsKey->map(fn ($key) => (float) ($bulanan[$key]->masuk ?? 0))->values(),
                'keluar' => $labelsKey->map(fn ($key) => (float) ($bulanan[$key]->keluar ?? 0))->values(),
                'transaksi' => $labelsKey->map(fn ($key) => (int) ($transaksiBulanan[$key]->jumlah ?? 0))->values(),
                'kategori' => $kategori->pluck('nama')->values(),
                'stok_kategori' => $kategori->map(fn ($item) => (float) $item->stok)->values(),
            ],
            'peringatanStok' => (clone $barangAktif)->with(['satuan', 'lokasi'])->whereColumn('stok', '<=', 'stok_minimum')->orderBy('stok')->limit(8)->get(),
            'mutasiTerbaru' => StokMutasi::with(['barang', 'user'])->latest('tanggal')->latest('id')->limit(8)->get(),
            'notifikasiCount' => (clone $barangAktif)->whereColumn('stok', '<=', 'stok_minimum')->count(),
        ]);
    }
}
