export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/**
 * Kirim form ke server dan kembalikan { ok, status, data }.
 * Method selain POST dikirim sebagai POST dengan _method (method spoofing Laravel).
 */
export async function kirimForm(url, { method = 'POST', formData = new FormData() } = {}) {
    if (method !== 'POST') {
        formData.set('_method', method);
    }

    let respons;
    try {
        respons = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
            credentials: 'same-origin',
        });
    } catch {
        return { ok: false, status: 0, data: { message: 'Tidak dapat terhubung ke server. Periksa koneksi Anda.' } };
    }

    let data = {};
    try {
        data = await respons.json();
    } catch {
        // respons bukan JSON
    }

    if (respons.status === 401) {
        window.location.href = '/login';
    } else if (respons.status === 419) {
        data = { message: 'Sesi telah berakhir. Muat ulang halaman, lalu coba lagi.' };
    }

    return { ok: respons.ok, status: respons.status, data };
}
