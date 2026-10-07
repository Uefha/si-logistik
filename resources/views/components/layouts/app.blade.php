@props(['title' => 'Dashboard', 'breadcrumbs' => []])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | {{ config('logistik.nama_singkat') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="{{ request()->routeIs('master.barang.show') ? 'halaman-detail-barang' : '' }}">
    <div class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
        <div class="offcanvas-header d-lg-none">
            <h5 class="offcanvas-title" id="appSidebarLabel">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <a href="{{ route('dashboard') }}" class="app-brand">
                <span class="app-brand-icon"><i class="bi bi-box-seam"></i></span>
                <span>
                    <strong>{{ config('logistik.nama_singkat') }}</strong>
                    <small>{{ config('logistik.nama_instansi') }}</small>
                </span>
            </a>

            <nav class="app-nav" aria-label="Menu utama">
                <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="speedometer2">
                    Dashboard
                </x-sidebar-link>

                <div class="nav-heading">Master Data</div>
                <x-sidebar-link :href="route('master.barang.index')" :active="request()->routeIs('master.barang.*')" icon="box-seam">
                    Data Barang
                </x-sidebar-link>
                <x-sidebar-link :href="route('master.kategori.index')" :active="request()->routeIs('master.kategori.*')" icon="tags">
                    Kategori Barang
                </x-sidebar-link>
                <x-sidebar-link :href="route('master.satuan.index')" :active="request()->routeIs('master.satuan.*')" icon="rulers">
                    Satuan
                </x-sidebar-link>
                <x-sidebar-link :href="route('master.lokasi.index')" :active="request()->routeIs('master.lokasi.*')" icon="geo-alt">
                    Lokasi Penyimpanan
                </x-sidebar-link>

                <div class="nav-heading">Transaksi Stok</div>
                <x-sidebar-link :href="route('transaksi.masuk.cepat')" :active="request()->routeIs('transaksi.masuk.*')" icon="box-arrow-in-down">
                    Barang Masuk
                </x-sidebar-link>
                <x-sidebar-link :href="route('transaksi.keluar.cepat')" :active="request()->routeIs('transaksi.keluar.*')" icon="box-arrow-up">
                    Barang Keluar
                </x-sidebar-link>
                <x-sidebar-link :href="route('transaksi.index')" :active="request()->routeIs('transaksi.index', 'transaksi.show')" icon="clock-history">
                    Riwayat Transaksi
                </x-sidebar-link>
                <x-sidebar-link :href="route('penyesuaian.create')" :active="request()->routeIs('penyesuaian.*')" icon="clipboard2-check">
                    Penyesuaian Stok
                </x-sidebar-link>

                <div class="nav-heading">Pengaturan</div>
                <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" icon="person-gear">
                    Profil Admin
                </x-sidebar-link>
            </nav>
        </div>
    </div>

    <div class="app-main">
        <header class="app-topbar">
            <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Buka menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="fw-semibold text-secondary d-none d-sm-block">{{ config('logistik.nama_aplikasi') }}</div>

            <div class="dropdown ms-auto">
                <button class="btn btn-light border d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5 text-primary"></i>
                    <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">{{ auth()->user()->email }}</h6></li>
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Profil Admin</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="app-content">
            <div class="page-head mb-4">
                <h1 class="h4 fw-bold mb-1">{{ $title }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
                        @foreach ($breadcrumbs as $label => $url)
                            @if ($loop->last)
                                <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                            @elseif ($url)
                                <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                            @else
                                <li class="breadcrumb-item">{{ $label }}</li>
                            @endif
                        @endforeach
                    </ol>
                </nav>
            </div>

            {{ $slot }}
        </main>

        <footer class="app-footer">
            &copy; {{ now()->year }} {{ config('logistik.nama_instansi') }} &middot; {{ config('logistik.nama_singkat') }}
        </footer>
    </div>

    <x-flash />
    <x-confirm-modal />
</body>
</html>
