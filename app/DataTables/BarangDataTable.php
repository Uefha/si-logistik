<?php

namespace App\DataTables;

use App\Models\Barang;
use App\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Respons server-side DataTables untuk daftar barang: pencarian, filter, urutan, dan paginasi di server.
 */
class BarangDataTable
{
    public static function respon(Request $request): JsonResponse
    {
        $query = Barang::query()
            ->with(['kategori', 'satuan', 'lokasi'])
            ->select('barang.*')
            ->filter($request->only(['kategori_id', 'lokasi_id', 'satuan_id', 'status_stok', 'is_active']));

        $total = (clone $query)->count();
        // Pencarian bebas: kode, barcode, nama, kategori, lokasi, satuan.
        $query->cari($request->input('search.value'));
        $filtered = (clone $query)->count();

        $sortable = ['kode_barang', 'barcode', 'nama_barang', 'stok', 'stok_minimum'];
        $orderIndex = (int) $request->input('order.0.column', -1);
        $orderField = $request->input("columns.$orderIndex.data");
        if (in_array($orderField, $sortable, true)) {
            $direction = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($orderField, $direction);
        } else {
            $query->orderBy('nama_barang');
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->skip($start)->take(min($length, 500));
        }

        $data = $query->get()->values()->map(fn (Barang $b, int $index) => [
            'DT_RowIndex' => $start + $index + 1,
            'kode_barang' => $b->kode_barang,
            'barcode' => $b->barcode,
            'nama_barang' => view('master.barang._nama', ['barang' => $b])->render(),
            'kategori_nama' => $b->kategori?->nama,
            'lokasi_nama' => $b->lokasi?->nama,
            'stok' => Format::angka($b->stok).' '.($b->satuan?->nama ?? ''),
            'stok_minimum' => Format::angka($b->stok_minimum),
            'status' => view('components.badge-stok', ['status' => $b->status_stok])->render(),
            'aksi' => view('master.barang._aksi', ['barang' => $b])->render(),
        ]);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }
}
