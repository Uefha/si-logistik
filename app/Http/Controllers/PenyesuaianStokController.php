<?php

namespace App\Http\Controllers;

use App\Exceptions\AturanBisnisException;
use App\Http\Requests\PenyesuaianStokRequest;
use App\Models\PenyesuaianStok;
use App\Services\StokService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PenyesuaianStokController extends Controller
{
    public function __construct(private readonly StokService $stok)
    {
    }

    public function index(): View
    {
        return view('penyesuaian.index', [
            'penyesuaian' => PenyesuaianStok::query()
                ->with(['barang' => fn ($query) => $query->withTrashed(), 'user', 'transaction'])
                ->latest('tanggal')
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('penyesuaian.create', ['pencarianUrl' => route('transaksi.barang.cari')]);
    }

    public function store(PenyesuaianStokRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $transaksi = $this->stok->sesuaikan(
                \App\Models\Barang::query()->findOrFail($request->validated('barang_id')),
                $request->validated('stok_sistem'),
                $request->validated('stok_fisik'),
                $request->validated('alasan'),
                $request->user(),
            );
        } catch (AturanBisnisException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['penyesuaian' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Penyesuaian stok berhasil disimpan.',
                'redirect' => route('transaksi.show', $transaksi),
            ], 201);
        }

        return redirect()->route('transaksi.show', $transaksi)->with('success', 'Penyesuaian stok berhasil disimpan.');
    }
}
