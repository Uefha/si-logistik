@php
    $edit = $barang->exists;
@endphp
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate
      x-data="{ loading: false, preview: @js($barang->foto_url), hapusFoto: false }"
      x-on:submit="loading = true">
    @csrf
    @if ($edit)
        @method('PUT')
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Informasi barang</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="kode_barang" class="form-label">Kode barang <span class="text-danger">*</span></label>
                            <input type="text" id="kode_barang" name="kode_barang" maxlength="50" required
                                   value="{{ old('kode_barang', $barang->kode_barang) }}"
                                   class="form-control @error('kode_barang') is-invalid @enderror">
                            @error('kode_barang') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="barcode" class="form-label">Barcode</label>
                            <input type="text" id="barcode" name="barcode" maxlength="64" autocomplete="off"
                                   value="{{ old('barcode', $barang->barcode) }}"
                                   class="form-control font-monospace @error('barcode') is-invalid @enderror">
                            @error('barcode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">
                                    @if ($edit)
                                        Kosongkan untuk mempertahankan barcode saat ini. Mengubah barcode membuat label lama tidak terbaca.
                                    @else
                                        Kosongkan untuk membuat barcode otomatis.
                                    @endif
                                </div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="nama_barang" class="form-label">Nama barang <span class="text-danger">*</span></label>
                            <input type="text" id="nama_barang" name="nama_barang" maxlength="191" required
                                   value="{{ old('nama_barang', $barang->nama_barang) }}"
                                   class="form-control @error('nama_barang') is-invalid @enderror">
                            @error('nama_barang') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="kategori_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select id="kategori_id" name="kategori_id" required class="form-select @error('kategori_id') is-invalid @enderror">
                                <option value="">Pilih kategori</option>
                                @foreach ($daftarKategori as $item)
                                    <option value="{{ $item->id }}" @selected((string) old('kategori_id', $barang->kategori_id) === (string) $item->id)>{{ $item->nama }}</option>
                                @endforeach
                            </select>
                            @error('kategori_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="satuan_id" class="form-label">Satuan <span class="text-danger">*</span></label>
                            <select id="satuan_id" name="satuan_id" required class="form-select @error('satuan_id') is-invalid @enderror">
                                <option value="">Pilih satuan</option>
                                @foreach ($daftarSatuan as $item)
                                    <option value="{{ $item->id }}" @selected((string) old('satuan_id', $barang->satuan_id) === (string) $item->id)>{{ $item->nama }}</option>
                                @endforeach
                            </select>
                            @error('satuan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="lokasi_id" class="form-label">Lokasi penyimpanan <span class="text-danger">*</span></label>
                            <select id="lokasi_id" name="lokasi_id" required class="form-select @error('lokasi_id') is-invalid @enderror">
                                <option value="">Pilih lokasi</option>
                                @foreach ($daftarLokasi as $item)
                                    <option value="{{ $item->id }}" @selected((string) old('lokasi_id', $barang->lokasi_id) === (string) $item->id)>{{ $item->nama }}</option>
                                @endforeach
                            </select>
                            @error('lokasi_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @unless ($edit)
                            <div class="col-md-4">
                                <label for="stok_awal" class="form-label">Stok awal <span class="text-danger">*</span></label>
                                <input type="number" id="stok_awal" name="stok_awal" min="0" step="0.01" required inputmode="decimal"
                                       value="{{ old('stok_awal', 0) }}"
                                       class="form-control @error('stok_awal') is-invalid @enderror">
                                @error('stok_awal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endunless
                        <div class="col-md-4">
                            <label for="stok_minimum" class="form-label">Stok minimum <span class="text-danger">*</span></label>
                            <input type="number" id="stok_minimum" name="stok_minimum" min="0" step="0.01" required inputmode="decimal"
                                   value="{{ old('stok_minimum', $barang->stok_minimum ?? 0) }}"
                                   class="form-control @error('stok_minimum') is-invalid @enderror">
                            @error('stok_minimum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="harga" class="form-label">Harga (opsional)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="harga" name="harga" min="0" step="0.01" inputmode="decimal"
                                       value="{{ old('harga', $barang->harga) }}"
                                       class="form-control @error('harga') is-invalid @enderror">
                                @error('harga') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        @if ($edit)
                            <div class="col-12">
                                <div class="alert alert-light border mb-0 small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Stok saat ini <strong>{{ \App\Support\Format::angka($barang->stok) }}</strong>.
                                    Stok tidak dapat diubah dari form ini; gunakan transaksi barang masuk/keluar atau penyesuaian stok.
                                </div>
                            </div>
                        @endif

                        <div class="col-12">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="2000"
                                      class="form-control @error('deskripsi') is-invalid @enderror">{{ old('deskripsi', $barang->deskripsi) }}</textarea>
                            @error('deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">Foto barang (opsional)</div>
                <div class="card-body">
                    <div class="ratio ratio-1x1 border rounded bg-light mb-3 overflow-hidden">
                        <template x-if="preview && !hapusFoto">
                            <img x-bind:src="preview" alt="Pratinjau foto" class="w-100 h-100 object-fit-contain">
                        </template>
                        <template x-if="!preview || hapusFoto">
                            <div class="d-flex flex-column align-items-center justify-content-center text-secondary">
                                <i class="bi bi-image fs-1"></i>
                                <small>Belum ada foto</small>
                            </div>
                        </template>
                    </div>
                    <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp"
                           class="form-control @error('foto') is-invalid @enderror"
                           x-on:change="if ($event.target.files[0]) { preview = URL.createObjectURL($event.target.files[0]); hapusFoto = false; }">
                    @error('foto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @else
                        <div class="form-text">JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.</div>
                    @enderror
                    @if ($edit && $barang->foto)
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="hapus_foto" name="hapus_foto" value="1" x-model="hapusFoto">
                            <label class="form-check-label" for="hapus_foto">Hapus foto saat ini</label>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Status</div>
                <div class="card-body">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                               @checked((bool) old('is_active', $barang->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Barang aktif</label>
                    </div>
                    <div class="form-text">Barang nonaktif tetap tersimpan tetapi tidak dipakai pada transaksi.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-primary" x-bind:disabled="loading">
            <span x-show="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" style="display: none;"></span>
            {{ $edit ? 'Simpan perubahan' : 'Simpan barang' }}
        </button>
        <a href="{{ $edit ? route('master.barang.show', $barang) : route('master.barang.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
