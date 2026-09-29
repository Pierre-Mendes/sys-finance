const HTML_ESCAPES: Record<string, string> = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
}

/**
 * Escapa texto para uso em APIs que interpretam HTML (innerHTML, SweetAlert `html`, ApexCharts).
 * Templates Vue ({{ }}) já escapam sozinhos; use isto apenas fora deles.
 */
export function escapeHtml(value: unknown): string {
    return String(value ?? '').replace(/[&<>"']/g, ch => HTML_ESCAPES[ch])
}
