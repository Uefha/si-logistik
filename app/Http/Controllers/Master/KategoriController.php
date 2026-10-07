<?php

namespace App\Http\Controllers\Master;

use App\Http\Requests\Master\KategoriRequest;
use App\Models\KategoriBarang;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class KategoriController extends MasterController
{
    protected function konfigurasi(): array
    {
        return [
            'kelas' => KategoriBarang::class,
            'judul' => 'Kategori Barang',
            'singular' => 'Kategori',
            'rute' => 'master.kategori',
            'ikon' => 'tags',
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

    public function store(KategoriRequest $request): JsonResponse
    {
        return $this->simpan($request->validated());
    }

    public function update(KategoriRequest $request, KategoriBarang $kategori): JsonResponse
    {
        return $this->perbarui($kategori, $request->validated());
    }

    public function destroy(KategoriBarang $kategori): JsonResponse
    {
        return $this->hapus($kategori);
    }
}
