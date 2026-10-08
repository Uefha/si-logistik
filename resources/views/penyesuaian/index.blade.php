<x-layouts.app title="Daftar Penyesuaian Stok" :breadcrumbs="['Transaksi' => route('transaksi.index'), 'Penyesuaian Stok' => null]">
    <div class="d-flex flex-wrap gap-2 mb-3"><a href="{{ route('penyesuaian.create') }}" class="btn btn-warning"><i class="bi bi-plus-circle me-1"></i> Penyesuaian Baru</a><a href="{{ route('transaksi.index', ['tipe' => 'penyesuaian']) }}" class="btn btn-outline-primary">Semua transaksi penyesuaian</a></div>
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Tanggal</th><th>Nomor</th><th>Barang</th><th class="text-end">Stok Sistem</th><th class="text-end">Stok Fisik</th><th class="text-end">Selisih</th><th>Alasan</th><th>Petugas</th></tr></thead>
        <tbody>
            @forelse ($penyesuaian as $item)
                <tr><td>{{ $item->tanggal->translatedFormat('d M Y') }}</td><td class="font-monospace"><a href="{{ route('transaksi.show', $item->transaction) }}">{{ $item->transaction->nomor_transaksi }}</a></td><td>{{ $item->barang->kode_barang }} — {{ $item->barang->nama_barang }}</td><td class="text-end">{{ \App\Support\Format::angka($item->stok_sistem) }}</td><td class="text-end">{{ \App\Support\Format::angka($item->stok_fisik) }}</td><td class="text-end fw-semibold">{{ (float) $item->selisih > 0 ? '+' : '' }}{{ \App\Support\Format::angka($item->selisih) }}</td><td>{{ $item->alasan }}</td><td>{{ $item->user->name }}</td></tr>
            @empty
                <tr><td colspan="8" class="text-center text-secondary py-5">Belum ada penyesuaian stok.</td></tr>
            @endforelse
        </tbody>
    </table></div>@if ($penyesuaian->hasPages())<div class="card-footer bg-white">{{ $penyesuaian->links() }}</div>@endif</div>
</x-layouts.app>
