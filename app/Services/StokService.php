<?php

namespace App\Services;

use App\Enums\JenisMutasi;
use App\Enums\TipeTransaksi;
use App\Exceptions\AturanBisnisException;
use App\Models\Barang;
use App\Models\PenyesuaianStok;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\StokMutasi;
use App\Models\User;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StokService
{
    public function __construct(
        private readonly NomorTransaksiService $nomor,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Simpan transaksi masuk/keluar beserta semua perubahan stok, ledger, dan audit secara atomik.
     *
     * @param  list<array{barang_id: int|string, qty: int|float|string}>  $items
     */
    public function simpan(
        TipeTransaksi $tipe,
        array $items,
        User $user,
        ?string $tujuan = null,
        ?string $keterangan = null,
    ): Transaction {
        if (! in_array($tipe, [TipeTransaksi::Masuk, TipeTransaksi::Keluar], true)) {
            throw new InvalidArgumentException('Tahap ini hanya mendukung transaksi masuk dan keluar.');
        }

        $qtyPerBarang = $this->gabungkanItem($items);

        return DB::transaction(function () use ($tipe, $qtyPerBarang, $user, $tujuan, $keterangan) {
            $ids = array_keys($qtyPerBarang);
            sort($ids, SORT_NUMERIC);

            // Ambil dan kunci semua barang dalam urutan ID konsisten untuk mengurangi deadlock.
            $barangTerkunci = Barang::query()
                ->whereIn('id', $ids)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($barangTerkunci->count() !== count($ids)) {
                throw new AturanBisnisException('Ada barang yang tidak ditemukan atau sudah nonaktif. Muat ulang keranjang.');
            }

            foreach ($ids as $id) {
                $barang = $barangTerkunci->get($id);
                if ($tipe === TipeTransaksi::Keluar && $qtyPerBarang[$id] > $this->kePerseratus($barang->stok)) {
                    throw new AturanBisnisException(
                        'Stok tidak mencukupi. Stok tersedia: '.Format::angka($barang->stok).'.'
                    );
                }
                if ($tipe === TipeTransaksi::Masuk && $this->kePerseratus($barang->stok) + $qtyPerBarang[$id] > 999_999_999_999_999) {
                    throw new AturanBisnisException('Jumlah transaksi melebihi batas maksimum stok barang.');
                }
            }

            $tanggal = CarbonImmutable::today(config('app.timezone'));
            $transaksi = Transaction::query()->create([
                'nomor_transaksi' => $this->nomor->berikutnya($tipe, $tanggal),
                'tipe' => $tipe,
                'tanggal' => $tanggal->toDateString(),
                'user_id' => $user->id,
                'tujuan' => $tipe === TipeTransaksi::Keluar ? $tujuan : null,
                'keterangan' => $keterangan,
            ]);

            foreach ($ids as $id) {
                /** @var Barang $barang */
                $barang = $barangTerkunci->get($id);
                $sebelum = $this->kePerseratus($barang->stok);
                $qty = $qtyPerBarang[$id];
                $sesudah = $tipe === TipeTransaksi::Masuk ? $sebelum + $qty : $sebelum - $qty;

                $detail = TransactionDetail::query()->create([
                    'transaction_id' => $transaksi->id,
                    'barang_id' => $barang->id,
                    'qty' => $this->dariPerseratus($qty),
                    'stok_sebelum' => $this->dariPerseratus($sebelum),
                    'stok_sesudah' => $this->dariPerseratus($sesudah),
                ]);

                $barang->stok = $this->dariPerseratus($sesudah);
                $barang->save();

                StokMutasi::query()->create([
                    'barang_id' => $barang->id,
                    'transaction_id' => $transaksi->id,
                    'transaction_detail_id' => $detail->id,
                    'jenis' => $tipe === TipeTransaksi::Masuk ? JenisMutasi::Masuk : JenisMutasi::Keluar,
                    'qty_masuk' => $tipe === TipeTransaksi::Masuk ? $this->dariPerseratus($qty) : '0.00',
                    'qty_keluar' => $tipe === TipeTransaksi::Keluar ? $this->dariPerseratus($qty) : '0.00',
                    'stok_sebelum' => $this->dariPerseratus($sebelum),
                    'stok_sesudah' => $this->dariPerseratus($sesudah),
                    'tanggal' => $tanggal->startOfDay(),
                    'user_id' => $user->id,
                    'keterangan' => $keterangan,
                ]);
            }

            $this->audit->catat(
                ($tipe === TipeTransaksi::Masuk ? 'Mencatat barang masuk: ' : 'Mencatat barang keluar: ').$transaksi->nomor_transaksi,
                'transaksi',
                $transaksi,
                [
                    'nomor_transaksi' => $transaksi->nomor_transaksi,
                    'tipe' => $tipe->value,
                    'jumlah_barang' => count($ids),
                    'tujuan' => $tujuan,
                ],
            );

            return $transaksi;
        }, 3);
    }

    public function sesuaikan(Barang $barang, int|float|string $stokSistemDiharapkan, int|float|string $stokFisik, string $alasan, User $user): Transaction
    {
        $fisik = $this->kePerseratus($stokFisik);
        if ($fisik < 0 || $fisik > 999_999_999_999_999) {
            throw new AturanBisnisException('Stok fisik berada di luar batas yang diizinkan.');
        }

        $sistemDiharapkan = $this->kePerseratus($stokSistemDiharapkan);

        return DB::transaction(function () use ($barang, $sistemDiharapkan, $fisik, $alasan, $user) {
            /** @var Barang|null $terkunci */
            $terkunci = Barang::query()
                ->whereKey($barang->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $terkunci) {
                throw new AturanBisnisException('Barang tidak ditemukan atau sudah nonaktif.');
            }

            $sistem = $this->kePerseratus($terkunci->stok);
            if ($sistem !== $sistemDiharapkan) {
                throw new AturanBisnisException(
                    'Stok sistem berubah sejak barang dipilih (terbaru: '.Format::angka($terkunci->stok).'). Muat ulang barang dan tinjau ulang penyesuaian.'
                );
            }
            $selisih = $fisik - $sistem;
            if ($selisih === 0) {
                throw new AturanBisnisException('Stok fisik sama dengan stok sistem. Tidak ada penyesuaian yang perlu disimpan.');
            }

            $tanggal = CarbonImmutable::today(config('app.timezone'));
            $transaksi = Transaction::query()->create([
                'nomor_transaksi' => $this->nomor->berikutnya(TipeTransaksi::Penyesuaian, $tanggal),
                'tipe' => TipeTransaksi::Penyesuaian,
                'tanggal' => $tanggal->toDateString(),
                'user_id' => $user->id,
                'tujuan' => null,
                'keterangan' => $alasan,
            ]);

            $penyesuaian = PenyesuaianStok::query()->create([
                'transaction_id' => $transaksi->id,
                'barang_id' => $terkunci->id,
                'stok_sistem' => $this->dariPerseratus($sistem),
                'stok_fisik' => $this->dariPerseratus($fisik),
                'selisih' => $this->dariPerseratus($selisih),
                'alasan' => $alasan,
                'tanggal' => $tanggal->toDateString(),
                'user_id' => $user->id,
            ]);

            $detail = TransactionDetail::query()->create([
                'transaction_id' => $transaksi->id,
                'barang_id' => $terkunci->id,
                'qty' => $this->dariPerseratus(abs($selisih)),
                'stok_sebelum' => $this->dariPerseratus($sistem),
                'stok_sesudah' => $this->dariPerseratus($fisik),
            ]);

            $terkunci->stok = $this->dariPerseratus($fisik);
            $terkunci->save();

            StokMutasi::query()->create([
                'barang_id' => $terkunci->id,
                'transaction_id' => $transaksi->id,
                'transaction_detail_id' => $detail->id,
                'jenis' => JenisMutasi::Penyesuaian,
                'qty_masuk' => $selisih > 0 ? $this->dariPerseratus($selisih) : '0.00',
                'qty_keluar' => $selisih < 0 ? $this->dariPerseratus(abs($selisih)) : '0.00',
                'stok_sebelum' => $this->dariPerseratus($sistem),
                'stok_sesudah' => $this->dariPerseratus($fisik),
                'tanggal' => $tanggal->startOfDay(),
                'user_id' => $user->id,
                'keterangan' => $alasan,
            ]);

            $this->audit->catat(
                'Menyesuaikan stok: '.$terkunci->nama_barang,
                'penyesuaian_stok',
                $penyesuaian,
                [
                    'nomor_transaksi' => $transaksi->nomor_transaksi,
                    'barang_id' => $terkunci->id,
                    'stok_sistem' => $this->dariPerseratus($sistem),
                    'stok_fisik' => $this->dariPerseratus($fisik),
                    'selisih' => $this->dariPerseratus($selisih),
                ],
            );

            return $transaksi;
        }, 3);
    }

    /** @param list<array{barang_id: int|string, qty: int|float|string}> $items
     *  @return array<int, int> quantity in hundredths
     */
    private function gabungkanItem(array $items): array
    {
        if ($items === []) {
            throw new InvalidArgumentException('Keranjang transaksi tidak boleh kosong.');
        }

        $hasil = [];
        foreach ($items as $item) {
            $id = (int) $item['barang_id'];
            $qty = $this->kePerseratus($item['qty']);
            if ($id < 1 || $qty < 1) {
                throw new InvalidArgumentException('Barang dan jumlah harus bernilai positif.');
            }
            $hasil[$id] = ($hasil[$id] ?? 0) + $qty;
        }

        return $hasil;
    }

    private function kePerseratus(mixed $nilai): int
    {
        return (int) round((float) $nilai * 100);
    }

    private function dariPerseratus(int $nilai): string
    {
        return number_format($nilai / 100, 2, '.', '');
    }
}
