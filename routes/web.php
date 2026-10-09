<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\BarangController;
use App\Http\Controllers\Master\KategoriController;
use App\Http\Controllers\Master\LokasiController;
use App\Http\Controllers\Master\SatuanController;
use App\Http\Controllers\KartuStokController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BarangFotoController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PenyesuaianStokController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransaksiController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/foto-barang/{path}', BarangFotoController::class)->where('path', '.*')->name('barang.foto');
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/{jenis}/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');
    Route::get('/laporan/{jenis}/excel', [LaporanController::class, 'excel'])->name('laporan.excel');
    Route::get('/audit-log/data', [AuditLogController::class, 'data'])->name('audit-log.data');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('transaksi')->name('transaksi.')->group(function () {
        Route::get('/', [TransaksiController::class, 'index'])->name('index');
        Route::get('/barang/cari', [TransaksiController::class, 'cariBarang'])->name('barang.cari');
        Route::get('/masuk/cepat', [TransaksiController::class, 'cepatMasuk'])->name('masuk.cepat');
        Route::post('/masuk', [TransaksiController::class, 'simpanMasuk'])->name('masuk.simpan');
        Route::get('/keluar/cepat', [TransaksiController::class, 'cepatKeluar'])->name('keluar.cepat');
        Route::post('/keluar', [TransaksiController::class, 'simpanKeluar'])->name('keluar.simpan');
        Route::get('/{transaksi}', [TransaksiController::class, 'show'])->whereNumber('transaksi')->name('show');
    });

    Route::prefix('penyesuaian-stok')->name('penyesuaian.')->group(function () {
        Route::get('/', [PenyesuaianStokController::class, 'index'])->name('index');
        Route::get('/buat', [PenyesuaianStokController::class, 'create'])->name('create');
        Route::post('/', [PenyesuaianStokController::class, 'store'])->name('store');
    });

    // Master data. Rute `data` harus didaftarkan SEBELUM resource agar tidak tertangkap oleh {parameter}.
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('barang/data', [BarangController::class, 'data'])->name('barang.data');
        Route::get('barang/{barang}/kartu-stok', KartuStokController::class)->whereNumber('barang')->name('barang.kartu-stok');
        Route::resource('barang', BarangController::class)->parameters(['barang' => 'barang']);

        Route::get('kategori/data', [KategoriController::class, 'data'])->name('kategori.data');
        Route::resource('kategori', KategoriController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['kategori' => 'kategori']);

        Route::get('satuan/data', [SatuanController::class, 'data'])->name('satuan.data');
        Route::resource('satuan', SatuanController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['satuan' => 'satuan']);

        Route::get('lokasi/data', [LokasiController::class, 'data'])->name('lokasi.data');
        Route::resource('lokasi', LokasiController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['lokasi' => 'lokasi']);
    });
});

require __DIR__.'/auth.php';
