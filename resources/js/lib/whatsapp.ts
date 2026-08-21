export function buildWhatsAppUrl(
    normalizedPhone: string,
    message: string,
): string {
    return `https://wa.me/${normalizedPhone}?text=${encodeURIComponent(message)}`;
}
