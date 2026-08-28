<?php

use App\Data\PulseWppHealthData;
use App\Data\SendMessageResultData;
use App\Data\WhatsAppQrData;
use App\Data\WhatsAppStatusData;
use App\Enums\PulseWppErrorCode;
use App\Enums\WhatsAppConnectionStatus;
use App\Exceptions\PulseWppException;
use App\Services\PulseWppClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->client = new PulseWppClient(
        baseUrl: 'http://pulse-wpp.test',
        apiKey: 'testing-api-key',
    );

    $this->channel = Mockery::spy();
    Log::shouldReceive('channel')->with('campaigns')->andReturn($this->channel);
});

test('health returns a typed PulseWppHealthData without sending an auth header', function () {
    Http::fake([
        'pulse-wpp.test/health' => Http::response(['status' => 'ok']),
    ]);

    $result = $this->client->health();

    expect($result)->toBeInstanceOf(PulseWppHealthData::class);
    expect($result->status)->toBe('ok');

    Http::assertSent(fn ($request) => ! $request->hasHeader('Authorization')
        && $request->url() === 'http://pulse-wpp.test/health');
});

test('status returns a typed WhatsAppStatusData', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/status' => Http::response([
            'status' => 'connected',
            'phone' => '5582999999999',
        ]),
    ]);

    $result = $this->client->status();

    expect($result)->toBeInstanceOf(WhatsAppStatusData::class);
    expect($result->status)->toBe(WhatsAppConnectionStatus::CONNECTED);
    expect($result->phone)->toBe('5582999999999');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer testing-api-key')
        && $request->url() === 'http://pulse-wpp.test/whatsapp/status');
});

test('qr returns a typed WhatsAppQrData', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/qr' => Http::response(['qr' => '2@AbC123...']),
    ]);

    $result = $this->client->qr();

    expect($result)->toBeInstanceOf(WhatsAppQrData::class);
    expect($result->qr)->toBe('2@AbC123...');
});

test('qr returns null when no qr is pending', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/qr' => Http::response(['qr' => null]),
    ]);

    expect($this->client->qr()->qr)->toBeNull();
});

test('connect returns the resulting status', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/connect' => Http::response([
            'status' => 'qr_required',
        ]),
    ]);

    $result = $this->client->connect();

    expect($result->status)->toBe(WhatsAppConnectionStatus::QR_REQUIRED);

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && $request->url() === 'http://pulse-wpp.test/whatsapp/connect');
});

test('disconnect returns the resulting status', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/disconnect' => Http::response([
            'status' => 'disconnected',
        ]),
    ]);

    $result = $this->client->disconnect();

    expect($result->status)->toBe(WhatsAppConnectionStatus::DISCONNECTED);
});

test('sendMessage returns a typed SendMessageResultData', function () {
    Http::fake([
        'pulse-wpp.test/messages' => Http::response([
            'success' => true,
            'messageId' => 'msg-123',
        ]),
    ]);

    $result = $this->client->sendMessage('5582999999999', 'Olá!');

    expect($result)->toBeInstanceOf(SendMessageResultData::class);
    expect($result->messageId)->toBe('msg-123');

    Http::assertSent(fn ($request) => $request['phone'] === '5582999999999'
        && $request['message'] === 'Olá!');
});

test('each stable error code is mapped to the local enum, never matched on error.message', function (string $code, int $status) {
    Http::fake([
        'pulse-wpp.test/whatsapp/status' => Http::response([
            'success' => false,
            'error' => ['code' => $code, 'message' => 'some message that could change any time'],
        ], $status),
    ]);

    try {
        $this->client->status();
        $this->fail('Expected a PulseWppException to be thrown.');
    } catch (PulseWppException $exception) {
        expect($exception->errorCode)->toBe(PulseWppErrorCode::from($code));
    }
})->with([
    ['UNAUTHORIZED', 401],
    ['INVALID_REQUEST', 400],
    ['WHATSAPP_NOT_CONNECTED', 409],
    ['WHATSAPP_AUTH_REQUIRED', 401],
    ['MESSAGE_SEND_FAILED', 502],
    ['PROVIDER_UNAVAILABLE', 503],
    ['INTERNAL_ERROR', 500],
]);

test('a connection failure (pulse-wpp offline/unreachable) is converted to a PulseWppException', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/status' => Http::failedConnection(),
    ]);

    try {
        $this->client->status();
        $this->fail('Expected a PulseWppException to be thrown.');
    } catch (PulseWppException $exception) {
        expect($exception->errorCode)->toBe(PulseWppErrorCode::PROVIDER_UNREACHABLE);
    }
});

