<?php

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('leads.index'));

    $response->assertRedirect(route('login'));
});

test('guests are redirected to the login page when bulk deleting leads', function () {
    $lead = Lead::factory()->create();

    $response = $this->delete(route('leads.bulk-destroy'), ['ids' => [$lead->id]]);

    $response->assertRedirect(route('login'));
});

describe('as an authenticated user', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
    });

    test('leads index lists all leads', function () {
        Lead::factory()->count(3)->create();

        $response = $this->get(route('leads.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('leads/Index')
            ->has('leads.data', 3)
        );
    });

    test('leads index exposes the normalized whatsapp phone for a lead with a valid phone', function () {
        Lead::factory()->create(['phone' => '(81) 3482-7554']);

        $response = $this->get(route('leads.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('leads.data.0.whatsapp_phone', '558134827554')
        );
    });

    test('leads index exposes no whatsapp phone for a lead without a phone', function () {
        Lead::factory()->create(['phone' => null]);

        $response = $this->get(route('leads.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('leads.data.0.whatsapp_phone', null)
        );
    });

    test('leads index exposes the user\'s message templates rendered for each lead', function () {
        Lead::factory()->create(['company_name' => 'Padaria do João', 'city' => 'Recife']);
        $template = MessageTemplate::factory()->create(['name' => 'Primeiro contato', 'content' => 'Olá {nome}, tudo bem em {cidade}?']);

        $response = $this->get(route('leads.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('leads.data.0.message_templates.0.id', $template->id)
            ->where('leads.data.0.message_templates.0.name', 'Primeiro contato')
            ->where('leads.data.0.message_templates.0.renderedContent', 'Olá Padaria do João, tudo bem em Recife?')
        );
    });

    test('leads index exposes an empty message templates list for a lead when the user has no templates', function () {
        Lead::factory()->create();

        $response = $this->get(route('leads.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('leads.data.0.message_templates', [])
        );
    });

    test('a lead can be created manually with default status new', function () {
        $data = [
            'company_name' => 'Clínica Exemplo',
            'category' => 'Clínica odontológica',
            'phone' => '5581999999999',
            'email' => 'contato@exemplo.com.br',
            'website' => 'https://exemplo.com.br',
            'instagram' => '@clinicaexemplo',
            'city' => 'Recife',
            'state' => 'PE',
            'notes' => 'Observações sobre o prospect.',
        ];

        $response = $this->post(route('leads.store'), $data);

        $lead = Lead::firstWhere('company_name', 'Clínica Exemplo');

        expect($lead)->not->toBeNull();
        expect($lead->status)->toBe(LeadStatus::NEW);
        expect($lead->city)->toBe('Recife');

        $response->assertRedirect(route('leads.show', $lead));
    });

    test('creating a lead requires a company name', function () {
        $response = $this->post(route('leads.store'), [
            'company_name' => '',
        ]);

        $response->assertSessionHasErrors('company_name');
    });

    test('a lead can be viewed', function () {
        $lead = Lead::factory()->create();

        $response = $this->get(route('leads.show', $lead));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('leads/Show')
            ->where('lead.id', $lead->id)
        );
    });

    test('a lead with a valid phone exposes a normalized whatsapp phone', function () {
        $lead = Lead::factory()->create(['phone' => '(81) 3482-7554']);

        $response = $this->get(route('leads.show', $lead));

        $response->assertInertia(fn ($page) => $page
            ->where('lead.whatsapp_phone', '558134827554')
        );
    });

    test('a lead without a phone exposes no whatsapp phone', function () {
        $lead = Lead::factory()->create(['phone' => null]);

        $response = $this->get(route('leads.show', $lead));

        $response->assertInertia(fn ($page) => $page
            ->where('lead.whatsapp_phone', null)
        );
    });

    test('a lead exposes the user\'s message templates rendered for it', function () {
        $lead = Lead::factory()->create(['company_name' => 'Padaria do João', 'city' => 'Recife']);
        $template = MessageTemplate::factory()->create(['name' => 'Primeiro contato', 'content' => 'Olá {nome}, tudo bem em {cidade}?']);

        $response = $this->get(route('leads.show', $lead));

        $response->assertInertia(fn ($page) => $page
            ->where('messageTemplates.0.id', $template->id)
            ->where('messageTemplates.0.name', 'Primeiro contato')
            ->where('messageTemplates.0.renderedContent', 'Olá Padaria do João, tudo bem em Recife?')
        );
    });

    test('a lead with no message templates exposes an empty list', function () {
        $lead = Lead::factory()->create();

        $response = $this->get(route('leads.show', $lead));

        $response->assertInertia(fn ($page) => $page
            ->where('messageTemplates', [])
        );
    });

    test('a lead only exposes the acting user\'s own message templates', function () {
        $lead = Lead::factory()->create();
        MessageTemplate::factory()->count(2)->create(['user_id' => User::factory()->create()->id]);
        $own = MessageTemplate::factory()->create();

        $response = $this->get(route('leads.show', $lead));

        $response->assertInertia(fn ($page) => $page
            ->has('messageTemplates', 1)
            ->where('messageTemplates.0.id', $own->id)
        );
    });

    test('a lead can be updated', function () {
        $lead = Lead::factory()->create(['company_name' => 'Old Name']);

        $response = $this->put(route('leads.update', $lead), [
            'company_name' => 'New Name',
            'category' => $lead->category,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'website' => 'https://'.fake()->domainName(),
            'instagram' => $lead->instagram,
            'city' => $lead->city,
            'state' => $lead->state,
            'notes' => 'Updated notes',
        ]);

        $response->assertRedirect(route('leads.show', $lead));

        expect($lead->fresh()->company_name)->toBe('New Name');
        expect($lead->fresh()->notes)->toBe('Updated notes');
    });

    test('notes can be added to a lead with no notes', function () {
        $lead = Lead::factory()->create(['notes' => null]);

        $response = $this->patch(route('leads.notes.update', $lead), [
            'notes' => 'First contact went well.',
        ]);

        $response->assertRedirect();
        expect($lead->fresh()->notes)->toBe('First contact went well.');
    });

    test('existing notes can be edited independently of the lead edit form', function () {
        $lead = Lead::factory()->create([
            'company_name' => 'Untouched Company',
            'notes' => 'Old note.',
        ]);

        $response = $this->patch(route('leads.notes.update', $lead), [
            'notes' => 'Updated note.',
        ]);

        $response->assertRedirect();
        expect($lead->fresh()->notes)->toBe('Updated note.');
        expect($lead->fresh()->company_name)->toBe('Untouched Company');
    });

    test('updating notes does not require the lead other fields', function () {
        $lead = Lead::factory()->create();

        $response = $this->patch(route('leads.notes.update', $lead), [
            'notes' => 'Standalone note update.',
        ]);

        $response->assertSessionDoesntHaveErrors();
    });

    test('a lead status can be updated', function () {
        $lead = Lead::factory()->create(['status' => LeadStatus::NEW]);

        $response = $this->patch(route('leads.status.update', $lead), [
            'status' => LeadStatus::MEETING->value,
        ]);

        $response->assertRedirect();
        expect($lead->fresh()->status)->toBe(LeadStatus::MEETING);
    });

    test('updating a lead status requires a valid enum value', function () {
        $lead = Lead::factory()->create(['status' => LeadStatus::NEW]);

        $response = $this->patch(route('leads.status.update', $lead), [
            'status' => 'not-a-real-status',
        ]);

        $response->assertSessionHasErrors('status');
        expect($lead->fresh()->status)->toBe(LeadStatus::NEW);
    });

    test('leads index can be searched by company name', function () {
        Lead::factory()->create(['company_name' => 'Clínica Sorriso']);
        Lead::factory()->create(['company_name' => 'Restaurante Sabor']);

        $response = $this->get(route('leads.index', ['search' => 'Sorriso']));

        $response->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.company_name', 'Clínica Sorriso')
        );
    });

    test('leads index can be filtered by status', function () {
        Lead::factory()->create(['status' => LeadStatus::NEW]);
        Lead::factory()->create(['status' => LeadStatus::WON]);

        $response = $this->get(route('leads.index', ['status' => LeadStatus::WON->value]));

        $response->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.status', LeadStatus::WON->value)
        );
    });

    test('leads index can be filtered by category', function () {
        Lead::factory()->create(['category' => 'Restaurante']);
        Lead::factory()->create(['category' => 'Advocacia']);

        $response = $this->get(route('leads.index', ['category' => 'Advocacia']));

        $response->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.category', 'Advocacia')
        );
    });

    test('leads index can be filtered by city', function () {
        Lead::factory()->create(['city' => 'Recife']);
        Lead::factory()->create(['city' => 'Olinda']);

        $response = $this->get(route('leads.index', ['city' => 'Olinda']));

        $response->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.city', 'Olinda')
        );
    });

    test('leads index can be filtered by phone regardless of formatting', function () {
        Lead::factory()->create(['phone' => '(81) 99999-1234']);
        Lead::factory()->create(['phone' => '5511988887777']);

        $response = $this->get(route('leads.index', ['phone' => '999991234']));

        $response->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.phone', '(81) 99999-1234')
        );
    });

    test('leads index can be filtered by phone typed with a different mask than stored', function () {
        Lead::factory()->create(['phone' => '81999991234']);
        Lead::factory()->create(['phone' => '5511988887777']);

        $response = $this->get(route('leads.index', ['phone' => '(81) 99999-1234']));

        $response->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.phone', '81999991234')
        );
    });

    test('leads index combines the phone filter with the company search filter', function () {
        Lead::factory()->create(['company_name' => 'Clínica Sorriso', 'phone' => '81999991234']);
        Lead::factory()->create(['company_name' => 'Restaurante Sabor', 'phone' => '81999991234']);
        Lead::factory()->create(['company_name' => 'Clínica Sorriso', 'phone' => '5511988887777']);

        $response = $this->get(route('leads.index', ['search' => 'Sorriso', 'phone' => '999991234']));

        $response->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.company_name', 'Clínica Sorriso')
            ->where('leads.data.0.phone', '81999991234')
        );
    });

    test('leads index can be sorted by score ascending', function () {
        Lead::factory()->create(['company_name' => 'Baixo Score', 'score' => 10]);
        Lead::factory()->create(['company_name' => 'Alto Score', 'score' => 90]);

        $response = $this->get(route('leads.index', ['sort' => 'score', 'direction' => 'asc']));

        $response->assertInertia(fn ($page) => $page
            ->where('leads.data.0.company_name', 'Baixo Score')
            ->where('leads.data.1.company_name', 'Alto Score')
        );
    });

    test('leads index can be sorted by company name descending', function () {
        Lead::factory()->create(['company_name' => 'Alfa']);
        Lead::factory()->create(['company_name' => 'Zeta']);

        $response = $this->get(route('leads.index', ['sort' => 'company_name', 'direction' => 'desc']));

        $response->assertInertia(fn ($page) => $page
            ->where('leads.data.0.company_name', 'Zeta')
            ->where('leads.data.1.company_name', 'Alfa')
        );
    });

    test('leads index ignores an invalid sort column', function () {
        Lead::factory()->create(['company_name' => 'Older Lead', 'created_at' => now()->subDay()]);
        Lead::factory()->create(['company_name' => 'Newer Lead', 'created_at' => now()]);

        $response = $this->get(route('leads.index', ['sort' => 'notes']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('leads.data.0.company_name', 'Newer Lead')
        );
    });

    test('leads index paginates results and reports pagination metadata', function () {
        Lead::factory()->count(20)->create();

        $response = $this->get(route('leads.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('leads.data', 15)
            ->where('leads.current_page', 1)
            ->where('leads.last_page', 2)
            ->where('leads.per_page', 15)
            ->where('leads.total', 20)
        );

        $response = $this->get(route('leads.index', ['page' => 2]));

        $response->assertInertia(fn ($page) => $page
            ->has('leads.data', 5)
            ->where('leads.current_page', 2)
        );
    });

    test('a filter narrows the paginated total across the whole result set, not just the current page', function () {
        Lead::factory()->count(20)->create(['status' => LeadStatus::NEW]);
        Lead::factory()->create(['status' => LeadStatus::WON, 'company_name' => 'Won Company']);

        $response = $this->get(route('leads.index', ['status' => LeadStatus::WON->value]));

        $response->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.company_name', 'Won Company')
            ->where('leads.current_page', 1)
            ->where('leads.last_page', 1)
            ->where('leads.total', 1)
        );
    });

    test('a lead can be deleted', function () {
        $lead = Lead::factory()->create();

        $response = $this->delete(route('leads.destroy', $lead));

        $response->assertRedirect(route('leads.index'));
        expect(Lead::find($lead->id))->toBeNull();
    });

    test('leads can be bulk deleted', function () {
        $leads = Lead::factory()->count(3)->create();

        $response = $this->delete(route('leads.bulk-destroy'), [
            'ids' => $leads->pluck('id')->all(),
        ]);

        $response->assertRedirect();
        expect(Lead::whereIn('id', $leads->pluck('id'))->count())->toBe(0);
    });

    test('bulk delete flashes a toast reporting how many leads were deleted', function () {
        $leads = Lead::factory()->count(2)->create();

        $response = $this->delete(route('leads.bulk-destroy'), [
            'ids' => $leads->pluck('id')->all(),
        ]);

        $response->assertSessionHas('inertia.flash_data', [
            'toast' => ['type' => 'success', 'message' => __(':count leads excluídos.', ['count' => 2])],
        ]);
    });

    test('bulk delete preserves current filters and pagination on redirect', function () {
        $lead = Lead::factory()->create();

        $response = $this
            ->from(route('leads.index', ['status' => LeadStatus::WON->value, 'page' => 2]))
            ->delete(route('leads.bulk-destroy'), ['ids' => [$lead->id]]);

        $response->assertRedirect(route('leads.index', ['status' => LeadStatus::WON->value, 'page' => 2]));
    });

    test('bulk delete does not delete a lead belonging to another user, but succeeds for the caller\'s own leads', function () {
        $ownLead = Lead::factory()->create();
        $otherLead = Lead::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->delete(route('leads.bulk-destroy'), [
            'ids' => [$ownLead->id, $otherLead->id],
        ]);

        $response->assertRedirect();
        expect(Lead::find($ownLead->id))->toBeNull();
        expect(Lead::withoutGlobalScopes()->find($otherLead->id))->not->toBeNull();
    });

    test('bulk delete rejects an empty ids array', function () {
        $response = $this->delete(route('leads.bulk-destroy'), ['ids' => []]);

        $response->assertSessionHasErrors('ids');
    });

    test('bulk delete rejects a non-existent lead id', function () {
        $response = $this->delete(route('leads.bulk-destroy'), ['ids' => [999999]]);

        $response->assertSessionHasErrors('ids.0');
    });

    test('leads index does not list leads belonging to another user', function () {
        Lead::factory()->count(2)->create(['user_id' => User::factory()->create()->id]);
        Lead::factory()->create();

        $response = $this->get(route('leads.index'));

        $response->assertInertia(fn ($page) => $page->has('leads.data', 1));
    });

    test('a lead belonging to another user returns 404', function () {
        $lead = Lead::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->get(route('leads.show', $lead));

        $response->assertNotFound();
    });

    test('a lead created manually is owned by the acting user', function () {
        $data = [
            'company_name' => 'Clínica Exemplo',
        ];

        $this->post(route('leads.store'), $data);

        $lead = Lead::firstWhere('company_name', 'Clínica Exemplo');

        expect($lead->user_id)->toBe(auth()->id());
    });
});
