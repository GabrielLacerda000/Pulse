import type { PulseWppErrorCode } from '@/types';

export const PULSE_WPP_ERROR_CODE_LABELS: Record<PulseWppErrorCode, string> = {
    UNAUTHORIZED: 'Erro de autorização',
    INVALID_REQUEST: 'Requisição inválida',
    WHATSAPP_NOT_CONNECTED: 'WhatsApp não conectado',
    WHATSAPP_AUTH_REQUIRED: 'Sessão do WhatsApp expirada',
    MESSAGE_SEND_FAILED: 'Falha ao enviar a mensagem',
    PROVIDER_UNAVAILABLE: 'Serviço de envio indisponível',
    PROVIDER_UNREACHABLE: 'Serviço de envio inacessível',
    INTERNAL_ERROR: 'Erro interno',
};
