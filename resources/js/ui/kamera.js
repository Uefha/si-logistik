import { showToast } from './toast';

export function initKameraBarcode(root = document) {
    root.querySelectorAll('[data-camera-widget]').forEach((widget) => {
        if (widget.dataset.siap) return;
        widget.dataset.siap = '1';

        const startButton = widget.querySelector('[data-camera-start]');
        const stopButton = widget.querySelector('[data-camera-stop]');
        const panel = widget.querySelector('[data-camera-panel]');
        const status = widget.querySelector('[data-camera-status]');
        const reader = widget.querySelector('[data-camera-reader]');
        const keyboardTarget = document.querySelector(widget.dataset.keyboardTarget);
        let scanner = null;
        let lastCode = null;
        let resetTimer = null;

        const tampilkanError = (error) => {
            const pesan = !window.isSecureContext
                ? 'Kamera memerlukan HTTPS saat dibuka dari jaringan sekolah. HTTP hanya didukung di localhost.'
                : 'Kamera tidak dapat dibuka. Periksa izin kamera dan pastikan kamera tidak sedang dipakai aplikasi lain.';
            status.textContent = pesan;
            showToast(pesan, 'error');
            console.error('Gagal memulai pemindai kamera:', error);
        };

        startButton.addEventListener('click', async () => {
            if (!window.isSecureContext) {
                tampilkanError('Halaman bukan secure context.');
                return;
            }

            startButton.disabled = true;
            status.textContent = 'Meminta izin dan memulai kamera…';
            panel.classList.remove('d-none');
            try {
                const { Html5Qrcode, Html5QrcodeSupportedFormats } = await import('html5-qrcode');
                scanner = new Html5Qrcode(reader.id, {
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.QR_CODE,
                ],
                verbose: false,
                });

                await scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 260, height: 150 }, aspectRatio: 1.0 },
                    (decodedText) => {
                        clearTimeout(resetTimer);
                        resetTimer = null;
                        if (decodedText !== lastCode) {
                            lastCode = decodedText;
                            status.textContent = `Kode terbaca: ${decodedText}. Kamera tetap aktif; tekan Selesai untuk menghentikan.`;
                            document.dispatchEvent(new CustomEvent('si-logistik:barcode-camera', { detail: { text: decodedText } }));
                        }
                    },
                    () => {
                        if (lastCode && !resetTimer) {
                            resetTimer = setTimeout(() => {
                                lastCode = null;
                                resetTimer = null;
                            }, 1600);
                        }
                    },
                );
                status.textContent = 'Kamera aktif. Arahkan ke barcode; tekan Selesai jika sudah.';
                stopButton.disabled = false;
                keyboardTarget?.focus();
            } catch (error) {
                tampilkanError(error);
                panel.classList.add('d-none');
                startButton.disabled = false;
                try { scanner.clear(); } catch { /* scanner gagal sebelum kamera aktif */ }
                scanner = null;
            }
        });

        stopButton.addEventListener('click', async () => {
            if (!scanner) return;
            stopButton.disabled = true;
            status.textContent = 'Menghentikan kamera…';
            try {
                await scanner.stop();
                scanner.clear();
                scanner = null;
                panel.classList.add('d-none');
                startButton.disabled = false;
                status.textContent = 'Kamera dihentikan.';
                keyboardTarget?.focus();
            } catch (error) {
                tampilkanError(error);
                stopButton.disabled = false;
            }
        });
    });
}
