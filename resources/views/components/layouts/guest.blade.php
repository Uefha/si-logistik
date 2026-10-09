@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f2c5c">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/logo-sekolah-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-sekolah.png') }}">
    <title>{{ $title ? $title.' | ' : '' }}{{ config('logistik.nama_singkat') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        .auth-page { background: linear-gradient(rgba(12, 31, 62, .08), rgba(12, 31, 62, .18)), url('{{ asset('images/login-background.png') }}') center / cover fixed; }
        .auth-hero { background: linear-gradient(145deg, rgba(15, 44, 92, .72), rgba(31, 78, 156, .40)); }
        .auth-panel { position: relative; background: transparent; }
        .auth-card { max-width: 27rem; padding: 2.25rem; border: 1px solid rgba(255, 255, 255, .75); border-radius: 1.5rem; background: rgba(255, 255, 255, .95); box-shadow: 0 1.5rem 4rem rgba(9, 29, 58, .24); }
        .auth-panel footer { position: absolute; right: 1.25rem; bottom: 1.25rem; z-index: 1; max-width: calc(100% - 2.5rem); margin: 0 !important; padding: .45rem .9rem; border-radius: 999px; color: #fff !important; background: rgba(12, 31, 62, .68); text-align: right; }
        @media (max-width: 991.98px) { .auth-panel { min-height: 100vh; background: rgba(10, 32, 66, .12); } .auth-card { padding: 1.75rem; } }
    </style>
</head>
<body>
    <div class="container-fluid auth-page">
        <div class="row g-0 min-vh-100">
            <div class="col-lg-6 d-none d-lg-flex auth-hero">
                <img class="auth-logo" src="{{ asset('images/logo-sekolah.png') }}" alt="Logo {{ config('logistik.nama_instansi') }}">
                <h2 class="display-6">{{ config('logistik.nama_aplikasi') }}</h2>
                <p class="fs-5">{{ config('logistik.nama_instansi') }}</p>
                <p>Pendataan barang, stok realtime, dan transaksi cepat dengan scan barcode.</p>
                <ul>
                    <li><i class="bi bi-check-circle-fill"></i> Stok selalu akurat dan tercatat</li>
                    <li><i class="bi bi-check-circle-fill"></i> Pendataan Barang masuk dan keluar</li>
                    <li><i class="bi bi-check-circle-fill"></i> Laporan PDF dan Excel</li>
                </ul>
            </div>
            <div class="col-lg-6 d-flex flex-column align-items-center justify-content-center p-4 auth-panel">
                <div class="auth-card w-100">
                    <div class="d-lg-none text-center mb-4">
                        <img class="auth-logo-mobile" src="{{ asset('images/logo-sekolah.png') }}" alt="Logo {{ config('logistik.nama_instansi') }}">
                        <div class="fw-bold fs-5 text-primary">{{ config('logistik.nama_singkat') }}</div>
                        <div class="text-secondary small">{{ config('logistik.nama_instansi') }}</div>
                    </div>
                    {{ $slot }}
                </div>
                <footer class="text-secondary small text-center mt-4">&copy; {{ now()->year }} Muhammad Nur Fadila &middot; {{ config('logistik.nama_instansi') }}</footer>
            </div>
        </div>
    </div>
    <x-flash />
</body>
</html>
