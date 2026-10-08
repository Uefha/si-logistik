<x-layouts.app title="Data Barang" :breadcrumbs="['Master Data' => null, 'Data Barang' => null]">
    <div class="card mb-3">
        <div class="card-body">
            <form id="form-filter" class="row g-2 align-items-end">
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="f-kategori" class="form-label small mb-1">Kategori</label>
                    <select id="f-kategori" name="kategori_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($daftarKategori as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="f-lokasi" class="form-label small mb-1">Lokasi</label>
                    <select id="f-lokasi" name="lokasi_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($daftarLokasi as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="f-satuan" class="form-label small mb-1">Satuan</label>
                    <select id="f-satuan" name="satuan_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($daftarSatuan as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="f-status" class="form-label small mb-1">Status stok</label>
                    <select id="f-status" name="status_stok" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($statusStok as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="f-aktif" class="form-label small mb-1">Status aktif</label>
                    <select id="f-aktif" name="is_active" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <button type="reset" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-x-circle me-1"></i> Reset filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="bi bi-box-seam me-1"></i> Daftar Barang</span>
            <a href="{{ route('master.barang.create') }}" class="btn btn-primary btn-sm" id="btn-tambah-barang">
                <i class="bi bi-plus-lg me-1"></i> Tambah Barang
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabel-barang" class="table table-hover align-middle w-100"
                       data-datatable
                       data-url="{{ route('master.barang.data') }}"
                       data-filter-form="#form-filter"
                       data-order='[[3,"asc"]]'
                       data-empty-action="#btn-tambah-barang"
                       data-columns="{{ json_encode([
                           ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
                           ['data' => 'kode_barang', 'name' => 'kode_barang'],
                           ['data' => 'barcode', 'name' => 'barcode', 'className' => 'font-monospace small'],
                           ['data' => 'nama_barang', 'name' => 'nama_barang'],
                           ['data' => 'kategori_nama', 'name' => 'kategori_nama', 'orderable' => false, 'searchable' => false],
                           ['data' => 'lokasi_nama', 'name' => 'lokasi_nama', 'orderable' => false, 'searchable' => false],
                           ['data' => 'stok', 'name' => 'stok', 'searchable' => false, 'className' => 'text-end text-nowrap'],
                           ['data' => 'stok_minimum', 'name' => 'stok_minimum', 'searchable' => false, 'className' => 'text-end'],
                           ['data' => 'status', 'name' => 'status', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
                           ['data' => 'aksi', 'name' => 'aksi', 'orderable' => false, 'searchable' => false, 'className' => 'text-end text-nowrap'],
                       ]) }}">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th>Kode</th>
                            <th>Barcode</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th class="text-end">Stok</th>
                            <th class="text-end">Stok Min.</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
