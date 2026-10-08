const parse = (el, name) => JSON.parse(el.dataset[name] || '[]');

export async function initDashboardCharts() {
    if (!document.getElementById('chartMutasi') && !document.getElementById('chartKategori') && !document.getElementById('chartTransaksi')) return;
    const { default: Chart } = await import('chart.js/auto');
    const mutasi = document.getElementById('chartMutasi');
    if (mutasi) {
        new Chart(mutasi, {
            type: 'bar',
            data: { labels: parse(mutasi, 'labels'), datasets: [
                { label: 'Barang masuk', data: parse(mutasi, 'masuk'), backgroundColor: '#198754', borderRadius: 5 },
                { label: 'Barang keluar', data: parse(mutasi, 'keluar'), backgroundColor: '#0d6efd', borderRadius: 5 },
            ] },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, scales: { y: { beginAtZero: true } } },
        });
    }
    const kategori = document.getElementById('chartKategori');
    if (kategori) {
        new Chart(kategori, {
            type: 'doughnut',
            data: { labels: parse(kategori, 'labels'), datasets: [{ data: parse(kategori, 'values'), backgroundColor: ['#1f4e9c','#198754','#ffc107','#0dcaf0','#dc3545','#6f42c1','#fd7e14','#20c997'] }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
    }
    const transaksi = document.getElementById('chartTransaksi');
    if (transaksi) {
        new Chart(transaksi, {
            type: 'line',
            data: { labels: parse(transaksi, 'labels'), datasets: [{ label: 'Jumlah transaksi', data: parse(transaksi, 'values'), borderColor: '#6f42c1', backgroundColor: 'rgba(111,66,193,.12)', fill: true, tension: .3 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });
    }
}
