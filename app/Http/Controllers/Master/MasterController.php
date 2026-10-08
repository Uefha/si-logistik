<?php

namespace App\Http\Controllers\Master;

use App\DataTables\MasterDataTable;
use App\Exceptions\AturanBisnisException;
use App\Http\Controllers\Controller;
use App\Services\MasterDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Dasar controller master bernama (kategori, satuan, lokasi).
 * Tambah, ubah, dan hapus dikerjakan lewat AJAX dari modal sehingga mengembalikan JSON.
 */
abstract class MasterController extends Controller
{
    public function __construct(protected readonly MasterDataService $service)
    {
    }

    /**
     * @return array{kelas: class-string<Model>, judul: string, singular: string, rute: string, ikon: string, punya_keterangan: bool}
     */
    abstract protected function konfigurasi(): array;

    protected function tampilIndex(): View
    {
        $cfg = $this->konfigurasi();

        $kolom = [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '60px'],
            ['data' => 'nama', 'name' => 'nama'],
        ];
        if ($cfg['punya_keterangan']) {
            $kolom[] = ['data' => 'keterangan', 'name' => 'keterangan', 'defaultContent' => ''];
        }
        $kolom[] = ['data' => 'jumlah_barang', 'name' => 'jumlah_barang', 'searchable' => false, 'className' => 'text-center'];
        $kolom[] = ['data' => 'aksi', 'name' => 'aksi', 'orderable' => false, 'searchable' => false, 'className' => 'text-end text-nowrap'];

        return view('master.simple.index', [
            'cfg' => $cfg,
            'kolom' => $kolom,
            'idTabel' => 'tabel-'.$cfg['rute'],
            'dataUrl' => route($cfg['rute'].'.data'),
            'storeUrl' => route($cfg['rute'].'.store'),
        ]);
    }

    protected function dataTable(): JsonResponse
    {
        return MasterDataTable::respon($this->konfigurasi());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function simpan(array $data): JsonResponse
    {
        $cfg = $this->konfigurasi();
        $this->service->buat($cfg['kelas'], $data);

        return response()->json(['message' => $cfg['singular'].' berhasil ditambahkan.'], 201);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function perbarui(Model $model, array $data): JsonResponse
    {
        $this->service->perbarui($model, $data);

        return response()->json(['message' => $this->konfigurasi()['singular'].' berhasil diperbarui.']);
    }

    protected function hapus(Model $model): JsonResponse
    {
        try {
            $this->service->hapus($model);
        } catch (AturanBisnisException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => $this->konfigurasi()['singular'].' berhasil dihapus.']);
    }
}
