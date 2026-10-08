@php
    $nilai = ['nama' => $model->nama];
    if ($cfg['punya_keterangan']) {
        $nilai['keterangan'] = $model->keterangan;
    }
    $pesanHapus = 'Hapus '.strtolower($cfg['singular']).' "'.$model->nama.'"? Tindakan ini tidak dapat dibatalkan.';
@endphp
<button type="button" class="btn btn-sm btn-outline-primary" title="Ubah"
        data-crud-edit
        data-update-url="{{ route($cfg['rute'].'.update', $model->getKey()) }}"
        data-values="{{ json_encode($nilai) }}">
    <i class="bi bi-pencil-square"></i>
</button>
<button type="button" class="btn btn-sm btn-outline-danger" title="Hapus"
        data-confirm-delete
        data-url="{{ route($cfg['rute'].'.destroy', $model->getKey()) }}"
        data-table="tabel-{{ $cfg['rute'] }}"
        data-pesan="{{ $pesanHapus }}">
    <i class="bi bi-trash"></i>
</button>
