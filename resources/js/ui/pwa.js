const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
let installPrompt = null;

function tombolPasang() {
    return [...document.querySelectorAll('[data-pwa-install]')];
}

function sembunyikanTombol() {
    tombolPasang().forEach((tombol) => tombol.classList.add('d-none'));
}

function tampilkanTombol(teks = 'Pasang aplikasi') {
    tombolPasang().forEach((tombol) => {
        tombol.textContent = teks;
        tombol.classList.remove('d-none');
    });
}

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    tampilkanTombol('Pasang aplikasi');
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    sembunyikanTombol();
});

document.addEventListener('DOMContentLoaded', () => {
    if (isStandalone()) sembunyikanTombol();
    else tampilkanTombol(isIos ? 'Cara pasang di iPhone' : 'Pasang aplikasi');

    tombolPasang().forEach((tombol) => tombol.addEventListener('click', async () => {
        if (installPrompt) {
            installPrompt.prompt();
            await installPrompt.userChoice;
            installPrompt = null;
            return;
        }
        const petunjuk = isIos
            ? 'Buka halaman ini di Safari, tekan tombol Bagikan, lalu pilih Tambahkan ke Layar Utama.'
            : 'Buka menu browser (⋮ atau ⋯), lalu pilih “Instal aplikasi” atau “Tambahkan ke layar utama”.';
        window.alert(petunjuk);
    }));
});

const isLocalHost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
if ((import.meta.env.PROD || isLocalHost) && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js').catch((error) => {
            console.error('Service worker SI-Logistik gagal didaftarkan:', error);
        });
    });
}
