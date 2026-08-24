<?php

namespace App\Http\Controllers;

use App\Enums\WhatsAppConnectionStatus;
use App\Exceptions\PulseWppException;
use App\Services\PulseWppClient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppConnectionController extends Controller
{
    /**
     * Show the WhatsApp connection status, rendering the pending QR code
     * when one is required.
     */
    public function show(PulseWppClient $client): Response
    {
        try {
            $status = $client->status();
        } catch (PulseWppException $exception) {
            return Inertia::render('whatsapp/Show', [
                'connection' => null,
                'error' => $exception->getMessage(),
            ]);
        }

        $qr = $status->status === WhatsAppConnectionStatus::QR_REQUIRED
            ? $client->qr()->qr
            : null;

        return Inertia::render('whatsapp/Show', [
            'connection' => [
                'status' => $status->status,
                'phone' => $status->phone,
                'qr' => $qr,
            ],
            'error' => null,
        ]);
    }

    /**
     * Start or resume the WhatsApp connection.
     */
    public function connect(PulseWppClient $client): RedirectResponse
    {
        try {
            $client->connect();
        } catch (PulseWppException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        return to_route('whatsapp.show');
    }

    /**
     * Disconnect the local WhatsApp socket, keeping the session for later reconnects.
     */
    public function disconnect(PulseWppClient $client): RedirectResponse
    {
        try {
            $client->disconnect();
        } catch (PulseWppException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        return to_route('whatsapp.show');
    }
}
