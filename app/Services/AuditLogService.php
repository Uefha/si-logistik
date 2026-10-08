<?php

namespace App\Services;

use App\Models\AktivitasLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogService
{
    /**
     * Catat satu aktivitas admin.
     *
     * Panggil di dalam DB::transaction() yang sama dengan perubahan datanya,
     * sehingga log ikut dibatalkan bila perubahan gagal.
     *
     * @param  array<string, mixed>  $data  ringkasan data yang diproses (tanpa data sensitif)
     */
    public function catat(string $aktivitas, string $modul, ?Model $subjek = null, array $data = []): AktivitasLog
    {
        $request = request();
        $userAgent = $request->userAgent();

        return AktivitasLog::query()->create([
            'user_id' => Auth::id(),
            'aktivitas' => $aktivitas,
            'modul' => $modul,
            'subjek_type' => $subjek?->getMorphClass(),
            'subjek_id' => $subjek?->getKey(),
            'data' => $data ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => filled($userAgent) ? Str::limit($userAgent, 255, '') : null,
        ]);
    }
}
