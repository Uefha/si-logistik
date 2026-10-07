<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\StokMutasi;
use App\Models\TransactionDetail;
use Illuminate\Support\Collection;

class LaporanService
{
    public const JENIS = ['stok', 'masuk', 'keluar', 'mutasi'];

    public function judul(string $jenis): string
    {
        return ['stok' => 'Laporan Stok Barang', 'masuk' => 'Laporan Barang Masuk', 'keluar' => 'Laporan Barang Keluar', 'mutasi' => 'Laporan Mutasi Stok'][$jenis];
    }

    public function data(string $jenis, array $filter): Collection
    {
        if ($jenis === 'stok') {
            return $this->stok($filter);
        }

        if ($jenis === 'mutasi') {
            $q = StokMutasi::query()->with(['barang.kategori', 'barang.satuan', 'barang.lokasi', 'transaction', 'user']);
            $this->rentang($q, $filter, 'tanggal');
            $q->when($filter['barang_id'] ?? null, fn ($builder, $id) => $builder->where('barang_id', $id))
                ->when($filter['q'] ?? null, fn ($builder, $nomor) => $builder->whereHas('transaction', fn ($trx) => $trx->where('nomor_transaksi', 'like', '%'.addcslashes($nomor, '\\%_').'%')))
                ->when($filter['lokasi_id'] ?? null, fn ($builder, $id) => $builder->whereHas('barang', fn ($barang) => $barang->where('lokasi_id', $id)))
                ->when($filter['kategori_id'] ?? null, fn ($builder, $id) => $builder->whereHas('barang', fn ($barang) => $barang->where('kategori_id', $id)))
                ->when($filter['user_id'] ?? null, fn ($builder, $id) => $builder->where('user_id', $id));

            return $q->orderBy('tanggal')->orderBy('id')->get()->map(fn (StokMutasi $m) => [
                $m->tanggal->format('d-m-Y H:i'), $m->transaction?->nomor_transaksi ?? 'STOK AWAL',
                $m->barang?->kode_barang, $m->barang?->nama_barang, $m->barang?->kategori?->nama,
                $m->jenis->label(), $m->qty_masuk, $m->qty_keluar, $m->stok_sesudah,
                $m->user?->name, $m->keterangan,
            ]);
        }

        $q = TransactionDetail::query()->with(['transaction.user', 'barang.kategori', 'barang.satuan', 'barang.lokasi'])
            ->whereHas('transaction', function ($trx) use ($jenis, $filter) {
                $trx->where('tipe', $jenis);
                $this->rentang($trx, $filter, 'tanggal');
            });
        $q->when($filter['barang_id'] ?? null, fn ($builder, $id) => $builder->where('barang_id', $id))
            ->when($filter['kategori_id'] ?? null, fn ($builder, $id) => $builder->whereHas('barang', fn ($barang) => $barang->where('kategori_id', $id)))
            ->when($filter['lokasi_id'] ?? null, fn ($builder, $id) => $builder->whereHas('barang', fn ($barang) => $barang->where('lokasi_id', $id)))
            ->when($filter['user_id'] ?? null, fn ($builder, $id) => $builder->whereHas('transaction', fn ($trx) => $trx->where('user_id', $id)))
            ->when($filter['q'] ?? null, fn ($builder, $qText) => $builder->whereHas('transaction', fn ($trx) => $trx->where('nomor_transaksi', 'like', '%'.addcslashes($qText, '\\%_').'%')));

        return $q->orderBy('transaction_id')->orderBy('id')->get()->map(fn (TransactionDetail $d) => [
            $d->transaction->tanggal->format('d-m-Y'), $d->transaction->nomor_transaksi,
            $d->barang?->kode_barang, $d->barang?->nama_barang, $d->barang?->kategori?->nama,
            $d->barang?->lokasi?->nama, $d->barang?->satuan?->nama, $d->qty, $d->stok_sebelum, $d->stok_sesudah,
            $d->transaction->user?->name, $d->transaction->tujuan,
        ]);
    }

    public function headers(string $jenis): array
    {
        return match ($jenis) {
            'stok' => ['Kode', 'Nama Barang', 'Kategori', 'Lokasi', 'Satuan', 'Stok', 'Stok Minimum', 'Status', 'Harga'],
            'masuk', 'keluar' => ['Tanggal', 'Nomor Transaksi', 'Kode', 'Nama Barang', 'Kategori', 'Lokasi', 'Satuan', 'Jumlah', 'Stok Sebelum', 'Stok Sesudah', 'Petugas', 'Tujuan/Penerima'],
            default => ['Tanggal', 'Nomor Transaksi', 'Kode', 'Nama Barang', 'Kategori', 'Jenis Mutasi', 'Masuk', 'Keluar', 'Saldo', 'Petugas', 'Keterangan'],
        };
    }

    private function stok(array $filter): Collection
    {
        return Barang::query()->with(['kategori', 'satuan', 'lokasi'])->aktif()
            ->when($filter['barang_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->when($filter['kategori_id'] ?? null, fn ($q, $id) => $q->where('kategori_id', $id))
            ->when($filter['lokasi_id'] ?? null, fn ($q, $id) => $q->where('lokasi_id', $id))
            ->when($filter['status_stok'] ?? null, fn ($q, $status) => $q->filterStatusStok($status))
            ->orderBy('nama_barang')->get()->map(fn (Barang $b) => [
                $b->kode_barang, $b->nama_barang, $b->kategori?->nama, $b->lokasi?->nama,
                $b->satuan?->nama, $b->stok, $b->stok_minimum, $b->statusStok->label(), $b->harga,
            ]);
    }

    private function rentang($query, array $filter, string $kolom): void
    {
        $query->when($filter['dari'] ?? null, fn ($q, $date) => $q->whereDate($kolom, '>=', $date))
            ->when($filter['sampai'] ?? null, fn ($q, $date) => $q->whereDate($kolom, '<=', $date));
    }
}
