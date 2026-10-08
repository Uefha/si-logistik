<?php

namespace App\Http\Controllers\Master;

use App\Http\Requests\Master\SatuanRequest;
use App\Models\Satuan;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SatuanController extends MasterController
{
    protected function konfigurasi(): array
    {
        return [
            'kelas' => Satuan::class,
            'judul' => 'Satuan Barang',
            'singular' => 'Satuan',
            'rute' => 'master.satuan',
            'ikon' => 'rulers',
            'punya_keterangan' => false,
        ];
    }

    public function index(): View
    {
        return $this->tampilIndex();
    }

    public function data(): JsonResponse
    {
        return $this->dataTable();
    }

    public function store(SatuanRequest $request): JsonResponse
    {
        return $this->simpan($request->validated());
    }

    public function update(SatuanRequest $request, Satuan $satuan): JsonResponse
    {
        return $this->perbarui($satuan, $request->validated());
    }

    public function destroy(Satuan $satuan): JsonResponse
    {
        return $this->hapus($satuan);
    }
}
