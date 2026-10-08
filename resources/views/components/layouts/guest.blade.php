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
</head>
<body>
    <div class="container-fluid">
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
            <div class="col-lg-6 d-flex flex-column align-items-center justify-content-center p-4">
                <div class="auth-card w-100">
                    <div class="text-end mb-3"><button type="button" class="btn btn-outline-primary btn-sm d-none" data-pwa-install>Pasang aplikasi</button></div>
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
