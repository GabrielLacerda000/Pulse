<?php

namespace App\Services;

use App\Concerns\LogsStructuredEvents;
use App\Data\PulseWppHealthData;
use App\Data\SendMessageResultData;
use App\Data\WhatsAppQrData;
use App\Data\WhatsAppStatusData;
use App\Exceptions\PulseWppException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class PulseWppClient
{
    use LogsStructuredEvents;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {}

    /**
     * Unauthenticated infrastructure health check. Not indicative of whether
     * WhatsApp itself is connected — use status() for that.
     */
    public function health(): PulseWppHealthData
    {
        return PulseWppHealthData::from(
            $this->response($this->send(fn (): Response => Http::baseUrl($this->baseUrl)->acceptJson()->get('/health')))->json()
        );
    }

    public function status(): WhatsAppStatusData
    {
        return WhatsAppStatusData::from($this->get('/whatsapp/status')->json());
    }

    public function qr(): WhatsAppQrData
    {
        return WhatsAppQrData::from($this->get('/whatsapp/qr')->json());
    }

    public function connect(): WhatsAppStatusData
    {
        return WhatsAppStatusData::from($this->post('/whatsapp/connect')->json());
    }

    public function disconnect(): WhatsAppStatusData
    {
        return WhatsAppStatusData::from($this->post('/whatsapp/disconnect')->json());
    }

    public function sendMessage(string $phone, string $message): SendMessageResultData
    {
        return SendMessageResultData::from($this->post('/messages', [
            'phone' => $phone,
            'message' => $message,
        ])->json());
    }

    private function get(string $endpoint): Response
    {
        return $this->authenticated($endpoint, fn (): Response => $this->request()->get($endpoint));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function post(string $endpoint, array $data = []): Response
    {
        return $this->authenticated($endpoint, fn (): Response => $this->request()->post($endpoint, $data));
    }

    /**
     * Runs an authenticated request, logging whatsapp.api.request_failed
     * before rethrowing on a PulseWppException. health() intentionally
     * bypasses this (and the logging) since it isn't a WhatsApp API call.
     */
    private function authenticated(string $endpoint, callable $request): Response
    {
        try {
            return $this->response($this->send($request));
        } catch (PulseWppException $exception) {
            $this->logEvent('whatsapp.api.request_failed', [
                'endpoint' => $endpoint,
                'error_code' => $exception->errorCode->value,
                'error' => ['message' => $exception->getMessage()],
            ], 'warning');

            throw $exception;
        }
    }

    /**
     * Runs an HTTP call, converting a connection failure (pulse-wpp
     * offline/unreachable, no HTTP response received) into the same
     * exception type thrown for an error response.
     */
    private function send(callable $request): Response
    {
        try {
            return $request();
        } catch (ConnectionException) {
            throw PulseWppException::unreachable();
        }
    }

    private function response(Response $response): Response
    {
        if ($response->failed()) {
            throw PulseWppException::fromResponse($response);
        }

        return $response;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)->withToken($this->apiKey)->acceptJson();
    }
}
