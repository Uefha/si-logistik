<div class="d-flex align-items-center gap-2">
    @if ($barang->foto_url)
        <img src="{{ $barang->foto_url }}" alt="" width="36" height="36" class="rounded border object-fit-cover" loading="lazy">
    @else
        <span class="d-inline-grid place-items-center rounded border bg-light text-secondary" style="width:36px;height:36px;display:inline-grid;place-items:center;"><i class="bi bi-image"></i></span>
    @endif
    <span>
        {{ $barang->nama_barang }}
        @unless ($barang->is_active)
            <span class="badge text-bg-secondary ms-1">Nonaktif</span>
        @endunless
    </span>
</div>
