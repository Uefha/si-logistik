<?php

namespace App\Models;

use Database\Factories\LokasiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lokasi extends Model
{
    /** @use HasFactory<LokasiFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'lokasi';

    protected $fillable = ['nama', 'keterangan'];

    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class, 'lokasi_id');
    }
}
