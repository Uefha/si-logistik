<x-layouts.app title="Detail Transaksi" :breadcrumbs="['Transaksi' => route('transaksi.index'), 'Riwayat Transaksi' => route('transaksi.index'), $transaksi->nomor_transaksi => null]">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3 transaksi-actions">
        <a href="{{ route('transaksi.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
        <button type="button" class="btn btn-outline-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Cetak</button>
    </div>
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between"><span class="font-monospace fw-bold fs-5">{{ $transaksi->nomor_transaksi }}</span><span class="badge {{ $transaksi->tipe === \App\Enums\TipeTransaksi::Masuk ? 'text-bg-success' : 'text-bg-primary' }}">{{ $transaksi->tipe->label() }}</span></div>
        <div class="card-body"><div class="row g-3">
            <div class="col-sm-4"><small class="text-secondary d-block">Tanggal</small><strong>{{ $transaksi->tanggal->translatedFormat('d F Y') }}</strong></div>
            <div class="col-sm-4"><small class="text-secondary d-block">Petugas</small><strong>{{ $transaksi->user->name }}</strong></div>
            <div class="col-sm-4"><small class="text-secondary d-block">Tujuan / penerima</small><strong>{{ $transaksi->tujuan ?: '—' }}</strong></div>
            @if ($transaksi->keterangan)<div class="col-12"><small class="text-secondary d-block">Keterangan</small>{{ $transaksi->keterangan }}</div>@endif
        </div></div>
    </div>
    @if ($transaksi->penyesuaianStok)
        <div class="card mb-4">
            <div class="card-header bg-white">Rincian penyesuaian stok</div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-3"><small class="text-secondary d-block">Stok sistem</small><strong>{{ \App\Support\Format::angka($transaksi->penyesuaianStok->stok_sistem) }}</strong></div>
                <div class="col-md-3"><small class="text-secondary d-block">Stok fisik</small><strong>{{ \App\Support\Format::angka($transaksi->penyesuaianStok->stok_fisik) }}</strong></div>
                <div class="col-md-3"><small class="text-secondary d-block">Selisih</small><strong>{{ (float) $transaksi->penyesuaianStok->selisih > 0 ? '+' : '' }}{{ \App\Support\Format::angka($transaksi->penyesuaianStok->selisih) }}</strong></div>
                <div class="col-md-3"><small class="text-secondary d-block">Alasan</small><strong>{{ $transaksi->penyesuaianStok->alasan }}</strong></div>
            </div></div>
        </div>
    @endif
    <div class="card"><div class="card-header bg-white">Barang dalam transaksi</div><div class="table-responsive">
        <table class="table align-middle mb-0"><thead><tr><th>Barang</th><th>Kode</th><th class="text-end">Jumlah</th><th class="text-end">Stok sebelum</th><th class="text-end">Stok sesudah</th></tr></thead>
            <tbody>@foreach ($transaksi->details as $detail)
                <tr><td><a href="{{ route('master.barang.show', $detail->barang) }}">{{ $detail->barang->nama_barang }}</a><small class="d-block text-secondary">{{ $detail->barang->kategori?->nama }} · {{ $detail->barang->lokasi?->nama }}</small></td><td>{{ $detail->barang->kode_barang }}</td><td class="text-end">{{ \App\Support\Format::angka($detail->qty) }} {{ $detail->barang->satuan?->nama }}</td><td class="text-end">{{ \App\Support\Format::angka($detail->stok_sebelum) }}</td><td class="text-end fw-semibold">{{ \App\Support\Format::angka($detail->stok_sesudah) }}</td></tr>
            @endforeach</tbody>
        </table>
    </div></div>
</x-layouts.app>
