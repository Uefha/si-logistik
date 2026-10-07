<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit log. Hanya created_at (diisi otomatis), baris tidak pernah diubah.
 */
class AktivitasLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'aktivitas_log';

    protected $fillable = [
        'user_id',
        'aktivitas',
        'modul',
        'subjek_type',
        'subjek_id',
        'data',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjek(): MorphTo
    {
        return $this->morphTo('subjek');
    }
}
