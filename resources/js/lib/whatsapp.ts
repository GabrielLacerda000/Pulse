import { WHATSAPP_DEFAULT_MESSAGE } from '@/constants/whatsapp';

export function buildWhatsAppUrl(
    normalizedPhone: string,
    message: string = WHATSAPP_DEFAULT_MESSAGE,
): string {
    return `https://wa.me/${normalizedPhone}?text=${encodeURIComponent(message)}`;
}
