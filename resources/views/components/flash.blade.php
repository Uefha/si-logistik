@php
    $warnaToast = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'primary'];
@endphp
<div class="toast-container position-fixed top-0 end-0 p-3" aria-live="polite" aria-atomic="true">
    @foreach ($warnaToast as $kunci => $warna)
        @if (session()->has($kunci))
            <div class="toast align-items-center text-bg-{{ $warna }} border-0" role="alert" data-bs-delay="4500">
                <div class="d-flex">
                    <div class="toast-body">{{ session($kunci) }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
                </div>
            </div>
        @endif
    @endforeach
</div>
