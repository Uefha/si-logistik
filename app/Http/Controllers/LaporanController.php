<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Models\KategoriBarang;
use App\Models\Barang;
use App\Models\Lokasi;
use App\Models\User;
use App\Services\LaporanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(Request $request, LaporanService $laporan): View
    {
        $filter = $this->validasi($request);
        $jenis = $request->query('jenis', 'stok');
        abort_unless(in_array($jenis, LaporanService::JENIS, true), 404);

        return view('laporan.index', [
            'jenis' => $jenis,
            'judul' => $laporan->judul($jenis),
            'headers' => $laporan->headers($jenis),
            'baris' => $laporan->data($jenis, $filter),
            'filter' => $filter,
            'barang' => Barang::withTrashed()->orderBy('nama_barang')->get(['id', 'kode_barang', 'nama_barang']),
            'kategori' => KategoriBarang::orderBy('nama')->get(['id', 'nama']),
            'lokasi' => Lokasi::orderBy('nama')->get(['id', 'nama']),
            'petugas' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function pdf(Request $request, string $jenis, LaporanService $laporan)
    {
        abort_unless(in_array($jenis, LaporanService::JENIS, true), 404);
        $filter = $this->validasi($request);
        $pdf = Pdf::loadView('laporan.pdf', [
            'judul' => $laporan->judul($jenis), 'headers' => $laporan->headers($jenis),
            'baris' => $laporan->data($jenis, $filter), 'filter' => $filter,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-'.$jenis.'-'.now()->format('Ymd-His').'.pdf');
    }

    public function excel(Request $request, string $jenis, LaporanService $laporan)
    {
        abort_unless(in_array($jenis, LaporanService::JENIS, true), 404);
        $filter = $this->validasi($request);

        return Excel::download(new LaporanExport($laporan->data($jenis, $filter), $laporan->headers($jenis)), 'laporan-'.$jenis.'-'.now()->format('Ymd-His').'.xlsx');
    }

    private function validasi(Request $request): array
    {
        $filter = $request->validate([
            'dari' => ['nullable', 'date'], 'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'periode' => ['nullable', 'in:hari,minggu,bulan,tahun,kustom'],
            'barang_id' => ['nullable', 'integer', 'exists:barang,id'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategori_barang,id'],
            'lokasi_id' => ['nullable', 'integer', 'exists:lokasi,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status_stok' => ['nullable', 'in:aman,menipis,habis'],
            'q' => ['nullable', 'string', 'max:50'],
        ]);
        if (($filter['periode'] ?? null) !== 'kustom') {
            $sekarang = now(config('app.timezone'));
            [$dari, $sampai] = match ($filter['periode'] ?? 'kustom') {
                'hari' => [$sekarang->copy()->startOfDay(), $sekarang->copy()->endOfDay()],
                'minggu' => [$sekarang->copy()->startOfWeek(), $sekarang->copy()->endOfWeek()],
                'bulan' => [$sekarang->copy()->startOfMonth(), $sekarang->copy()->endOfMonth()],
                'tahun' => [$sekarang->copy()->startOfYear(), $sekarang->copy()->endOfYear()],
                default => [null, null],
            };
            if ($dari) {
                $filter['dari'] = $dari->toDateString();
                $filter['sampai'] = $sampai->toDateString();
            }
        }

        return $filter;
    }
}
