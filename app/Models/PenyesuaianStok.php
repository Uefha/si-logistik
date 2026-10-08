<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenyesuaianStok extends Model
{
    protected $table = 'penyesuaian_stok';

    protected $fillable = [
        'transaction_id',
        'barang_id',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'alasan',
        'tanggal',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'stok_sistem' => 'decimal:2',
            'stok_fisik' => 'decimal:2',
            'selisih' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
