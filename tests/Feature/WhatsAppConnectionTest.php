<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('whatsapp.show'));

    $response->assertRedirect(route('login'));
});

describe('as an authenticated user', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
    });

    test('shows the connected status with the phone number', function () {
        Http::fake([
            'pulse-wpp.test/whatsapp/status' => Http::response([
                'status' => 'connected',
                'phone' => '5582999999999',
            ]),
        ]);

        $response = $this->get(route('whatsapp.show'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('whatsapp/Show')
            ->where('connection.status', 'connected')
            ->where('connection.phone', '5582999999999')
            ->where('connection.qr', null)
            ->where('error', null)
        );

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/whatsapp/qr'));
    });

    test('shows the disconnected status', function () {
        Http::fake([
            'pulse-wpp.test/whatsapp/status' => Http::response(['status' => 'disconnected']),
        ]);

        $response = $this->get(route('whatsapp.show'));

        $response->assertInertia(fn ($page) => $page
            ->where('connection.status', 'disconnected')
        );
    });

    test('logged_out is visually distinguished from disconnected in the payload', function () {
        Http::fake([
            'pulse-wpp.test/whatsapp/status' => Http::response(['status' => 'logged_out']),
        ]);

        $response = $this->get(route('whatsapp.show'));

        $response->assertInertia(fn ($page) => $page
            ->where('connection.status', 'logged_out')
        );
    });

    test('fetches and renders the pending qr code when qr_required', function () {
        Http::fake([
            'pulse-wpp.test/whatsapp/status' => Http::response(['status' => 'qr_required']),
            'pulse-wpp.test/whatsapp/qr' => Http::response(['qr' => '2@AbC123...']),
        ]);

        $response = $this->get(route('whatsapp.show'));

        $response->assertInertia(fn ($page) => $page
            ->where('connection.status', 'qr_required')
            ->where('connection.qr', '2@AbC123...')
        );
    });

    test('each stable error code from pulse-wpp is surfaced on the page without a connection payload', function (string $code, int $status) {
        Http::fake([
            'pulse-wpp.test/whatsapp/status' => Http::response([
                'success' => false,
                'error' => ['code' => $code, 'message' => "error: {$code}"],
            ], $status),
        ]);

        $response = $this->get(route('whatsapp.show'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('whatsapp/Show')
            ->where('connection', null)
            ->where('error', "error: {$code}")
        );
    })->with([
        ['UNAUTHORIZED', 401],
        ['INVALID_REQUEST', 400],
        ['WHATSAPP_NOT_CONNECTED', 409],
        ['WHATSAPP_AUTH_REQUIRED', 401],
        ['MESSAGE_SEND_FAILED', 502],
        ['PROVIDER_UNAVAILABLE', 503],
        ['INTERNAL_ERROR', 500],
    ]);

    test('shows a friendly error when pulse-wpp is offline/unreachable', function () {
        Http::fake([
            'pulse-wpp.test/whatsapp/status' => Http::failedConnection(),
        ]);

        $response = $this->get(route('whatsapp.show'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('whatsapp/Show')
            ->where('connection', null)
            ->where('error', 'Não foi possível se conectar ao servidor do WhatsApp.')
        );
    });

    test('a user can trigger a connect', function () {
        Http::fake([
            'pulse-wpp.test/whatsapp/connect' => Http::response(['status' => 'qr_required']),
        ]);

        $response = $this->post(route('whatsapp.connect'));

        $response->assertRedirect(route('whatsapp.show'));
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'http://pulse-wpp.test/whatsapp/connect');
    });

    test('a failed connect still redirects back with an error toast, for each stable error code', function (string $code, int $status) {
        Http::fake([
            'pulse-wpp.test/whatsapp/connect' => Http::response([
                'success' => false,
                'error' => ['code' => $code, 'message' => "error: {$code}"],
            ], $status),
        ]);

        $response = $this->post(route('whatsapp.connect'));

        $response->assertRedirect(route('whatsapp.show'));
        $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => "error: {$code}"]);
    })->with([
        ['UNAUTHORIZED', 401],
        ['INVALID_REQUEST', 400],
        ['WHATSAPP_NOT_CONNECTED', 409],
        ['WHATSAPP_AUTH_REQUIRED', 401],
        ['MESSAGE_SEND_FAILED', 502],
        ['PROVIDER_UNAVAILABLE', 503],
        ['INTERNAL_ERROR', 500],
    ]);

    test('a connect while pulse-wpp is offline/unreachable still redirects back with an error toast', function () {
        Http::fake([
            'pulse-wpp.test/whatsapp/connect' => Http::failedConnection(),
        ]);

        $response = $this->post(route('whatsapp.connect'));

        $response->assertRedirect(route('whatsapp.show'));
        $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Não foi possível se conectar ao servidor do WhatsApp.']);
    });

    test('a user can trigger a disconnect', function () {
        Http::fake([
            'pulse-wpp.test/whatsapp/disconnect' => Http::response(['status' => 'disconnected']),
        ]);

        $response = $this->post(route('whatsapp.disconnect'));

        $response->assertRedirect(route('whatsapp.show'));
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'http://pulse-wpp.test/whatsapp/disconnect');
    });

    test('a failed disconnect still redirects back with an error toast, for each stable error code', function (string $code, int $status) {
        Http::fake([
            'pulse-wpp.test/whatsapp/disconnect' => Http::response([
                'success' => false,
                'error' => ['code' => $code, 'message' => "error: {$code}"],
            ], $status),
        ]);

        $response = $this->post(route('whatsapp.disconnect'));

        $response->assertRedirect(route('whatsapp.show'));
        $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => "error: {$code}"]);
    })->with([
        ['UNAUTHORIZED', 401],
        ['INVALID_REQUEST', 400],
        ['WHATSAPP_NOT_CONNECTED', 409],
        ['WHATSAPP_AUTH_REQUIRED', 401],
        ['MESSAGE_SEND_FAILED', 502],
        ['PROVIDER_UNAVAILABLE', 503],
        ['INTERNAL_ERROR', 500],
    ]);

    test('a disconnect while pulse-wpp is offline/unreachable still redirects back with an error toast', function () {
        Http::fake([
            'pulse-wpp.test/whatsapp/disconnect' => Http::failedConnection(),
        ]);

        $response = $this->post(route('whatsapp.disconnect'));

        $response->assertRedirect(route('whatsapp.show'));
        $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Não foi possível se conectar ao servidor do WhatsApp.']);
    });
});
