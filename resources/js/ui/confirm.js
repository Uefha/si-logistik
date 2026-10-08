import { Modal } from 'bootstrap';
import { kirimForm } from './http';
import { showToast } from './toast';
import { tabel } from './datatables';

/**
 * Dialog konfirmasi hapus. Tombol dengan atribut data-confirm-delete memicu dialog;
 * data-url = alamat DELETE, data-table = id tabel yang dimuat ulang,
 * data-redirect = bila ada, respons berisi URL tujuan setelah sukses.
 */
export function initConfirmDelete() {
    const el = document.getElementById('modalKonfirmasi');
    if (!el) {
        return;
    }

    const modal = Modal.getOrCreateInstance(el);
    const teks = el.querySelector('[data-konfirmasi-teks]');
    const tombolYa = el.querySelector('[data-konfirmasi-ya]');
    const spinner = el.querySelector('[data-konfirmasi-spinner]');
    let sasaran = null;

    document.addEventListener('click', (e) => {
        const pemicu = e.target.closest('[data-confirm-delete]');
        if (!pemicu) {
            return;
        }
        e.preventDefault();
        sasaran = {
            url: pemicu.dataset.url,
            tabel: pemicu.dataset.table,
            redirect: pemicu.dataset.redirect,
        };
        teks.textContent = pemicu.dataset.pesan || 'Apakah Anda yakin ingin melakukan tindakan ini?';
        modal.show();
    });

    tombolYa.addEventListener('click', async () => {
        if (!sasaran || tombolYa.disabled) {
            return;
        }

        const target = sasaran;
        tombolYa.disabled = true;
        spinner.classList.remove('d-none');

        const data = new FormData();
        if (target.redirect) {
            data.set('redirect', '1');
        }
        const hasil = await kirimForm(target.url, { method: 'DELETE', formData: data });

        tombolYa.disabled = false;
        spinner.classList.add('d-none');
        modal.hide();
        sasaran = null;

        if (!hasil.ok) {
            showToast(hasil.data.message ?? 'Gagal menghapus data.', 'error');
            return;
        }

        if (hasil.data.redirect) {
            window.location.href = hasil.data.redirect;
            return;
        }

        showToast(hasil.data.message ?? 'Data berhasil dihapus.');
        tabel[target.tabel]?.ajax.reload(null, false);
    });
}
