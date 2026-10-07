import { Toast } from 'bootstrap';

const WARNA = { success: 'success', error: 'danger', warning: 'warning', info: 'primary' };

/**
 * Tampilkan toast dinamis. Teks dipasang lewat textContent (bukan innerHTML)
 * sehingga pesan dari server tidak dapat menyisipkan HTML.
 */
export function showToast(pesan, tipe = 'success') {
    const wadah = document.querySelector('.toast-container');
    if (!wadah) {
        return;
    }

    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${WARNA[tipe] ?? 'primary'} border-0`;
    el.setAttribute('role', 'alert');
    el.dataset.bsDelay = '4500';

    const baris = document.createElement('div');
    baris.className = 'd-flex';

    const isi = document.createElement('div');
    isi.className = 'toast-body';
    isi.textContent = pesan;

    const tutup = document.createElement('button');
    tutup.type = 'button';
    tutup.className = 'btn-close btn-close-white me-2 m-auto';
    tutup.setAttribute('data-bs-dismiss', 'toast');
    tutup.setAttribute('aria-label', 'Tutup');

    baris.append(isi, tutup);
    el.append(baris);
    wadah.append(el);

    el.addEventListener('hidden.bs.toast', () => el.remove());
    Toast.getOrCreateInstance(el).show();
}
