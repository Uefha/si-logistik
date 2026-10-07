<x-layouts.app title="Dashboard">
    <div class="row g-3 mb-4">
        @php($cards = [
            ['Jenis Barang', $ringkasan['jenis_barang'], 'box-seam', 'primary'],
            ['Total Stok', \App\Support\Format::angka($ringkasan['total_stok']), 'boxes', 'info'],
            ['Barang Masuk Hari Ini', \App\Support\Format::angka($ringkasan['masuk_hari_ini']), 'box-arrow-in-down', 'success'],
            ['Barang Keluar Hari Ini', \App\Support\Format::angka($ringkasan['keluar_hari_ini']), 'box-arrow-up', 'warning'],
            ['Transaksi Bulan Ini', $ringkasan['transaksi_bulan_ini'], 'arrow-left-right', 'primary'],
            ['Stok Perlu Perhatian', $ringkasan['stok_kritis'], 'exclamation-triangle', 'danger'],
        ])
        @foreach ($cards as [$label, $nilai, $ikon, $warna])
            <div class="col-6 col-xl-2"><div class="card h-100 dashboard-stat border-start border-4 border-{{ $warna }}"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start"><span class="small text-secondary">{{ $label }}</span><i class="bi bi-{{ $ikon }} text-{{ $warna }} fs-5"></i></div>
                <div class="h3 fw-bold mb-0 mt-2">{{ $nilai }}</div>
            </div></div></div>
        @endforeach
    </div>
    <div class="row g-3 mb-4">
        <div class="col-xl-8"><div class="card h-100"><div class="card-header">Mutasi Stok 12 Bulan Terakhir</div><div class="card-body"><div class="chart-wrap"><canvas id="chartMutasi" data-labels="{{ json_encode($grafik['labels']) }}" data-masuk="{{ json_encode($grafik['masuk']) }}" data-keluar="{{ json_encode($grafik['keluar']) }}" aria-label="Grafik barang masuk dan keluar per bulan"></canvas></div></div></div></div>
        <div class="col-xl-4"><div class="card h-100"><div class="card-header">Stok per Kategori</div><div class="card-body"><div class="chart-wrap chart-wrap-pie"><canvas id="chartKategori" data-labels="{{ json_encode($grafik['kategori']) }}" data-values="{{ json_encode($grafik['stok_kategori']) }}" aria-label="Grafik stok per kategori"></canvas></div></div></div></div>
    </div>
    <div class="row g-3 mb-4"><div class="col-xl-8"><div class="card"><div class="card-header">Jumlah Transaksi per Bulan</div><div class="card-body"><div class="chart-wrap"><canvas id="chartTransaksi" data-labels="{{ json_encode($grafik['labels']) }}" data-values="{{ json_encode($grafik['transaksi']) }}" aria-label="Grafik jumlah transaksi per bulan"></canvas></div></div></div></div></div>
    <div class="row g-3">
        <div class="col-xl-6"><div class="card h-100"><div class="card-header d-flex justify-content-between"><span><i class="bi bi-bell me-1 text-warning"></i> Peringatan Stok</span><span class="badge text-bg-danger">{{ $notifikasiCount }}</span></div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Barang</th><th>Lokasi</th><th class="text-end">Stok</th><th>Status</th></tr></thead><tbody>
            @forelse ($peringatanStok as $barang)<tr><td><a href="{{ route('master.barang.show', $barang) }}">{{ $barang->nama_barang }}</a><div class="small text-secondary">{{ $barang->kode_barang }}</div></td><td>{{ $barang->lokasi?->nama ?? '—' }}</td><td class="text-end">{{ \App\Support\Format::angka($barang->stok) }} {{ $barang->satuan?->nama }}</td><td><span class="badge text-bg-{{ $barang->statusStok->warna() }}">{{ $barang->statusStok->label() }}</span></td></tr>
            @empty<tr><td colspan="4" class="text-center text-secondary py-4">Semua stok berada di atas batas minimum.</td></tr>@endforelse
            </tbody></table></div></div></div>
        <div class="col-xl-6"><div class="card h-100"><div class="card-header">Mutasi Terbaru</div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Waktu</th><th>Barang</th><th>Jenis</th><th class="text-end">Jumlah</th></tr></thead><tbody>
            @forelse ($mutasiTerbaru as $mutasi)<tr><td class="text-nowrap">{{ $mutasi->tanggal->format('d/m H:i') }}</td><td>{{ $mutasi->barang?->nama_barang ?? '—' }}</td><td>{{ $mutasi->jenis->label() }}</td><td class="text-end">{{ \App\Support\Format::angka((float) $mutasi->qty_masuk ?: (float) $mutasi->qty_keluar) }}</td></tr>
            @empty<tr><td colspan="4" class="text-center text-secondary py-4">Belum ada mutasi stok.</td></tr>@endforelse
            </tbody></table></div></div></div>
    </div>
</x-layouts.app>
