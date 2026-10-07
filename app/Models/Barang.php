<?php

namespace App\Models;

use App\Enums\StatusStok;
use Database\Factories\BarangFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
    /** @use HasFactory<BarangFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'barang';

    /**
     * `stok` sengaja TIDAK ada di $fillable: stok hanya boleh diubah oleh
     * service stok (BarangService saat stok awal, StokService pada transaksi),
     * sehingga tidak bisa ikut terisi lewat mass assignment dari input form.
     */
    protected $fillable = [
        'kode_barang',
        'barcode',
        'nama_barang',
        'kategori_id',
        'satuan_id',
        'lokasi_id',
        'stok_minimum',
        'harga',
        'deskripsi',
        'foto',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stok' => 'decimal:2',
            'stok_minimum' => 'decimal:2',
            'harga' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ----------------------------------------------------------------- relasi

    // withTrashed: barang yang sudah ada tetap menampilkan master-nya
    // walaupun master tersebut kemudian di-soft-delete.
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class, 'kategori_id')->withTrashed();
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id')->withTrashed();
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id')->withTrashed();
    }

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class, 'barang_id');
    }

    public function stokMutasi(): HasMany
    {
        return $this->hasMany(StokMutasi::class, 'barang_id');
    }

    // ---------------------------------------------------------------- atribut

    /** Status stok dihitung dinamis (tidak disimpan). */
    protected function statusStok(): Attribute
    {
        return Attribute::get(fn (): StatusStok => StatusStok::dari($this->stok, $this->stok_minimum));
    }

    /**
     * URL foto, atau null bila belum ada foto. Memakai asset() (bukan APP_URL) agar tetap benar
     * ketika aplikasi dibuka lewat alamat IP jaringan sekolah.
     */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->foto ? asset('storage/'.$this->foto) : null);
    }

    // ------------------------------------------------------------------ scope

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** AMAN: stok > stok_minimum. */
    public function scopeStokAman(Builder $query): Builder
    {
        return $query->whereColumn('stok', '>', 'stok_minimum');
    }

    /** MENIPIS: stok > 0 dan stok <= stok_minimum. */
    public function scopeStokMenipis(Builder $query): Builder
    {
        return $query->where('stok', '>', 0)->whereColumn('stok', '<=', 'stok_minimum');
    }

    /** HABIS: stok = 0. */
    public function scopeStokHabis(Builder $query): Builder
    {
        return $query->where('stok', '<=', 0);
    }

    /**
     * Catatan: bukan bernama `scopeStatusStok` karena akan bentrok dengan accessor
     * `statusStok()` di atas (pemanggilan statis memilih accessor, bukan scope).
     */
    public function scopeFilterStatusStok(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            StatusStok::Aman->value => $query->stokAman(),
            StatusStok::Menipis->value => $query->stokMenipis(),
            StatusStok::Habis->value => $query->stokHabis(),
            default => $query,
        };
    }

    /**
     * Pencarian bebas: barcode, kode, nama, kategori, lokasi, satuan.
     * Karakter wildcard LIKE dari input pengguna di-escape.
     */
    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        $kata = trim((string) $kata);

        if ($kata === '') {
            return $query;
        }

        $like = '%'.addcslashes($kata, '\\%_').'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('barang.kode_barang', 'like', $like)
                ->orWhere('barang.barcode', 'like', $like)
                ->orWhere('barang.nama_barang', 'like', $like)
                ->orWhereHas('kategori', fn (Builder $k) => $k->where('nama', 'like', $like))
                ->orWhereHas('lokasi', fn (Builder $l) => $l->where('nama', 'like', $like))
                ->orWhereHas('satuan', fn (Builder $s) => $s->where('nama', 'like', $like));
        });
    }

    /**
     * Terapkan filter daftar barang. Kunci yang dikenali:
     * kategori_id, lokasi_id, satuan_id, status_stok, is_active.
     *
     * @param  array<string, mixed>  $filter
     */
    public function scopeFilter(Builder $query, array $filter): Builder
    {
        foreach (['kategori_id', 'lokasi_id', 'satuan_id'] as $kolom) {
            if (filled($filter[$kolom] ?? null)) {
                $query->where($kolom, (int) $filter[$kolom]);
            }
        }

        if (in_array($filter['is_active'] ?? null, ['0', '1', 0, 1, true, false], true)) {
            $query->where('is_active', (bool) $filter['is_active']);
        }

        return $query->filterStatusStok($filter['status_stok'] ?? null);
    }
}
