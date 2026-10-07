<x-layouts.app title="Tambah Barang" :breadcrumbs="['Master Data' => null, 'Data Barang' => route('master.barang.index'), 'Tambah Barang' => null]">
    @include('master.barang._form', ['action' => route('master.barang.store')])
</x-layouts.app>
