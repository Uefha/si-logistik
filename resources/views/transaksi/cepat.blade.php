<x-layouts.app :title="$tipe->label()" :breadcrumbs="['Transaksi' => route('transaksi.index'), $tipe->label() => null]">
    <div class="d-flex flex-wrap gap-2 mb-3 transaksi-actions">
        <a href="{{ route('transaksi.masuk.cepat') }}" class="btn {{ $tipe === \App\Enums\TipeTransaksi::Masuk ? 'btn-success' : 'btn-outline-success' }}"><i class="bi bi-box-arrow-in-down me-1"></i> Barang Masuk</a>
        <a href="{{ route('transaksi.keluar.cepat') }}" class="btn {{ $tipe === \App\Enums\TipeTransaksi::Keluar ? 'btn-primary' : 'btn-outline-primary' }}"><i class="bi bi-box-arrow-up me-1"></i> Barang Keluar</a>
        <a href="{{ route('transaksi.index') }}" class="btn btn-outline-secondary ms-lg-auto"><i class="bi bi-clock-history me-1"></i> Riwayat Transaksi</a>
    </div>

    @if ($errors->has('transaksi'))
        <div class="alert alert-danger" role="alert">{{ $errors->first('transaksi') }}</div>
    @endif

    <form data-transaksi-cepat data-store-url="{{ $simpanUrl }}" data-search-url="{{ $pencarianUrl }}">
        <div class="row g-4 align-items-start">
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header bg-white"><i class="bi bi-upc-scan me-2"></i>Pindai atau cari barang</div>
                    <div class="card-body">
                        <label for="inputBarcode" class="form-label">Barcode, kode, atau nama barang</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-upc"></i></span>
                            <input id="inputBarcode" type="search" class="form-control" placeholder="Pindai barcode lalu tekan Enter" autocomplete="off" data-barcode-input>
                        </div>
                        <div class="form-text">Scanner USB dapat langsung digunakan seperti keyboard. Pindai barcode yang sama berulang kali untuk menambah jumlah.</div>
                        <x-camera-scanner id="kameraTransaksi" keyboard-target="#inputBarcode" />
                        <div class="list-group mt-3" data-hasil-cari aria-live="polite"></div>
                    </div>
                </div>

                @if ($tipe === \App\Enums\TipeTransaksi::Keluar)
                    <div class="card mt-4">
                        <div class="card-header bg-white">Tujuan barang keluar</div>
                        <div class="card-body">
                            <label for="tujuan" class="form-label">Tujuan atau penerima <span class="text-danger">*</span></label>
                            <input id="tujuan" name="tujuan" class="form-control" maxlength="191" required placeholder="Nama penerima / unit tujuan">
                        </div>
                    </div>
                @endif
                <div class="card mt-4">
                    <div class="card-header bg-white">Keterangan <span class="text-secondary fw-normal">(opsional)</span></div>
                    <div class="card-body"><textarea name="keterangan" class="form-control" rows="3" maxlength="5000" placeholder="Catatan transaksi"></textarea></div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-basket2 me-2"></i>Keranjang</span>
                        <span class="badge text-bg-primary"><span data-total-barang>0</span> jenis barang</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 transaksi-keranjang">
                            <thead><tr><th>Barang</th><th class="text-end" style="width: 130px">Jumlah</th><th style="width: 52px"></th></tr></thead>
                            <tbody data-keranjang></tbody>
                        </table>
                        <div class="text-center text-secondary py-5" data-keranjang-kosong>
                            <i class="bi bi-basket fs-2 d-block mb-2"></i>Keranjang masih kosong. Pindai barcode untuk mulai.
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <span class="text-secondary">Total kuantitas: <strong class="text-body" data-total-qty>0</strong></span>
                        <button class="btn btn-lg {{ $tipe === \App\Enums\TipeTransaksi::Masuk ? 'btn-success' : 'btn-primary' }}" type="submit" data-simpan-transaksi disabled>
                            <i class="bi bi-check2-circle me-1"></i> Simpan {{ strtolower($tipe->label()) }}
                        </button>
                    </div>
                </div>
                <div class="alert alert-info mt-3 mb-0 small"><i class="bi bi-info-circle me-1"></i>Stok belum berubah sampai transaksi berhasil disimpan.</div>
            </div>
        </div>
    </form>
</x-layouts.app>
