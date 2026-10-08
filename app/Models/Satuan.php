<?php

namespace App\Models;

use Database\Factories\SatuanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Satuan extends Model
{
    /** @use HasFactory<SatuanFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'satuan';

    protected $fillable = ['nama'];

    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class, 'satuan_id');
    }
}
