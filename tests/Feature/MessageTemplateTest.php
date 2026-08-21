<?php

use App\Models\MessageTemplate;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('message-templates.index'));

    $response->assertRedirect(route('login'));
});

describe('as an authenticated user', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
    });

    test('a message template created without an explicit user is owned by the acting user', function () {
        $template = MessageTemplate::factory()->create();

        expect($template->user_id)->toBe(auth()->id());
    });

    test('a user cannot see another user\'s message templates through normal queries', function () {
        MessageTemplate::factory()->count(2)->create(['user_id' => User::factory()->create()->id]);
        $own = MessageTemplate::factory()->create();

        $templates = MessageTemplate::all();

        expect($templates)->toHaveCount(1);
        expect($templates->first()->id)->toBe($own->id);
    });

    test('message templates index lists only the user\'s own templates', function () {
        MessageTemplate::factory()->count(2)->create(['user_id' => User::factory()->create()->id]);
        MessageTemplate::factory()->create();

        $response = $this->get(route('message-templates.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('message-templates/Index')
            ->has('messageTemplates.data', 1)
        );
    });

    test('a user can create a message template', function () {
        $data = [
            'name' => 'Primeiro contato',
            'content' => 'Olá {nome}, tudo bem?',
        ];

        $response = $this->post(route('message-templates.store'), $data);

        $response->assertRedirect(route('message-templates.index'));

        $template = MessageTemplate::firstWhere('name', 'Primeiro contato');
        expect($template)->not->toBeNull();
        expect($template->content)->toBe('Olá {nome}, tudo bem?');
        expect($template->channel)->toBe('whatsapp');
        expect($template->user_id)->toBe(auth()->id());
    });

    test('creating a message template requires a name and content', function () {
        $response = $this->post(route('message-templates.store'), []);

        $response->assertInvalid(['name', 'content']);
    });

    test('a user can edit their own message template', function () {
        $template = MessageTemplate::factory()->create();

        $response = $this->get(route('message-templates.edit', $template));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('message-templates/Edit')
            ->where('messageTemplate.id', $template->id)
        );
    });

    test('a user can update their own message template', function () {
        $template = MessageTemplate::factory()->create(['name' => 'Original']);

        $response = $this->put(route('message-templates.update', $template), [
            'name' => 'Atualizado',
            'content' => 'Novo texto para {nome} em {cidade}',
        ]);

        $response->assertRedirect(route('message-templates.index'));

        expect($template->fresh()->name)->toBe('Atualizado');
        expect($template->fresh()->content)->toBe('Novo texto para {nome} em {cidade}');
    });

    test('a user can delete their own message template', function () {
        $template = MessageTemplate::factory()->create();

        $response = $this->delete(route('message-templates.destroy', $template));

        $response->assertRedirect(route('message-templates.index'));
        expect(MessageTemplate::find($template->id))->toBeNull();
    });

    test('a message template belonging to another user returns 404 when editing', function () {
        $template = MessageTemplate::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->get(route('message-templates.edit', $template));

        $response->assertNotFound();
    });

    test('a message template belonging to another user returns 404 when updating', function () {
        $template = MessageTemplate::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->put(route('message-templates.update', $template), [
            'name' => 'Hijacked',
            'content' => 'Hijacked content',
        ]);

        $response->assertNotFound();
        expect($template->fresh()->name)->not->toBe('Hijacked');
    });

    test('a message template belonging to another user returns 404 when deleting', function () {
        $template = MessageTemplate::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->delete(route('message-templates.destroy', $template));

        $response->assertNotFound();
        expect(MessageTemplate::withoutGlobalScopes()->find($template->id))->not->toBeNull();
    });
});
