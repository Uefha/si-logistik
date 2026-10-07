<?php

namespace App\Services;

use App\Exceptions\AturanBisnisException;
use App\Models\KategoriBarang;
use App\Models\Lokasi;
use App\Models\Satuan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CRUD sederhana untuk master bernama unik: Kategori, Satuan, Lokasi.
 * Menjalankan aturan hapus (tidak boleh jika masih dipakai barang aktif) dan audit log.
 */
class MasterDataService
{
    /** Kelas model => [kunci modul untuk audit, nama untuk pesan]. */
    private const DAFTAR = [
        KategoriBarang::class => ['kategori', 'kategori'],
        Satuan::class => ['satuan', 'satuan'],
        Lokasi::class => ['lokasi', 'lokasi'],
    ];

    public function __construct(private readonly AuditLogService $audit)
    {
    }

    /**
     * @param  class-string<Model>  $kelas
     * @param  array<string, mixed>  $data
     */
    public function buat(string $kelas, array $data): Model
    {
        [$modul, $nama] = $this->info($kelas);

        return DB::transaction(function () use ($kelas, $data, $modul, $nama) {
            $model = $kelas::query()->create($data);

            $this->audit->catat("Menambahkan {$nama}: {$model->nama}", $modul, $model, ['nama' => $model->nama]);

            return $model;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(Model $model, array $data): Model
    {
        [$modul, $nama] = $this->info($model::class);

        return DB::transaction(function () use ($model, $data, $modul, $nama) {
            $model->fill($data);

            $perubahan = [];
            foreach ($model->getDirty() as $kolom => $baru) {
                $perubahan[$kolom] = ['dari' => $model->getOriginal($kolom), 'menjadi' => $baru];
            }

            $model->save();

            if ($perubahan !== []) {
                $this->audit->catat("Mengubah {$nama}: {$model->nama}", $modul, $model, ['perubahan' => $perubahan]);
            }

            return $model;
        });
    }

    /**
     * @throws AturanBisnisException
     */
    public function hapus(Model $model): void
    {
        [$modul, $nama] = $this->info($model::class);

        $dipakai = $model->barang()->count(); // barang yang sudah di-soft-delete tidak dihitung

        if ($dipakai > 0) {
            throw new AturanBisnisException(
                ucfirst($nama)." \"{$model->nama}\" masih dipakai oleh {$dipakai} barang, sehingga tidak dapat dihapus."
            );
        }

        DB::transaction(function () use ($model, $modul, $nama) {
            $model->delete();

            $this->audit->catat("Menghapus {$nama}: {$model->nama}", $modul, $model, ['nama' => $model->nama]);
        });
    }

    /**
     * @param  class-string<Model>  $kelas
     * @return array{0: string, 1: string}
     */
    private function info(string $kelas): array
    {
        return self::DAFTAR[$kelas] ?? throw new InvalidArgumentException("Model {$kelas} bukan master data.");
    }
}
