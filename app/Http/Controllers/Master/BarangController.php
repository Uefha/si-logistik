<?php

namespace App\Http\Controllers\Master;

use App\DataTables\BarangDataTable;
use App\Enums\StatusStok;
use App\Exceptions\AturanBisnisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\BarangRequest;
use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Lokasi;
use App\Models\Satuan;
use App\Models\StokMutasi;
use App\Services\BarangService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class BarangController extends Controller
{
    public function __construct(private readonly BarangService $service)
    {
    }

    public function index(): View
    {
        return view('master.barang.index', $this->daftarPilihan() + ['statusStok' => StatusStok::cases()]);
    }

    public function data(Request $request): JsonResponse
    {
        return BarangDataTable::respon($request);
    }

    public function create(): View
    {
        return view('master.barang.create', $this->daftarPilihan() + [
            'barang' => new Barang(['is_active' => true, 'stok_minimum' => 0]),
        ]);
    }

    public function store(BarangRequest $request): RedirectResponse
    {
        $barang = $this->service->buat($this->dataForm($request), $request->file('foto'));

        return redirect()->route('master.barang.show', $barang)->with('success', 'Barang berhasil ditambahkan.');
    }

    public function show(Barang $barang): View
    {
        $barang->load(['kategori', 'satuan', 'lokasi']);
        $riwayat = StokMutasi::query()
            ->where('barang_id', $barang->id)
            ->with('transaction')
            ->latest('tanggal')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('master.barang.show', ['barang' => $barang, 'riwayat' => $riwayat]);
    }

    public function edit(Barang $barang): View
    {
        return view('master.barang.edit', $this->daftarPilihan() + ['barang' => $barang]);
    }

    public function update(BarangRequest $request, Barang $barang): RedirectResponse
    {
        $this->service->perbarui(
            $barang,
            $this->dataForm($request),
            $request->file('foto'),
            $request->boolean('hapus_foto'),
        );

        return redirect()->route('master.barang.show', $barang)->with('success', 'Barang berhasil diperbarui.');
    }

    /**
     * Hapus lewat AJAX dari dialog konfirmasi. Bila `redirect=1` dikirim (dari halaman detail),
     * respons memuat URL tujuan dan pesan sukses disimpan sebagai flash untuk halaman berikutnya.
     */
    public function destroy(Request $request, Barang $barang): JsonResponse
    {
        try {
            $this->service->hapus($barang);
        } catch (AturanBisnisException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($request->boolean('redirect')) {
            session()->flash('success', 'Barang berhasil dihapus.');

            return response()->json([
                'message' => 'Barang berhasil dihapus.',
                'redirect' => route('master.barang.index'),
            ]);
        }

        return response()->json(['message' => 'Barang berhasil dihapus.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(BarangRequest $request): array
    {
        return Arr::except($request->validated(), ['foto', 'hapus_foto']);
    }

    /**
     * @return array<string, mixed>
     */
    private function daftarPilihan(): array
    {
        return [
            'daftarKategori' => KategoriBarang::query()->orderBy('nama')->get(['id', 'nama']),
            'daftarSatuan' => Satuan::query()->orderBy('nama')->get(['id', 'nama']),
            'daftarLokasi' => Lokasi::query()->orderBy('nama')->get(['id', 'nama']),
        ];
    }
}
