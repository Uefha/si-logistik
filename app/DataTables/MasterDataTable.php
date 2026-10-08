<?php

namespace App\DataTables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

/**
 * Respons server-side DataTables untuk master bernama (kategori, satuan, lokasi).
 */
class MasterDataTable
{
    /**
     * @param  array{kelas: class-string<Model>, rute: string, singular: string, punya_keterangan: bool}  $cfg
     */
    public static function respon(array $cfg): JsonResponse
    {
        $kelas = $cfg['kelas'];

        // withCount memakai relasi barang() sehingga barang yang sudah di-soft-delete tidak dihitung.
        $query = $kelas::query()->withCount('barang');

        $request = request();
        $total = (clone $query)->count();
        $kata = trim((string) $request->input('search.value', ''));
        if ($kata !== '') {
            $query->where('nama', 'like', '%'.addcslashes($kata, '\\%_').'%');
        }
        $filtered = (clone $query)->count();

        $arah = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $kolomIndex = (int) $request->input('order.0.column', 1);
        $namaKolom = $request->input("columns.$kolomIndex.data", 'nama');
        $query->orderBy($namaKolom === 'jumlah_barang' ? 'barang_count' : 'nama', $arah);

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->skip($start)->take(min($length, 500));
        }

        $data = $query->get()->values()->map(function (Model $model, int $index) use ($cfg, $start) {
            $row = [
                'DT_RowIndex' => $start + $index + 1,
                'nama' => $model->nama,
                'jumlah_barang' => (int) $model->barang_count,
                'aksi' => view('master.simple._aksi', ['model' => $model, 'cfg' => $cfg])->render(),
            ];
            if ($cfg['punya_keterangan']) {
                $row['keterangan'] = $model->keterangan;
            }
            return $row;
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }
}
