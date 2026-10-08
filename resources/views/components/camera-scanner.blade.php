@props(['id', 'keyboardTarget'])

<div data-camera-widget data-keyboard-target="{{ $keyboardTarget }}" class="mt-3">
    <button type="button" class="btn btn-outline-primary" data-camera-start>
        <i class="bi bi-camera-video me-1"></i> Pindai dengan kamera
    </button>
    <div class="d-none mt-3 rounded border bg-light p-3" data-camera-panel>
        <div id="{{ $id }}" data-camera-reader class="mx-auto" style="max-width: 520px"></div>
        <p class="small text-secondary mt-2 mb-2" data-camera-status role="status" aria-live="polite">Kamera belum aktif.</p>
        <button type="button" class="btn btn-danger btn-sm" data-camera-stop disabled><i class="bi bi-camera-video-off me-1"></i> Selesai</button>
    </div>
    <p class="form-text mb-0">Pemindaian kamera memerlukan HTTPS di jaringan sekolah. Scanner USB tetap dapat dipakai bersamaan.</p>
</div>
