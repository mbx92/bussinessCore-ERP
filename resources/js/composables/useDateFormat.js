const MONTHS_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/**
 * Format tanggal YYYY-MM-DD → "dd MMM yyyy" (contoh: 17 Mei 2026).
 */
export function useDateFormat() {
    const formatDate = (dateStr) => {
        if (!dateStr) return '-';

        const parts = String(dateStr).slice(0, 10).split('-');
        if (parts.length !== 3) return String(dateStr);

        const [year, month, day] = parts.map((part) => Number(part));
        if (!day || !month || !year) return String(dateStr);

        return `${day} ${MONTHS_ID[month - 1]} ${year}`;
    };

    return { formatDate };
}
