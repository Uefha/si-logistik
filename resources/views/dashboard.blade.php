<x-layouts.app title="Dashboard">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold text-primary">Selamat datang, {{ auth()->user()->name }}</h2>
                    <p class="text-secondary mb-3">
                        {{ config('logistik.nama_aplikasi') }} berhasil dipasang. Autentikasi, struktur database, dan data master awal
                        sudah siap. Modul lain akan muncul di menu seiring tahap pembangunan.
                    </p>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Halaman ini sementara. Dashboard monitoring stok dibangun pada Tahap 5.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-hdd-network me-1"></i> Diagnostik setup (Tahap 2)</div>
                <div class="card-body">
                    <dl class="diag-list mb-0">
                        @foreach ($diagnostik as $label => $nilai)
                            <dt>{{ $label }}</dt>
                            <dd>{{ $nilai }}</dd>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
