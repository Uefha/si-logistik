<x-layouts.app title="Laporan" :breadcrumbs="['Laporan' => null, $judul => null]">
    <div class="card mb-3"><div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach (['stok' => 'Stok Barang', 'masuk' => 'Barang Masuk', 'keluar' => 'Barang Keluar', 'mutasi' => 'Mutasi Stok'] as $key => $label)
                <a class="btn {{ $jenis === $key ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('laporan.index', ['jenis' => $key]) }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="row g-3 align-items-end" id="filterLaporan">
            <input type="hidden" name="jenis" value="{{ $jenis }}">
            @if ($jenis !== 'stok')<div class="col-sm-6 col-lg-2"><label class="form-label" for="periode">Periode</label><select class="form-select" name="periode" id="periode"><option value="kustom" @selected(($filter['periode'] ?? 'kustom') === 'kustom')>Kustom / Semua</option>@foreach (['hari'=>'Hari ini','minggu'=>'Minggu ini','bulan'=>'Bulan ini','tahun'=>'Tahun ini'] as $key => $label)<option value="{{ $key }}" @selected(($filter['periode'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-sm-6 col-lg-2"><label class="form-label" for="dari">Dari</label><input class="form-control" type="date" name="dari" id="dari" value="{{ $filter['dari'] ?? '' }}"></div>
            <div class="col-sm-6 col-lg-2"><label class="form-label" for="sampai">Sampai</label><input class="form-control" type="date" name="sampai" id="sampai" value="{{ $filter['sampai'] ?? '' }}"></div>@endif
            <div class="col-sm-6 col-lg-2"><label class="form-label" for="barang_id">Barang</label><select class="form-select" name="barang_id" id="barang_id"><option value="">Semua barang</option>@foreach ($barang as $item)<option value="{{ $item->id }}" @selected((string)($filter['barang_id'] ?? '') === (string)$item->id)>{{ $item->kode_barang }} — {{ $item->nama_barang }}</option>@endforeach</select></div>
            <div class="col-sm-6 col-lg-2"><label class="form-label" for="kategori_id">Kategori</label><select class="form-select" name="kategori_id" id="kategori_id"><option value="">Semua kategori</option>@foreach ($kategori as $item)<option value="{{ $item->id }}" @selected((string)($filter['kategori_id'] ?? '') === (string)$item->id)>{{ $item->nama }}</option>@endforeach</select></div>
            @if ($jenis === 'stok')<div class="col-sm-6 col-lg-2"><label class="form-label" for="status_stok">Status stok</label><select class="form-select" name="status_stok" id="status_stok"><option value="">Semua status</option>@foreach (['aman'=>'Aman','menipis'=>'Menipis','habis'=>'Habis'] as $key=>$label)<option value="{{ $key }}" @selected(($filter['status_stok'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></div>@endif
            <div class="col-sm-6 col-lg-2"><label class="form-label" for="lokasi_id">Lokasi</label><select class="form-select" name="lokasi_id" id="lokasi_id"><option value="">Semua lokasi</option>@foreach ($lokasi as $item)<option value="{{ $item->id }}" @selected((string)($filter['lokasi_id'] ?? '') === (string)$item->id)>{{ $item->nama }}</option>@endforeach</select></div>
            @if ($jenis !== 'stok')<div class="col-sm-6 col-lg-2"><label class="form-label" for="user_id">Petugas</label><select class="form-select" name="user_id" id="user_id"><option value="">Semua petugas</option>@foreach ($petugas as $item)<option value="{{ $item->id }}" @selected((string)($filter['user_id'] ?? '') === (string)$item->id)>{{ $item->name }}</option>@endforeach</select></div>@endif
            @if (in_array($jenis, ['masuk','keluar','mutasi'], true))<div class="col-sm-6 col-lg-2"><label class="form-label" for="q">Nomor transaksi</label><input class="form-control" name="q" id="q" value="{{ $filter['q'] ?? '' }}"></div>@endif
            <div class="col-sm-6 col-lg-2 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button><a class="btn btn-outline-secondary" href="{{ route('laporan.index', ['jenis'=>$jenis]) }}">Reset</a></div>
        </form>
    </div></div>
    @php($params = request()->query())
    <div class="card"><div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><span>{{ $judul }} <span class="badge text-bg-secondary">{{ $baris->count() }} baris</span></span><div class="d-flex gap-2"><a class="btn btn-sm btn-outline-danger" href="{{ route('laporan.pdf', ['jenis'=>$jenis] + $params) }}"><i class="bi bi-filetype-pdf me-1"></i>PDF</a><a class="btn btn-sm btn-outline-success" href="{{ route('laporan.excel', ['jenis'=>$jenis] + $params) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a></div></div>
        <div class="table-responsive"><table class="table table-striped table-hover mb-0"><thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead><tbody>
            @forelse ($baris as $row)<tr>@foreach ($row as $value)<td>{{ $value ?? '—' }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headers) }}" class="text-center text-secondary py-5">Tidak ada data untuk filter ini.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</x-layouts.app>
