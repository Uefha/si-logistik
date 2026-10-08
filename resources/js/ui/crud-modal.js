import { Modal } from 'bootstrap';
import { kirimForm } from './http';
import { showToast } from './toast';
import { tabel } from './datatables';

/**
 * Modal tambah/ubah untuk master bernama (kategori, satuan, lokasi).
 * Pemicu: [data-crud-create] dan [data-crud-edit] (dengan data-update-url dan data-values JSON).
 * Kesalahan validasi (422) ditampilkan di bawah kolom terkait.
 */
export function initCrudModal() {
    const el = document.getElementById('modalMaster');
    if (!el) {
        return;
    }

    const modal = Modal.getOrCreateInstance(el);
    const form = el.querySelector('form');
    const judul = el.querySelector('[data-modal-judul]');
    const tombol = el.querySelector('[data-modal-simpan]');
    const spinner = el.querySelector('[data-modal-spinner]');
    const label = el.dataset.label;
    const urlTambah = el.dataset.storeUrl;
    let mode = { method: 'POST', url: urlTambah };

    const bersihkanGalat = () => {
        form.querySelectorAll('.is-invalid').forEach((i) => i.classList.remove('is-invalid'));
        form.querySelectorAll('[data-error-for]').forEach((f) => {
            f.textContent = '';
        });
    };

    const tampilkanGalat = (galat) => {
        Object.entries(galat).forEach(([kolom, pesan]) => {
            form.elements[kolom]?.classList.add('is-invalid');
            const umpan = form.querySelector(`[data-error-for="${kolom}"]`);
            if (umpan) {
                umpan.textContent = Array.isArray(pesan) ? pesan[0] : String(pesan);
            }
        });
    };

    const siapkan = () => {
        form.reset();
        bersihkanGalat();
    };

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-crud-create]')) {
            e.preventDefault();
            siapkan();
            mode = { method: 'POST', url: urlTambah };
            judul.textContent = `Tambah ${label}`;
            modal.show();
            return;
        }

        const ubah = e.target.closest('[data-crud-edit]');
        if (ubah) {
            e.preventDefault();
            siapkan();
            mode = { method: 'PUT', url: ubah.dataset.updateUrl };
            const nilai = JSON.parse(ubah.dataset.values ?? '{}');
            Object.entries(nilai).forEach(([kolom, isi]) => {
                if (form.elements[kolom]) {
                    form.elements[kolom].value = isi ?? '';
                }
            });
            judul.textContent = `Ubah ${label}`;
            modal.show();
        }
    });

    el.addEventListener('shown.bs.modal', () => form.elements.nama?.focus());

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (tombol.disabled) {
            return;
        }

        bersihkanGalat();
        tombol.disabled = true;
        spinner.classList.remove('d-none');

        const hasil = await kirimForm(mode.url, { method: mode.method, formData: new FormData(form) });

        tombol.disabled = false;
        spinner.classList.add('d-none');

        if (hasil.ok) {
            modal.hide();
            showToast(hasil.data.message ?? 'Data berhasil disimpan.');
            tabel[el.dataset.table]?.ajax.reload(null, false);
        } else if (hasil.status === 422 && hasil.data.errors) {
            tampilkanGalat(hasil.data.errors);
        } else {
            showToast(hasil.data.message ?? 'Terjadi kesalahan. Silakan coba lagi.', 'error');
        }
    });
}
