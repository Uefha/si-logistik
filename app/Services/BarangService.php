<?php

namespace App\Services;

use App\Enums\JenisMutasi;
use App\Exceptions\AturanBisnisException;
use App\Models\Barang;
use App\Models\StokMutasi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BarangService
{
    /** Kolom yang boleh diisi dari form (stok sengaja tidak termasuk). */
    private const KOLOM_FORM = [
        'kode_barang', 'barcode', 'nama_barang', 'kategori_id', 'satuan_id', 'lokasi_id',
        'stok_minimum', 'harga', 'deskripsi', 'is_active',
    ];

    public function __construct(
        private readonly BarcodeService $barcode,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Buat barang baru beserta catatan stok awal di ledger (stok_mutasi) dan audit log,
     * semuanya dalam satu transaksi database.
     *
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data, ?UploadedFile $foto = null): Barang
    {
        $pathFoto = $foto?->store('barang', 'public');

        try {
            return DB::transaction(function () use ($data, $pathFoto) {
                $atribut = Arr::only($data, self::KOLOM_FORM);
                $atribut['barcode'] = filled($atribut['barcode'] ?? null) ? $atribut['barcode'] : $this->barcode->buatUnik();
                $atribut['is_active'] = $atribut['is_active'] ?? true;
                $stokAwal = $this->angka($data['stok_awal'] ?? 0);

                $barang = new Barang($atribut);
                $barang->foto = $pathFoto;
                $barang->stok = $stokAwal; // satu-satunya tempat stok diisi langsung: stok awal
                $barang->save();

                StokMutasi::query()->create([
                    'barang_id' => $barang->id,
                    'transaction_id' => null,
                    'transaction_detail_id' => null,
                    'jenis' => JenisMutasi::StokAwal,
                    'qty_masuk' => $stokAwal,
                    'qty_keluar' => 0,
                    'stok_sebelum' => 0,
                    'stok_sesudah' => $stokAwal,
                    'tanggal' => now(),
                    'user_id' => Auth::id(),
                    'keterangan' => 'Stok awal',
                ]);

                $this->audit->catat('Menambahkan barang: '.$barang->nama_barang, 'barang', $barang, [
                    'kode_barang' => $barang->kode_barang,
                    'barcode' => $barang->barcode,
                    'stok_awal' => $stokAwal,
                ]);

                return $barang;
            });
        } catch (Throwable $e) {
            if ($pathFoto) {
                Storage::disk('public')->delete($pathFoto);
            }

            throw $e;
        }
    }

    /**
     * Perbarui data barang. Stok TIDAK dapat diubah lewat method ini.
     *
     * @param  array<string, mixed>  $data
     */
    public function perbarui(Barang $barang, array $data, ?UploadedFile $foto = null, bool $hapusFoto = false): Barang
    {
        $fotoLama = $barang->foto;
        $fotoBaru = $foto?->store('barang', 'public');

        try {
            DB::transaction(function () use ($barang, $data, $fotoBaru, $hapusFoto) {
                $atribut = Arr::only($data, self::KOLOM_FORM);
                // Barcode dikosongkan saat edit = pertahankan barcode yang ada.
                if (! filled($atribut['barcode'] ?? null)) {
                    unset($atribut['barcode']);
                }

                $barang->fill($atribut);

                if ($fotoBaru) {
                    $barang->foto = $fotoBaru;
                } elseif ($hapusFoto) {
                    $barang->foto = null;
                }

                $perubahan = $this->ringkasPerubahan($barang);
                $barang->save();

                if ($perubahan !== []) {
                    $this->audit->catat('Mengubah barang: '.$barang->nama_barang, 'barang', $barang, [
                        'kode_barang' => $barang->kode_barang,
                        'perubahan' => $perubahan,
                    ]);
                }
            });
        } catch (Throwable $e) {
            if ($fotoBaru) {
                Storage::disk('public')->delete($fotoBaru);
            }

            throw $e;
        }

        // Berkas lama dihapus hanya setelah perubahan berhasil tersimpan.
        if ($fotoLama && $fotoLama !== $barang->foto) {
            Storage::disk('public')->delete($fotoLama);
        }

        return $barang;
    }

    /**
     * Hapus (soft delete) barang. Histori transaksi tetap tersimpan.
     * Barang yang masih memiliki stok tidak boleh dihapus.
     *
     * @throws AturanBisnisException
     */
    public function hapus(Barang $barang): void
    {
        if ((float) $barang->stok > 0) {
            throw new AturanBisnisException(
                "Barang \"{$barang->nama_barang}\" masih memiliki stok ".$this->tampilkan($barang->stok)
                .'. Nonaktifkan barang ini, atau kosongkan stoknya terlebih dahulu.'
            );
        }

        DB::transaction(function () use ($barang) {
            $barang->delete();

            $this->audit->catat('Menghapus barang: '.$barang->nama_barang, 'barang', $barang, [
                'kode_barang' => $barang->kode_barang,
                'barcode' => $barang->barcode,
            ]);
        });
    }

    /**
     * @return array<string, array{dari: mixed, menjadi: mixed}>
     */
    private function ringkasPerubahan(Barang $barang): array
    {
        $ringkas = [];

        foreach ($barang->getDirty() as $kolom => $baru) {
            $ringkas[$kolom] = $kolom === 'foto'
                ? ['dari' => $barang->getOriginal('foto') ? 'ada' : null, 'menjadi' => $baru ? 'diganti' : 'dihapus']
                : ['dari' => $barang->getOriginal($kolom), 'menjadi' => $baru];
        }

        return $ringkas;
    }

    private function angka(mixed $nilai): string
    {
        return number_format((float) $nilai, 2, '.', '');
    }

    private function tampilkan(mixed $nilai): string
    {
        return rtrim(rtrim(number_format((float) $nilai, 2, ',', '.'), '0'), ',');
    }
}
