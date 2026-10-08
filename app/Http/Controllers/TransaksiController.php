<?php

namespace App\Http\Controllers;

use App\Enums\TipeTransaksi;
use App\Exceptions\AturanBisnisException;
use App\Http\Requests\TransaksiRequest;
use App\Models\Barang;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StokService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransaksiController extends Controller
{
    public function __construct(private readonly StokService $stok)
    {
    }

    public function index(Request $request): View
    {
        $filter = $request->validate([
            'tipe' => ['nullable', 'in:masuk,keluar,penyesuaian'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'q' => ['nullable', 'string', 'max:30'],
            'barang_id' => ['nullable', 'integer', 'exists:barang,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $query = Transaction::query()->with('user')->withCount('details')->latest('tanggal')->latest('id');

        if (filled($filter['tipe'] ?? null)) {
            $query->where('tipe', $filter['tipe']);
        }
        if (filled($filter['dari'] ?? null)) {
            $query->whereDate('tanggal', '>=', $filter['dari']);
        }
        if (filled($filter['sampai'] ?? null)) {
            $query->whereDate('tanggal', '<=', $filter['sampai']);
        }
        if (filled($filter['q'] ?? null)) {
            $query->where('nomor_transaksi', 'like', '%'.addcslashes($filter['q'], '\\%_').'%');
        }
        if (filled($filter['barang_id'] ?? null)) {
            $query->whereHas('details', fn ($detail) => $detail->where('barang_id', $filter['barang_id']));
        }
        if (filled($filter['user_id'] ?? null)) {
            $query->where('user_id', $filter['user_id']);
        }
        if (filled($filter['bulan'] ?? null)) {
            $query->whereMonth('tanggal', $filter['bulan']);
        }
        if (filled($filter['tahun'] ?? null)) {
            $query->whereYear('tanggal', $filter['tahun']);
        }

        return view('transaksi.index', [
            'transaksi' => $query->paginate(20)->withQueryString(),
            'daftarBarang' => Barang::withTrashed()->orderBy('nama_barang')->get(['id', 'nama_barang', 'kode_barang']),
            'daftarPetugas' => User::orderBy('name')->get(['id', 'name']),
            'jenisTransaksi' => TipeTransaksi::cases(),
        ]);
    }

    public function cepatMasuk(): View
    {
        return $this->halamanCepat(TipeTransaksi::Masuk);
    }

    public function cepatKeluar(): View
    {
        return $this->halamanCepat(TipeTransaksi::Keluar);
    }

    public function cariBarang(Request $request): JsonResponse
    {
        $kata = trim((string) $request->query('q', ''));
        if ($kata === '') {
            return response()->json(['data' => []]);
        }

        $barang = Barang::query()
            ->aktif()
            ->with(['kategori', 'satuan', 'lokasi'])
            ->where(function ($query) use ($kata) {
                $query->where('barcode', $kata)
                    ->orWhere('kode_barang', 'like', '%'.addcslashes($kata, '\\%_').'%')
                    ->orWhere('nama_barang', 'like', '%'.addcslashes($kata, '\\%_').'%');
            })
            ->orderByRaw('CASE WHEN barcode = ? THEN 0 WHEN kode_barang = ? THEN 1 ELSE 2 END', [$kata, $kata])
            ->limit(10)
            ->get();

        return response()->json(['data' => $barang->map(fn (Barang $item) => [
            'id' => $item->id,
            'kode_barang' => $item->kode_barang,
            'barcode' => $item->barcode,
            'nama_barang' => $item->nama_barang,
            'kategori' => $item->kategori?->nama,
            'satuan' => $item->satuan?->nama,
            'lokasi' => $item->lokasi?->nama,
            'stok' => $item->stok,
            'stok_format' => \App\Support\Format::angka($item->stok),
        ])]);
    }

    public function simpanMasuk(TransaksiRequest $request): RedirectResponse|JsonResponse
    {
        return $this->simpan($request, TipeTransaksi::Masuk);
    }

    public function simpanKeluar(TransaksiRequest $request): RedirectResponse|JsonResponse
    {
        return $this->simpan($request, TipeTransaksi::Keluar);
    }

    public function show(Transaction $transaksi): View
    {
        $transaksi->load(['user', 'details.barang.kategori', 'details.barang.satuan', 'details.barang.lokasi', 'penyesuaianStok.barang']);

        return view('transaksi.show', ['transaksi' => $transaksi]);
    }

    private function halamanCepat(TipeTransaksi $tipe): View
    {
        return view('transaksi.cepat', [
            'tipe' => $tipe,
            'simpanUrl' => route($tipe === TipeTransaksi::Masuk ? 'transaksi.masuk.simpan' : 'transaksi.keluar.simpan'),
            'pencarianUrl' => route('transaksi.barang.cari'),
        ]);
    }

    private function simpan(TransaksiRequest $request, TipeTransaksi $tipe): RedirectResponse|JsonResponse
    {
        try {
            $transaksi = $this->stok->simpan(
                $tipe,
                $request->validated('items'),
                $request->user(),
                $request->validated('tujuan'),
                $request->validated('keterangan'),
            );
        } catch (AturanBisnisException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['transaksi' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Transaksi berhasil disimpan.',
                'redirect' => route('transaksi.show', $transaksi),
                'nomor_transaksi' => $transaksi->nomor_transaksi,
            ], 201);
        }

        return redirect()->route('transaksi.show', $transaksi)->with('success', 'Transaksi berhasil disimpan.');
    }
}
