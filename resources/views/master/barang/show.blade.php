<x-layouts.app title="Detail Barang" :breadcrumbs="['Master Data' => null, 'Data Barang' => route('master.barang.index'), $barang->nama_barang => null]">
    <div class="row g-4" data-halaman-detail-barang>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    @if ($barang->foto_url)
                        <img src="{{ $barang->foto_url }}" alt="Foto {{ $barang->nama_barang }}" class="img-fluid rounded border mb-3" style="max-height: 280px;">
                    @else
                        <div class="ratio ratio-1x1 border rounded bg-light mb-3">
                            <div class="d-flex flex-column align-items-center justify-content-center text-secondary">
                                <i class="bi bi-image fs-1"></i>
                                <small>Belum ada foto</small>
                            </div>
                        </div>
                    @endif
                    <h2 class="h5 mb-1">{{ $barang->nama_barang }}</h2>
                    <div class="mb-2"><x-badge-stok :status="$barang->status_stok" /></div>
                    @unless ($barang->is_active)
                        <span class="badge text-bg-secondary">Nonaktif</span>
                    @endunless
                </div>
                <div class="card-footer d-flex gap-2 justify-content-center bg-white">
                    <a href="{{ route('master.barang.edit', $barang) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square me-1"></i> Ubah</a>
                    <button type="button" class="btn btn-outline-danger btn-sm"
                            data-confirm-delete
                            data-redirect="1"
                            data-url="{{ route('master.barang.destroy', $barang) }}"
                            data-pesan="Hapus barang &quot;{{ $barang->nama_barang }}&quot;? Histori transaksi tetap tersimpan.">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                    <a href="{{ route('master.barang.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">Informasi barang</div>
                <div class="card-body">
                    <dl class="row mb-0 detail-barang-info">
                        <dt class="col-sm-4 detail-barang-label">Kode barang</dt>
                        <dd class="col-sm-8 detail-barang-value">{{ $barang->kode_barang }}</dd>
                        <dt class="col-sm-4 detail-barang-label">Barcode</dt>
                        <dd class="col-sm-8 detail-barang-value font-monospace">{{ $barang->barcode }}</dd>
                        <dt class="col-sm-4 detail-barang-label">Kategori</dt>
                        <dd class="col-sm-8 detail-barang-value">{{ $barang->kategori?->nama ?? '—' }}</dd>
                        <dt class="col-sm-4 detail-barang-label">Satuan</dt>
                        <dd class="col-sm-8 detail-barang-value">{{ $barang->satuan?->nama ?? '—' }}</dd>
                        <dt class="col-sm-4 detail-barang-label">Lokasi penyimpanan</dt>
                        <dd class="col-sm-8 detail-barang-value">{{ $barang->lokasi?->nama ?? '—' }}</dd>
                        <dt class="col-sm-4 detail-barang-label">Stok saat ini</dt>
                        <dd class="col-sm-8 detail-barang-value fw-semibold">{{ \App\Support\Format::angka($barang->stok) }} {{ $barang->satuan?->nama }}</dd>
                        <dt class="col-sm-4 detail-barang-label">Stok minimum</dt>
                        <dd class="col-sm-8 detail-barang-value">{{ \App\Support\Format::angka($barang->stok_minimum) }} {{ $barang->satuan?->nama }}</dd>
                        <dt class="col-sm-4 detail-barang-label">Harga</dt>
                        <dd class="col-sm-8 detail-barang-value">{{ \App\Support\Format::rupiah($barang->harga) }}</dd>
                        <dt class="col-sm-4 detail-barang-label">Deskripsi</dt>
                        <dd class="col-sm-8 detail-barang-value detail-barang-deskripsi">{!! nl2br(e($barang->deskripsi ?? '-')) !!}</dd>
                        <dt class="col-sm-4 detail-barang-label">Dibuat</dt>
                        <dd class="col-sm-8 detail-barang-value">{{ $barang->created_at?->translatedFormat('d F Y H:i') }}</dd>
                        <dt class="col-sm-4 detail-barang-label">Diperbarui</dt>
                        <dd class="col-sm-8 detail-barang-value mb-0">{{ $barang->updated_at?->translatedFormat('d F Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span>Barcode dan label</span>
                    <div class="d-flex gap-2 transaksi-actions"><a href="{{ route('master.barang.kartu-stok', $barang) }}" class="btn btn-sm btn-outline-secondary">Kartu stok</a><button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Cetak label</button></div>
                </div>
                <div class="card-body">
                    @include('master.barang._label', ['barang' => $barang])
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span>Riwayat transaksi barang</span>
                    <a href="{{ route('master.barang.kartu-stok', $barang) }}" class="btn btn-sm btn-outline-primary">Lihat kartu stok</a>
                </div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Tanggal</th><th>Transaksi</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th><th class="text-end">Saldo</th></tr></thead>
                    <tbody>
                        @forelse ($riwayat as $mutasi)
                            <tr>
                                <td>{{ $mutasi->tanggal->translatedFormat('d M Y H:i') }}</td>
                                <td>@if ($mutasi->transaction)<a href="{{ route('transaksi.show', $mutasi->transaction) }}">{{ $mutasi->transaction->nomor_transaksi }}</a>@else Stok Awal @endif</td>
                                <td class="text-end">{{ (float) $mutasi->qty_masuk > 0 ? \App\Support\Format::angka($mutasi->qty_masuk) : '—' }}</td>
                                <td class="text-end">{{ (float) $mutasi->qty_keluar > 0 ? \App\Support\Format::angka($mutasi->qty_keluar) : '—' }}</td>
                                <td class="text-end fw-semibold">{{ \App\Support\Format::angka($mutasi->stok_sesudah) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada mutasi stok.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</x-layouts.app>
