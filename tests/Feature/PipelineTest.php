<?php

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('pipeline.index'));

    $response->assertRedirect(route('login'));
});

describe('as an authenticated user', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
    });

    test('pipeline index lists all leads', function () {
        Lead::factory()->count(3)->create();

        $response = $this->get(route('pipeline.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('pipeline/Index')
            ->has('leads', 3)
        );
    });

    test('moving a lead card between columns updates its status', function () {
        $lead = Lead::factory()->create(['status' => LeadStatus::NEW]);

        $response = $this->patch(route('leads.status.update', $lead), [
            'status' => LeadStatus::PROPOSAL->value,
        ]);

        $response->assertRedirect();
        expect($lead->fresh()->status)->toBe(LeadStatus::PROPOSAL);
    });

    test('pipeline index can be searched by company name', function () {
        Lead::factory()->create(['company_name' => 'Clínica Sorriso']);
        Lead::factory()->create(['company_name' => 'Restaurante Sabor']);

        $response = $this->get(route('pipeline.index', ['search' => 'Sorriso']));

        $response->assertInertia(fn ($page) => $page
            ->has('leads', 1)
            ->where('leads.0.company_name', 'Clínica Sorriso')
        );
    });

    test('pipeline index can be filtered by status', function () {
        Lead::factory()->create(['status' => LeadStatus::NEW]);
        Lead::factory()->create(['status' => LeadStatus::WON]);

        $response = $this->get(route('pipeline.index', ['status' => LeadStatus::WON->value]));

        $response->assertInertia(fn ($page) => $page
            ->has('leads', 1)
            ->where('leads.0.status', LeadStatus::WON->value)
        );
    });

    test('pipeline index can be filtered by category', function () {
        Lead::factory()->create(['category' => 'Restaurante']);
        Lead::factory()->create(['category' => 'Advocacia']);

        $response = $this->get(route('pipeline.index', ['category' => 'Advocacia']));

        $response->assertInertia(fn ($page) => $page
            ->has('leads', 1)
            ->where('leads.0.category', 'Advocacia')
        );
    });

    test('pipeline index can be filtered by city', function () {
        Lead::factory()->create(['city' => 'Recife']);
        Lead::factory()->create(['city' => 'Olinda']);

        $response = $this->get(route('pipeline.index', ['city' => 'Olinda']));

        $response->assertInertia(fn ($page) => $page
            ->has('leads', 1)
            ->where('leads.0.city', 'Olinda')
        );
    });
});
