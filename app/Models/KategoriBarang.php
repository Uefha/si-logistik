<?php

namespace App\Models;

use Database\Factories\KategoriBarangFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriBarang extends Model
{
    /** @use HasFactory<KategoriBarangFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'kategori_barang';

    protected $fillable = ['nama'];

    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class, 'kategori_id');
    }
}
