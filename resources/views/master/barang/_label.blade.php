<div class="barcode-label border rounded bg-white p-3" data-barcode-label>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="flex-grow-1 text-center">
            <div class="fw-bold">{{ $barang->nama_barang }}</div>
            <small>{{ $barang->kode_barang }}</small>
            <svg class="d-block mx-auto mt-2" data-barcode-code="{{ $barang->barcode }}" aria-label="Barcode {{ $barang->barcode }}"></svg>
            <small class="font-monospace">{{ $barang->barcode }}</small>
        </div>
        <div class="text-center">
            <canvas width="160" height="160" data-qr-code="{{ $barang->barcode }}" aria-label="QR {{ $barang->barcode }}"></canvas>
            <small class="d-block">QR barang</small>
        </div>
    </div>
</div>
