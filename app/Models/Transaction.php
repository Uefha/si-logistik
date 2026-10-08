<?php

namespace App\Models;

use App\Enums\TipeTransaksi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'nomor_transaksi',
        'tipe',
        'tanggal',
        'user_id',
        'tujuan',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tipe' => TipeTransaksi::class,
            'tanggal' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class, 'transaction_id');
    }

    public function penyesuaianStok(): HasOne
    {
        return $this->hasOne(PenyesuaianStok::class, 'transaction_id');
    }
}
