import './bootstrap';
import * as bootstrap from 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import Alpine from 'alpinejs';

import { initDataTables } from './ui/datatables';
import { initConfirmDelete } from './ui/confirm';
import { initCrudModal } from './ui/crud-modal';
import { initBarcodeLabels, initTransaksiCepat } from './ui/transaksi';
import { initKameraBarcode } from './ui/kamera';
import { initFormPenyesuaian } from './ui/penyesuaian';
import { initDashboardCharts } from './ui/dashboard';
import { initResponsiveTables } from './ui/responsive-tables';
import './ui/pwa';

window.bootstrap = bootstrap;
window.Alpine = Alpine;

Alpine.start();

// Skrip modul dijalankan sebelum DOMContentLoaded, sehingga seluruh elemen halaman sudah tersedia.
document.addEventListener('DOMContentLoaded', () => {
    // Tampilkan semua toast yang dirender server (lihat components/flash.blade.php).
    document.querySelectorAll('.toast').forEach((el) => {
        bootstrap.Toast.getOrCreateInstance(el).show();
    });

    initDataTables();
    initResponsiveTables();
    initConfirmDelete();
    initCrudModal();
    initTransaksiCepat();
    initBarcodeLabels();
    initKameraBarcode();
    initFormPenyesuaian();
    initDashboardCharts();
});
