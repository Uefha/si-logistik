@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | ' : '' }}{{ config('logistik.nama_singkat') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="container-fluid">
        <div class="row g-0 min-vh-100">
            <div class="col-lg-6 d-none d-lg-flex auth-hero">
                <div class="hero-icon"><i class="bi bi-box-seam"></i></div>
                <h2 class="display-6">{{ config('logistik.nama_aplikasi') }}</h2>
                <p class="fs-5">{{ config('logistik.nama_instansi') }}</p>
                <p>Pendataan barang, stok realtime, dan transaksi cepat dengan scan barcode.</p>
                <ul>
                    <li><i class="bi bi-check-circle-fill"></i> Stok selalu akurat dan tercatat</li>
                    <li><i class="bi bi-check-circle-fill"></i> Barang masuk dan keluar ala kasir</li>
                    <li><i class="bi bi-check-circle-fill"></i> Laporan PDF dan Excel</li>
                </ul>
            </div>
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-4">
                <div class="auth-card w-100">
                    <div class="d-lg-none text-center mb-4">
                        <div class="fs-1 text-primary"><i class="bi bi-box-seam"></i></div>
                        <div class="fw-bold fs-5 text-primary">{{ config('logistik.nama_singkat') }}</div>
                        <div class="text-secondary small">{{ config('logistik.nama_instansi') }}</div>
                    </div>
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
    <x-flash />
</body>
</html>
