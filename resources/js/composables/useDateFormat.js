const MONTHS_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/**
 * Ambil bagian tanggal YYYY-MM-DD dari string date/datetime (timezone-safe, tanpa Date parse).
 */
function datePartFrom(value) {
    const normalized = String(value).trim();
    if (!normalized) return null;

    if (normalized.length >= 10 && normalized[4] === '-' && normalized[7] === '-') {
        return normalized.slice(0, 10);
    }

    return null;
}

/**
 * Format tanggal → "dd MMM yyyy" (contoh: 17 Mei 2026).
 */
export function formatDate(dateStr) {
    if (!dateStr) return '-';

    const parts = datePartFrom(dateStr)?.split('-');
    if (!parts || parts.length !== 3) return String(dateStr);

    const [year, month, day] = parts.map((part) => Number(part));
    if (!day || !month || !year) return String(dateStr);

    return `${day} ${MONTHS_ID[month - 1]} ${year}`;
}

/**
 * Format datetime → "dd MMM yyyy, HH:mm" bila ada komponen waktu.
 */
export function formatDateTime(dateStr) {
    if (!dateStr) return '-';

    const normalized = String(dateStr).trim();
    const dateOnly = formatDate(normalized);
    if (dateOnly === '-' || dateOnly === normalized) return dateOnly;

    let timePart = null;
    if (normalized.includes('T')) {
        timePart = normalized.slice(11, 16);
    } else if (normalized.includes(' ')) {
        timePart = normalized.split(/\s+/)[1]?.slice(0, 5) ?? null;
    }

    if (!timePart || timePart === '00:00') return dateOnly;

    return `${dateOnly}, ${timePart}`;
}

export function useDateFormat() {
    return { formatDate, formatDateTime };
}
