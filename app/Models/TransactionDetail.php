<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    protected $table = 'transaction_details';

    protected $fillable = [
        'transaction_id',
        'barang_id',
        'qty',
        'stok_sebelum',
        'stok_sesudah',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'stok_sebelum' => 'decimal:2',
            'stok_sesudah' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    // withTrashed: histori transaksi tetap menampilkan barang yang sudah dihapus.
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id')->withTrashed();
    }
}
