/** Tambahkan label kolom agar tabel tetap terbaca saat baris ditampilkan sebagai kartu. */
export function initResponsiveTables(root = document) {
    const pasangLabel = (table) => {
        const headers = [...table.querySelectorAll('thead tr:last-child th')]
            .map((th) => th.textContent.replace(/\s+/g, ' ').trim());

        if (!headers.length) return;

        table.querySelectorAll('tbody tr').forEach((row) => {
            const cells = [...row.children].filter((cell) => cell.matches('td'));
            // Baris kosong dengan colspan adalah pesan status, bukan data berbentuk kolom.
            if (cells.length === 1 && Number(cells[0].colSpan) > 1) return;

            let index = 0;
            cells.forEach((cell) => {
                const span = Math.max(1, Number(cell.colSpan) || 1);
                const label = headers[index] ?? '';
                if (label && !cell.dataset.label) cell.dataset.label = label;
                index += span;
            });
        });
    };

    root.querySelectorAll('table.table').forEach((table) => {
        pasangLabel(table);
        if (table.dataset.labelObserver) return;
        table.dataset.labelObserver = '1';

        const tbody = table.tBodies[0];
        if (tbody) {
            new MutationObserver(() => pasangLabel(table)).observe(tbody, {
                childList: true,
                subtree: true,
            });
        }
    });
}
