<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = ['key', 'value'];

    /** Ambil nilai pengaturan berdasarkan kunci. */
    public static function ambil(string $key, ?string $bawaan = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $bawaan;
    }

    /** Simpan (buat atau perbarui) nilai pengaturan. */
    public static function simpan(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
