<x-layouts.app title="Kartu Stok" :breadcrumbs="['Master Data' => null, 'Data Barang' => route('master.barang.index'), $barang->nama_barang => route('master.barang.show', $barang), 'Kartu Stok' => null]">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 transaksi-actions">
        <div><strong>{{ $barang->kode_barang }} — {{ $barang->nama_barang }}</strong><div class="text-secondary small">{{ $barang->kategori?->nama }} · {{ $barang->lokasi?->nama }}</div></div>
        <div class="d-flex gap-2"><a href="{{ route('master.barang.show', $barang) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Detail barang</a><button class="btn btn-outline-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i> Cetak kartu stok</button></div>
    </div>
    <div class="card">
        <div class="card-header bg-white">
            <div class="fw-semibold">Kartu stok</div>
            <small class="text-secondary">Saldo dicatat sesuai urutan mutasi. Satuan: {{ $barang->satuan?->nama }}</small>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Tanggal</th><th>Transaksi</th><th>Keterangan</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th><th class="text-end">Saldo</th></tr></thead>
            <tbody>
                @forelse ($mutasi as $item)
                    <tr>
                        <td>{{ $item->tanggal->translatedFormat('d M Y H:i') }}</td>
                        <td class="font-monospace">@if ($item->transaction)<a href="{{ route('transaksi.show', $item->transaction) }}">{{ $item->transaction->nomor_transaksi }}</a>@else<span class="text-secondary">Stok Awal</span>@endif</td>
                        <td>{{ $item->jenis->label() }}@if ($item->keterangan)<small class="d-block text-secondary">{{ $item->keterangan }}</small>@endif</td>
                        <td class="text-end">{{ (float) $item->qty_masuk > 0 ? \App\Support\Format::angka($item->qty_masuk) : '—' }}</td>
                        <td class="text-end">{{ (float) $item->qty_keluar > 0 ? \App\Support\Format::angka($item->qty_keluar) : '—' }}</td>
                        <td class="text-end fw-semibold">{{ \App\Support\Format::angka($item->stok_sesudah) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-5">Belum ada mutasi stok untuk barang ini.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($mutasi->hasPages())<div class="card-footer bg-white transaksi-actions">{{ $mutasi->links() }}</div>@endif
    </div>
</x-layouts.app>
