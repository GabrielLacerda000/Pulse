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
});
