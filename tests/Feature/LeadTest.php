<?php

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('leads.index'));

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
            ->has('leads', 3)
        );
    });

    test('a lead can be created manually with default status new', function () {
        $data = [
            'company_name' => 'Clínica Exemplo',
            'category' => 'Clínica odontológica',
            'phone' => '5581999999999',
            'email' => 'contato@exemplo.com.br',
            'website' => 'exemplo.com.br',
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

    test('a lead can be updated', function () {
        $lead = Lead::factory()->create(['company_name' => 'Old Name']);

        $response = $this->put(route('leads.update', $lead), [
            'company_name' => 'New Name',
            'category' => $lead->category,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'website' => $lead->website,
            'instagram' => $lead->instagram,
            'city' => $lead->city,
            'state' => $lead->state,
            'notes' => 'Updated notes',
        ]);

        $response->assertRedirect(route('leads.show', $lead));

        expect($lead->fresh()->company_name)->toBe('New Name');
        expect($lead->fresh()->notes)->toBe('Updated notes');
    });

    test('a lead can be deleted', function () {
        $lead = Lead::factory()->create();

        $response = $this->delete(route('leads.destroy', $lead));

        $response->assertRedirect(route('leads.index'));
        expect(Lead::find($lead->id))->toBeNull();
    });
});
