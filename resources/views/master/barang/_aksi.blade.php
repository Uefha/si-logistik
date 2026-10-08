<a href="{{ route('master.barang.show', $barang) }}" class="btn btn-sm btn-outline-secondary" title="Detail"><i class="bi bi-eye"></i></a>
<a href="{{ route('master.barang.edit', $barang) }}" class="btn btn-sm btn-outline-primary" title="Ubah"><i class="bi bi-pencil-square"></i></a>
<button type="button" class="btn btn-sm btn-outline-danger" title="Hapus"
        data-confirm-delete
        data-url="{{ route('master.barang.destroy', $barang) }}"
        data-table="tabel-barang"
        data-pesan="Hapus barang &quot;{{ $barang->nama_barang }}&quot;? Histori transaksi tetap tersimpan.">
    <i class="bi bi-trash"></i>
</button>
