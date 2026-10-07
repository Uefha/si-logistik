<x-layouts.app :title="$cfg['judul']" :breadcrumbs="['Master Data' => null, $cfg['judul'] => null]">
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="bi bi-{{ $cfg['ikon'] }} me-1"></i> Daftar {{ $cfg['judul'] }}</span>
            <button type="button" class="btn btn-primary btn-sm" id="btn-tambah" data-crud-create>
                <i class="bi bi-plus-lg me-1"></i> Tambah {{ $cfg['singular'] }}
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="{{ $idTabel }}" class="table table-hover align-middle w-100"
                       data-datatable
                       data-url="{{ $dataUrl }}"
                       data-columns="{{ json_encode($kolom) }}"
                       data-order='[[1,"asc"]]'
                       data-empty-action="#btn-tambah">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th>Nama</th>
                            @if ($cfg['punya_keterangan'])
                                <th>Keterangan</th>
                            @endif
                            <th class="text-center">Jumlah Barang</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalMaster" tabindex="-1" aria-labelledby="modalMasterLabel" aria-hidden="true"
         data-label="{{ $cfg['singular'] }}" data-store-url="{{ $storeUrl }}" data-table="{{ $idTabel }}">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" autocomplete="off" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMasterLabel" data-modal-judul>Tambah {{ $cfg['singular'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="master-nama" class="form-label">Nama {{ strtolower($cfg['singular']) }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="master-nama" name="nama" maxlength="100" required>
                        <div class="invalid-feedback" data-error-for="nama"></div>
                    </div>
                    @if ($cfg['punya_keterangan'])
                        <div class="mb-0">
                            <label for="master-keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="master-keterangan" name="keterangan" rows="3" maxlength="1000"></textarea>
                            <div class="invalid-feedback" data-error-for="keterangan"></div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-modal-simpan>
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true" data-modal-spinner></span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
