<?php

namespace App\Http\Controllers\Master;

use App\Http\Requests\Master\LokasiRequest;
use App\Models\Lokasi;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LokasiController extends MasterController
{
    protected function konfigurasi(): array
    {
        return [
            'kelas' => Lokasi::class,
            'judul' => 'Lokasi Penyimpanan',
            'singular' => 'Lokasi',
            'rute' => 'master.lokasi',
            'ikon' => 'geo-alt',
            'punya_keterangan' => true,
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

    public function store(LokasiRequest $request): JsonResponse
    {
        return $this->simpan($request->validated());
    }

    public function update(LokasiRequest $request, Lokasi $lokasi): JsonResponse
    {
        return $this->perbarui($lokasi, $request->validated());
    }

    public function destroy(Lokasi $lokasi): JsonResponse
    {
        return $this->hapus($lokasi);
    }
}