test('an unmapped error code falls back to INTERNAL_ERROR', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/status' => Http::response([
            'success' => false,
            'error' => ['code' => 'SOMETHING_NEW', 'message' => 'unexpected'],
        ], 500),
    ]);

    try {
        $this->client->status();
        $this->fail('Expected a PulseWppException to be thrown.');
    } catch (PulseWppException $exception) {
        expect($exception->errorCode)->toBe(PulseWppErrorCode::INTERNAL_ERROR);
    }
});

test('status logs whatsapp.api.request_failed at warning with endpoint, error_code, and error.message', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/status' => Http::response([
            'success' => false,
            'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'boom'],
        ], 500),
    ]);

    try {
        $this->client->status();
    } catch (PulseWppException) {
        // expected
    }

    $this->channel->shouldHaveReceived('warning')->once()->withArgs(
        fn (string $event, array $context) => $event === 'whatsapp.api.request_failed'
            && $context['endpoint'] === '/whatsapp/status'
            && $context['error_code'] === 'INTERNAL_ERROR'
            && $context['error']['message'] === 'boom'
    );
});

test('qr logs whatsapp.api.request_failed on failure', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/qr' => Http::response([
            'success' => false,
            'error' => ['code' => 'WHATSAPP_NOT_CONNECTED', 'message' => 'not connected'],
        ], 409),
    ]);

    try {
        $this->client->qr();
    } catch (PulseWppException) {
        // expected
    }

    $this->channel->shouldHaveReceived('warning')->once()->withArgs(
        fn (string $event, array $context) => $event === 'whatsapp.api.request_failed'
            && $context['endpoint'] === '/whatsapp/qr'
            && $context['error_code'] === 'WHATSAPP_NOT_CONNECTED'
    );
});

test('connect logs whatsapp.api.request_failed on failure', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/connect' => Http::response([
            'success' => false,
            'error' => ['code' => 'PROVIDER_UNAVAILABLE', 'message' => 'unavailable'],
        ], 503),
    ]);

    try {
        $this->client->connect();
    } catch (PulseWppException) {
        // expected
    }

    $this->channel->shouldHaveReceived('warning')->once()->withArgs(
        fn (string $event, array $context) => $event === 'whatsapp.api.request_failed'
            && $context['endpoint'] === '/whatsapp/connect'
            && $context['error_code'] === 'PROVIDER_UNAVAILABLE'
    );
});

test('disconnect logs whatsapp.api.request_failed on failure', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/disconnect' => Http::response([
            'success' => false,
            'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'boom'],
        ], 500),
    ]);

    try {
        $this->client->disconnect();
    } catch (PulseWppException) {
        // expected
    }

    $this->channel->shouldHaveReceived('warning')->once()->withArgs(
        fn (string $event, array $context) => $event === 'whatsapp.api.request_failed'
            && $context['endpoint'] === '/whatsapp/disconnect'
            && $context['error_code'] === 'INTERNAL_ERROR'
    );
});

test('sendMessage logs whatsapp.api.request_failed on failure without leaking the phone number or message body', function () {
    Http::fake([
        'pulse-wpp.test/messages' => Http::response([
            'success' => false,
            'error' => ['code' => 'MESSAGE_SEND_FAILED', 'message' => 'send failed'],
        ], 502),
    ]);

    try {
        $this->client->sendMessage('5582999999999', 'segredo do corpo da mensagem');
    } catch (PulseWppException) {
        // expected
    }

    $this->channel->shouldHaveReceived('warning')->once()->withArgs(
        function (string $event, array $context) {
            expect(json_encode($context))
                ->not->toContain('5582999999999')
                ->not->toContain('segredo do corpo da mensagem');

            return $event === 'whatsapp.api.request_failed'
                && $context['endpoint'] === '/messages'
                && $context['error_code'] === 'MESSAGE_SEND_FAILED';
        }
    );
});

test('a connection failure also logs whatsapp.api.request_failed with the PROVIDER_UNREACHABLE code', function () {
    Http::fake([
        'pulse-wpp.test/whatsapp/status' => Http::failedConnection(),
    ]);

    try {
        $this->client->status();
    } catch (PulseWppException) {
        // expected
    }

    $this->channel->shouldHaveReceived('warning')->once()->withArgs(
        fn (string $event, array $context) => $event === 'whatsapp.api.request_failed'
            && $context['endpoint'] === '/whatsapp/status'
            && $context['error_code'] === 'PROVIDER_UNREACHABLE'
    );
});
