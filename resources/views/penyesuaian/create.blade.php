<x-layouts.app title="Penyesuaian Stok" :breadcrumbs="['Transaksi' => route('transaksi.index'), 'Penyesuaian Stok' => null]">
    <div class="d-flex flex-wrap gap-2 mb-3 transaksi-actions">
        <a href="{{ route('transaksi.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Riwayat Transaksi</a>
        <a href="{{ route('penyesuaian.index') }}" class="btn btn-outline-primary"><i class="bi bi-list-check me-1"></i> Daftar Penyesuaian</a>
    </div>
    <div class="row justify-content-center"><div class="col-xl-8">
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i> Periksa fisik barang terlebih dahulu. Penyesuaian akan mengubah saldo stok dan dicatat permanen sebagai transaksi ADJ.</div>
        <form action="{{ route('penyesuaian.store') }}" method="POST" data-form-penyesuaian data-search-url="{{ $pencarianUrl }}">
            @csrf
            <input type="hidden" name="barang_id">
            <input type="hidden" name="stok_sistem">
            <div class="card mb-4"><div class="card-header bg-white">Pilih barang</div><div class="card-body">
                <label class="form-label" for="cariBarangPenyesuaian">Barcode, kode, atau nama barang</label>
                <input id="cariBarangPenyesuaian" type="search" class="form-control form-control-lg" placeholder="Pindai barcode lalu tekan Enter" autocomplete="off" data-barang-search>
                <x-camera-scanner id="kameraPenyesuaian" keyboard-target="#cariBarangPenyesuaian" />
                <div class="list-group mt-3" data-hasil-barang></div>
                <div class="alert alert-info py-2 mt-3 mb-0 d-none" data-barang-terpilih></div>
                @error('barang_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div></div>
            <div class="card mb-4"><div class="card-header bg-white">Hitung stok fisik</div><div class="card-body">
                <label class="form-label" for="stokFisik">Jumlah stok fisik hasil hitung</label>
                <input id="stokFisik" name="stok_fisik" class="form-control form-control-lg" type="number" min="0" step="0.01" required inputmode="decimal" placeholder="0,00">
                @error('stok_fisik')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                <label class="form-label mt-3" for="alasan">Alasan penyesuaian</label>
                <textarea id="alasan" name="alasan" class="form-control" rows="3" maxlength="5000" required placeholder="Contoh: hasil stok opname tanggal ..."></textarea>
                @error('alasan')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div></div>
            <button class="btn btn-warning btn-lg" type="button" data-buka-konfirmasi><i class="bi bi-clipboard2-check me-1"></i> Tinjau penyesuaian</button>
        </form>
    </div></div>

    <div class="modal fade" id="modalKonfirmasiPenyesuaian" tabindex="-1" aria-labelledby="judulKonfirmasiPenyesuaian" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h2 class="modal-title fs-5" id="judulKonfirmasiPenyesuaian">Konfirmasi penyesuaian stok</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><p class="mb-0" data-konfirmasi-penyesuaian-teks></p><p class="small text-secondary mt-3 mb-0">Stok, transaksi, ledger, dan audit akan disimpan bersama. Transaksi tidak dapat diedit atau dihapus.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-warning" data-simpan-penyesuaian>Ya, simpan</button></div>
        </div></div>
    </div>
</x-layouts.app>
