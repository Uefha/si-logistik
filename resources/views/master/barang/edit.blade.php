<x-layouts.app title="Ubah Barang" :breadcrumbs="['Master Data' => null, 'Data Barang' => route('master.barang.index'), $barang->nama_barang => null]">
    @include('master.barang._form', ['action' => route('master.barang.update', $barang)])
</x-layouts.app>
