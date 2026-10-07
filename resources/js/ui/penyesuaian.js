import { Modal } from 'bootstrap';
import { csrfToken } from './http';
import { showToast } from './toast';

export function initFormPenyesuaian(root = document) {
    const form = root.querySelector('[data-form-penyesuaian]');
    if (!form || form.dataset.siap) return;
    form.dataset.siap = '1';

    const search = form.querySelector('[data-barang-search]');
    const hiddenId = form.elements.barang_id;
    const results = form.querySelector('[data-hasil-barang]');
    const selection = form.querySelector('[data-barang-terpilih]');
    const fisik = form.elements.stok_fisik;
    const button = form.querySelector('[data-buka-konfirmasi]');
    const confirmButton = document.querySelector('[data-simpan-penyesuaian]');
    const modalElement = document.getElementById('modalKonfirmasiPenyesuaian');
    const modal = Modal.getOrCreateInstance(modalElement);
    let selectedItem = null;
    let searchTimer;
    let currentSearchId = 0;

    const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    })[char]);

    function pilih(item, fokusJumlah = true) {
        selectedItem = item;
        hiddenId.value = item.id;
        form.elements.stok_sistem.value = item.stok;
        search.value = `${item.kode_barang} — ${item.nama_barang}`;
        results.replaceChildren();
        selection.innerHTML = `<i class="bi bi-box-seam me-1"></i> Stok sistem saat ini: <strong>${escape(item.stok_format)} ${escape(item.satuan ?? '')}</strong>`;
        selection.classList.remove('d-none');
        if (fokusJumlah) fisik.focus();
    }

    async function cari(kata, exact = false, jagaFokus = false) {
        const requestId = ++currentSearchId;
        if (!kata.trim()) {
            results.replaceChildren();
            return;
        }
        try {
            const response = await fetch(`${form.dataset.searchUrl}?q=${encodeURIComponent(kata.trim())}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (requestId !== currentSearchId) return;
            const items = payload.data ?? [];
            if (exact && items[0]?.barcode === kata.trim()) {
                pilih(items[0], !jagaFokus);
                return;
            }
            results.replaceChildren();
            items.forEach((item) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'list-group-item list-group-item-action d-flex justify-content-between gap-3';
                option.innerHTML = `<span><strong class="d-block">${escape(item.nama_barang)}</strong><small>${escape(item.kode_barang)} · ${escape(item.barcode)}</small></span><span class="text-end small">Stok<br><b>${escape(item.stok_format)} ${escape(item.satuan ?? '')}</b></span>`;
                option.addEventListener('click', () => pilih(item));
                results.append(option);
            });
            if (items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'list-group-item text-secondary';
                empty.textContent = 'Barang tidak ditemukan atau tidak aktif.';
                results.append(empty);
            }
        } catch {
            showToast('Pencarian barang gagal. Periksa koneksi lalu coba lagi.', 'error');
        }
    }

    search.addEventListener('input', () => {
        hiddenId.value = '';
        selectedItem = null;
        selection.classList.add('d-none');
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => cari(search.value), 180);
    });
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(searchTimer);
            cari(search.value, true, true);
        }
    });
    document.addEventListener('si-logistik:barcode-camera', (event) => cari(event.detail.text, true, true));

    button.addEventListener('click', () => {
        if (!selectedItem || !form.reportValidity()) {
            if (!selectedItem) search.focus();
            return;
        }
        const delta = Number(fisik.value) - Number(selectedItem.stok);
        const unit = selectedItem.satuan ?? '';
        const message = document.querySelector('[data-konfirmasi-penyesuaian-teks]');
        if (delta === 0) {
            message.textContent = 'Jumlah fisik sama dengan stok sistem. Tidak ada perubahan stok yang perlu dicatat.';
            confirmButton.disabled = true;
        } else {
            const arah = delta > 0 ? 'menambah' : 'mengurangi';
            message.textContent = `Penyesuaian ini akan ${arah} stok ${selectedItem.nama_barang} sebesar ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Math.abs(delta))} ${unit}. Tindakan ini dicatat sebagai transaksi ADJ dan tidak dapat diedit.`;
            confirmButton.disabled = false;
        }
        modal.show();
    });

    confirmButton.addEventListener('click', async () => {
        if (confirmButton.disabled) return;
        confirmButton.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    barang_id: hiddenId.value,
                    stok_sistem: form.elements.stok_sistem.value,
                    stok_fisik: fisik.value,
                    alasan: form.elements.alasan.value,
                }),
            });
            const data = await response.json();
            if (response.ok) {
                window.location.assign(data.redirect);
                return;
            }
            showToast(data.message ?? Object.values(data.errors ?? {}).flat()[0] ?? 'Penyesuaian stok gagal disimpan.', 'error');
        } catch {
            showToast('Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.', 'error');
        } finally {
            confirmButton.disabled = false;
        }
    });
}
