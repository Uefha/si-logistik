<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\StokMutasi;
use Illuminate\View\View;

class KartuStokController extends Controller
{
    public function __invoke(Barang $barang): View
    {
        $mutasi = StokMutasi::query()
            ->where('barang_id', $barang->id)
            ->with(['transaction', 'user'])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->paginate(50);

        return view('master.barang.kartu-stok', [
            'barang' => $barang->load(['satuan', 'kategori', 'lokasi']),
            'mutasi' => $mutasi,
        ]);
    }
}
