<x-layouts.app title="Audit Log" :breadcrumbs="['Audit Log' => null]">
    <div class="alert alert-info"><i class="bi bi-shield-check me-1"></i>Catatan aktivitas tersimpan sebagai histori baca-saja untuk penelusuran perubahan.</div>
    <div class="card mb-3"><div class="card-body"><form id="filterAudit" class="row g-3 align-items-end">
        <div class="col-md-3"><label class="form-label" for="dari">Dari tanggal</label><input class="form-control" type="date" id="dari" name="dari"></div>
        <div class="col-md-3"><label class="form-label" for="sampai">Sampai tanggal</label><input class="form-control" type="date" id="sampai" name="sampai"></div>
        <div class="col-md-3"><label class="form-label" for="modul">Modul</label><select class="form-select" id="modul" name="modul"><option value="">Semua modul</option>@foreach ($modulList as $item)<option value="{{ $item }}">{{ ucfirst($item) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="user_id">Petugas</label><select class="form-select" id="user_id" name="user_id"><option value="">Semua petugas</option>@foreach ($petugas as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan</button><button class="btn btn-outline-secondary" type="reset">Reset</button></div>
    </form></div></div>
    <div class="card"><div class="table-responsive"><table id="tabelAudit" class="table table-hover align-middle mb-0" data-datatable data-url="{{ route('audit-log.data') }}" data-filter-form="#filterAudit" data-order='[[0,"desc"]]' data-columns='[{"data":"waktu"},{"data":"petugas"},{"data":"modul"},{"data":"aktivitas"},{"data":"subjek"},{"data":"detail"},{"data":"ip"}]'>
        <thead><tr><th>Waktu</th><th>Petugas</th><th>Modul</th><th>Aktivitas</th><th>Subjek</th><th>Detail ringkas</th><th>Alamat IP</th></tr></thead><tbody></tbody>
    </table></div></div>
</x-layouts.app>
