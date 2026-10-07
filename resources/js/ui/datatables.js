import DataTable from 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.css';
import { showToast } from './toast';

const BAHASA = {
    processing: 'Memuat data...',
    search: '',
    searchPlaceholder: 'Cari data...',
    lengthMenu: 'Tampilkan _MENU_ data',
    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
    infoEmpty: 'Menampilkan 0 data',
    infoFiltered: '(difilter dari _MAX_ total data)',
    zeroRecords: 'Tidak ada data yang cocok.',
    emptyTable: 'Belum ada data.',
    loadingRecords: 'Memuat...',
    paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' },
};

/** Registri instance DataTable berdasarkan id tabel. */
export const tabel = {};

// Kesalahan ajax ditampilkan sebagai toast, bukan alert bawaan DataTables.
DataTable.ext.errMode = 'none';

/**
 * Ganti baris "kosong" dengan empty state: teks "Belum ada data." dan tombol "Tambah Data".
 * Hanya berlaku bila tabel memang kosong (bukan karena filter/pencarian).
 */
function tampilkanKosong(el, api) {
    if (api.page.info().recordsTotal !== 0) {
        return;
    }

    const sel = el.querySelector('td.dt-empty');
    if (!sel || sel.dataset.kustom) {
        return;
    }
    sel.dataset.kustom = '1';
    sel.replaceChildren();

    const wadah = document.createElement('div');
    wadah.className = 'text-center py-4';

    const ikon = document.createElement('i');
    ikon.className = 'bi bi-inbox fs-1 text-secondary d-block mb-2';
    const teks = document.createElement('p');
    teks.className = 'text-secondary mb-3';
    teks.textContent = 'Belum ada data.';
    wadah.append(ikon, teks);

    const asal = el.dataset.emptyAction ? document.querySelector(el.dataset.emptyAction) : null;
    if (asal) {
        const tombol = asal.cloneNode(false); // tanpa anak; atribut data-* ikut, jadi delegasi klik tetap bekerja
        tombol.removeAttribute('id');
        const plus = document.createElement('i');
        plus.className = 'bi bi-plus-lg me-1';
        tombol.append(plus, 'Tambah Data');
        wadah.append(tombol);
    }

    sel.append(wadah);
}

export function initDataTables(root = document) {
    root.querySelectorAll('table[data-datatable]').forEach((el) => {
        if (el.dataset.dtSiap) {
            return;
        }
        el.dataset.dtSiap = '1';

        const kolom = JSON.parse(el.dataset.columns ?? '[]');
        const urutan = JSON.parse(el.dataset.order ?? '[]');
        const form = el.dataset.filterForm ? document.querySelector(el.dataset.filterForm) : null;

        const dt = new DataTable(el, {
            processing: true,
            serverSide: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            language: BAHASA,
            order: urutan,
            columns: kolom,
            ajax: {
                url: el.dataset.url,
                data(d) {
                    if (form) {
                        new FormData(form).forEach((nilai, kunci) => {
                            d[kunci] = nilai;
                        });
                    }
                },
            },
            drawCallback() {
                tampilkanKosong(el, this.api());
            },
        });

        dt.on('error', () => showToast('Gagal memuat data tabel. Muat ulang halaman lalu coba lagi.', 'error'));
        tabel[el.id] = dt;

        if (form) {
            const muatUlang = () => dt.ajax.reload();
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                muatUlang();
            });
            form.addEventListener('change', muatUlang);
            form.addEventListener('reset', () => setTimeout(muatUlang, 0));
        }
    });
}
