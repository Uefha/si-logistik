<?php

namespace App\Models;

use App\Enums\JenisMutasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ledger perubahan stok. Baris tidak pernah diubah atau dihapus,
 * sehingga tabel hanya memiliki created_at.
 */
class StokMutasi extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'stok_mutasi';

    protected $fillable = [
        'barang_id',
        'transaction_id',
        'transaction_detail_id',
        'jenis',
        'qty_masuk',
        'qty_keluar',
        'stok_sebelum',
        'stok_sesudah',
        'tanggal',
        'user_id',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisMutasi::class,
            'qty_masuk' => 'decimal:2',
            'qty_keluar' => 'decimal:2',
            'stok_sebelum' => 'decimal:2',
            'stok_sesudah' => 'decimal:2',
            'tanggal' => 'datetime',
        ];
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id')->withTrashed();
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function transactionDetail(): BelongsTo
    {
        return $this->belongsTo(TransactionDetail::class, 'transaction_detail_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
