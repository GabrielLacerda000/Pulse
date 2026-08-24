<?php

namespace App\Enums;

enum WhatsAppConnectionStatus: string
{
    case DISCONNECTED = 'disconnected';
    case CONNECTING = 'connecting';
    case QR_REQUIRED = 'qr_required';
    case CONNECTED = 'connected';
    case LOGGED_OUT = 'logged_out';
}
