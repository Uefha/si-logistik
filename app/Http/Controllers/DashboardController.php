<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Halaman sementara. Dashboard monitoring dibangun pada Tahap 5;
     * untuk saat ini hanya menampilkan diagnostik setup agar mudah diverifikasi.
     */
    public function __invoke(): View
    {
        $diagnostik = [
            'Aplikasi' => config('logistik.nama_aplikasi'),
            'Instansi' => config('logistik.nama_instansi'),
            'Laravel' => app()->version(),
            'PHP' => PHP_VERSION,
            'Database' => config('database.default').' / '.DB::connection()->getDatabaseName(),
            'Zona waktu' => config('app.timezone'),
            'Bahasa' => app()->getLocale(),
            'Waktu server' => now()->translatedFormat('l, d F Y H:i:s'),
        ];

        return view('dashboard', ['diagnostik' => $diagnostik]);
    }
}
