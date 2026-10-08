import JsBarcode from 'jsbarcode';
import QRCode from 'qrcode';
import { csrfToken } from './http';
import { showToast } from './toast';

export function initTransaksiCepat(root = document) {
    const form = root.querySelector('[data-transaksi-cepat]');
    if (!form || form.dataset.siap) return;
    form.dataset.siap = '1';

    const input = form.querySelector('[data-barcode-input]');
    const hasil = form.querySelector('[data-hasil-cari]');
    const tbody = form.querySelector('[data-keranjang]');
    const kosong = form.querySelector('[data-keranjang-kosong]');
    const totalBarang = form.querySelector('[data-total-barang]');
    const totalQty = form.querySelector('[data-total-qty]');
    const submit = form.querySelector('[data-simpan-transaksi]');
    const cart = new Map();
    let timer;
    let requestId = 0;

    const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    })[char]);

    function renderCart() {
        tbody.replaceChildren();
        let qtyTotal = 0;
        for (const item of cart.values()) {
            qtyTotal += Number(item.qty);
            const row = document.createElement('tr');
            row.innerHTML = `<td><div class="fw-semibold">${escape(item.nama_barang)}</div><small class="text-secondary">${escape(item.kode_barang)} · stok ${escape(item.stok_format)} ${escape(item.satuan ?? '')}</small></td>
                <td class="text-end"><input class="form-control form-control-sm text-end" type="number" min="0.01" step="0.01" value="${escape(item.qty)}" aria-label="Jumlah ${escape(item.nama_barang)}" data-qty="${item.id}"></td>
                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" aria-label="Hapus ${escape(item.nama_barang)}" data-hapus="${item.id}"><i class="bi bi-x-lg"></i></button></td>`;
            tbody.append(row);
        }
        kosong.classList.toggle('d-none', cart.size > 0);
        totalBarang.textContent = String(cart.size);
        totalQty.textContent = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(qtyTotal);
        submit.disabled = cart.size === 0;
    }

    function tambah(item) {
        const lama = cart.get(item.id);
        cart.set(item.id, { ...item, qty: lama ? Number(lama.qty) + 1 : 1 });
        hasil.replaceChildren();
        input.value = '';
        renderCart();
        input.focus();
    }

    async function cari(kata, exact = false) {
        const id = ++requestId;
        if (!kata.trim()) {
            hasil.replaceChildren();
            return;
        }
        try {
            const url = `${form.dataset.searchUrl}?q=${encodeURIComponent(kata.trim())}`;
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const payload = await response.json();
            if (id !== requestId) return;
            const list = payload.data ?? [];
            if (exact && list[0]?.barcode === kata.trim()) {
                tambah(list[0]);
                return;
            }
            hasil.replaceChildren();
            list.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action d-flex justify-content-between gap-3';
                button.innerHTML = `<span><span class="fw-semibold d-block">${escape(item.nama_barang)}</span><small>${escape(item.kode_barang)} · ${escape(item.barcode)}</small></span><span class="text-end small">Stok<br><b>${escape(item.stok_format)} ${escape(item.satuan ?? '')}</b></span>`;
                button.addEventListener('click', () => tambah(item));
                hasil.append(button);
            });
            if (list.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'list-group-item text-secondary';
                empty.textContent = 'Barang tidak ditemukan atau tidak aktif.';
                hasil.append(empty);
            }
        } catch {
            showToast('Pencarian barang gagal. Periksa koneksi lalu coba lagi.', 'error');
        }
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => cari(input.value), 180);
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(timer);
            cari(input.value, true);
        }
    });
    document.addEventListener('si-logistik:barcode-camera', (event) => {
        cari(event.detail.text, true);
    });
    tbody.addEventListener('change', (event) => {
        const field = event.target.closest('[data-qty]');
        if (!field) return;
        const item = cart.get(Number(field.dataset.qty));
        const qty = Number(field.value);
        if (!item || !Number.isFinite(qty) || qty <= 0) {
            if (item) field.value = item.qty;
            return;
        }
        item.qty = qty;
        renderCart();
    });
    tbody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-hapus]');
        if (!button) return;
        cart.delete(Number(button.dataset.hapus));
        renderCart();
        input.focus();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submit.disabled) return;
        submit.disabled = true;
        const payload = {
            items: [...cart.values()].map(({ id, qty }) => ({ barang_id: id, qty })),
            tujuan: form.elements.tujuan?.value || null,
            keterangan: form.elements.keterangan?.value || null,
        };
        try {
            const response = await fetch(form.dataset.storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (response.ok) {
                window.location.assign(data.redirect);
                return;
            }
            if (data.errors) {
                const messages = Object.values(data.errors).flat();
                showToast(messages[0] ?? 'Periksa kembali data transaksi.', 'error');
            } else {
                showToast(data.message ?? 'Transaksi gagal disimpan.', 'error');
            }
        } catch {
            showToast('Tidak dapat terhubung ke server. Keranjang masih tersimpan di halaman ini.', 'error');
        } finally {
            submit.disabled = cart.size === 0;
            input.focus();
        }
    });

    renderCart();
    input.focus();
}

export function initBarcodeLabels(root = document) {
    root.querySelectorAll('[data-barcode-code]').forEach((svg) => {
        try {
            JsBarcode(svg, svg.dataset.barcodeCode, { format: 'CODE128', displayValue: true, height: 54, margin: 8, fontSize: 14 });
        } catch {
            svg.replaceWith(document.createTextNode(svg.dataset.barcodeCode));
        }
    });
    root.querySelectorAll('[data-qr-code]').forEach((canvas) => {
        QRCode.toCanvas(canvas, canvas.dataset.qrCode, { width: 160, margin: 1, errorCorrectionLevel: 'M' });
    });
}
