<x-layouts.app title="Riwayat Transaksi" :breadcrumbs="['Transaksi' => null, 'Riwayat Transaksi' => null]">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('transaksi.masuk.cepat') }}" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Barang Masuk</a>
        <a href="{{ route('transaksi.keluar.cepat') }}" class="btn btn-primary"><i class="bi bi-dash-circle me-1"></i> Barang Keluar</a>
        <a href="{{ route('penyesuaian.create') }}" class="btn btn-outline-warning"><i class="bi bi-clipboard2-check me-1"></i> Penyesuaian Stok</a>
        <a href="{{ route('penyesuaian.index') }}" class="btn btn-outline-secondary"><i class="bi bi-list-check me-1"></i> Daftar Penyesuaian</a>
    </div>
    <div class="card mb-4"><div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label" for="tipe">Jenis transaksi</label><select class="form-select" name="tipe" id="tipe"><option value="">Semua jenis</option>@foreach ($jenisTransaksi as $jenis)<option value="{{ $jenis->value }}" @selected(request('tipe') === $jenis->value)>{{ $jenis->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="barang_id">Barang</label><select class="form-select" name="barang_id" id="barang_id"><option value="">Semua barang</option>@foreach ($daftarBarang as $barang)<option value="{{ $barang->id }}" @selected((string) request('barang_id') === (string) $barang->id)>{{ $barang->kode_barang }} — {{ $barang->nama_barang }}@if ($barang->trashed()) (dihapus)@endif</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="user_id">Admin / petugas</label><select class="form-select" name="user_id" id="user_id"><option value="">Semua petugas</option>@foreach ($daftarPetugas as $petugas)<option value="{{ $petugas->id }}" @selected((string) request('user_id') === (string) $petugas->id)>{{ $petugas->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="q">Nomor transaksi</label><input class="form-control" name="q" id="q" value="{{ request('q') }}" placeholder="Cari nomor"></div>
            <div class="col-md-2"><label class="form-label" for="dari">Dari tanggal</label><input class="form-control" type="date" name="dari" id="dari" value="{{ request('dari') }}"></div>
            <div class="col-md-2"><label class="form-label" for="sampai">Sampai tanggal</label><input class="form-control" type="date" name="sampai" id="sampai" value="{{ request('sampai') }}"></div>
            <div class="col-md-2"><label class="form-label" for="bulan">Bulan</label><select class="form-select" name="bulan" id="bulan"><option value="">Semua</option>@foreach (range(1, 12) as $bulan)<option value="{{ $bulan }}" @selected((string) request('bulan') === (string) $bulan)>{{ \Illuminate\Support\Carbon::create()->month($bulan)->translatedFormat('F') }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label" for="tahun">Tahun</label><input class="form-control" type="number" min="2000" max="2100" name="tahun" id="tahun" value="{{ request('tahun') }}" placeholder="YYYY"></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i> Filter</button><a class="btn btn-outline-secondary" href="{{ route('transaksi.index') }}" aria-label="Hapus filter"><i class="bi bi-x-lg"></i></a></div>
        </form>
    </div></div>
    <div class="card"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Nomor</th><th>Tanggal</th><th>Jenis</th><th>Tujuan / penerima</th><th>Petugas</th><th class="text-center">Jenis barang</th><th></th></tr></thead>
            <tbody>
                @forelse ($transaksi as $item)
                    <tr>
                        <td class="font-monospace fw-semibold"><a href="{{ route('transaksi.show', $item) }}">{{ $item->nomor_transaksi }}</a></td>
                        <td>{{ $item->tanggal->translatedFormat('d M Y') }}</td>
                        <td><span class="badge {{ match ($item->tipe) { \App\Enums\TipeTransaksi::Masuk => 'text-bg-success', \App\Enums\TipeTransaksi::Keluar => 'text-bg-primary', default => 'text-bg-warning' } }}">{{ $item->tipe->label() }}</span></td>
                        <td>{{ $item->tujuan ?: '—' }}</td>
                        <td>{{ $item->user->name }}</td>
                        <td class="text-center">{{ $item->details_count }}</td>
                        <td class="text-end"><a href="{{ route('transaksi.show', $item) }}" class="btn btn-sm btn-outline-secondary">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-5">Belum ada transaksi sesuai filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($transaksi->hasPages())<div class="card-footer bg-white">{{ $transaksi->links() }}</div>@endif
    </div>
</x-layouts.app>
