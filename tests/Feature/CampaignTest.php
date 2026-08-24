<?php

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('campaigns.index'));

    $response->assertRedirect(route('login'));
});

describe('as an authenticated user', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
    });

    test('a campaign created without an explicit user is owned by the acting user', function () {
        $campaign = Campaign::factory()->create();

        expect($campaign->user_id)->toBe(auth()->id());
    });

    test('a user cannot see another user\'s campaigns through normal queries', function () {
        Campaign::factory()->count(2)->create(['user_id' => User::factory()->create()->id]);
        $own = Campaign::factory()->create();

        $campaigns = Campaign::all();

        expect($campaigns)->toHaveCount(1);
        expect($campaigns->first()->id)->toBe($own->id);
    });

    test('campaigns index lists only the user\'s own campaigns', function () {
        Campaign::factory()->count(2)->create(['user_id' => User::factory()->create()->id]);
        Campaign::factory()->create();

        $response = $this->get(route('campaigns.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Index')
            ->has('campaigns.data', 1)
        );
    });

    test('the create page only lists leads whose phone is valid for WhatsApp', function () {
        $withPhone = Lead::factory()->create(['phone' => '5581999999999']);
        Lead::factory()->create(['phone' => null]);
        Lead::factory()->create(['phone' => '123']);

        $response = $this->get(route('campaigns.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Create')
            ->has('leads', 1)
            ->where('leads.0.id', $withPhone->id)
        );
    });

    test('a user can create a campaign with a lead snapshot and message pool', function () {
        $leadOne = Lead::factory()->create(['phone' => '5581999999999', 'company_name' => 'Empresa Um']);
        $leadTwo = Lead::factory()->create(['phone' => '5581988888888', 'company_name' => 'Empresa Dois']);

        $data = [
            'name' => 'Campanha de lançamento',
            'delay_min_seconds' => 30,
            'delay_max_seconds' => 90,
            'lead_ids' => [$leadOne->id, $leadTwo->id],
            'messages' => ['Olá {nome}, tudo bem?', 'Oi {nome}, vi sua empresa em {cidade}.'],
        ];

        $response = $this->post(route('campaigns.store'), $data);

        $response->assertRedirect(route('campaigns.index'));

        $campaign = Campaign::firstWhere('name', 'Campanha de lançamento');
        expect($campaign)->not->toBeNull();
        expect($campaign->status)->toBe(CampaignStatus::DRAFT);
        expect($campaign->delay_min_seconds)->toBe(30);
        expect($campaign->delay_max_seconds)->toBe(90);
        expect($campaign->user_id)->toBe(auth()->id());

        expect($campaign->messages)->toHaveCount(2);
        expect($campaign->messages->pluck('content')->all())->toBe($data['messages']);

        expect($campaign->recipients)->toHaveCount(2);
        $campaign->recipients->each(function ($recipient) {
            expect($recipient->status)->toBe(CampaignRecipientStatus::PENDING);
        });
        expect($campaign->recipients->pluck('lead_id')->sort()->values()->all())
            ->toBe([$leadOne->id, $leadTwo->id]);
        expect($campaign->recipients->firstWhere('lead_id', $leadOne->id)->company_name)->toBe('Empresa Um');
    });

    test('the recipient snapshot is independent of later Lead changes', function () {
        $lead = Lead::factory()->create(['phone' => '5581999999999', 'company_name' => 'Nome Original']);

        $this->post(route('campaigns.store'), [
            'name' => 'Campanha',
            'delay_min_seconds' => 30,
            'delay_max_seconds' => 60,
            'lead_ids' => [$lead->id],
            'messages' => ['Olá!'],
        ]);

        $recipient = Campaign::firstWhere('name', 'Campanha')->recipients->first();

        $lead->update(['company_name' => 'Nome Alterado']);

        expect($recipient->fresh()->company_name)->toBe('Nome Original');
    });

    test('creating a campaign requires name, delays, leads and messages', function () {
        $response = $this->post(route('campaigns.store'), []);

        $response->assertInvalid(['name', 'delay_min_seconds', 'delay_max_seconds', 'lead_ids', 'messages']);
    });

    test('delay_min_seconds and delay_max_seconds must respect the minimum floor', function () {
        $lead = Lead::factory()->create(['phone' => '5581999999999']);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Campanha',
            'delay_min_seconds' => 1,
            'delay_max_seconds' => 5,
            'lead_ids' => [$lead->id],
            'messages' => ['Olá!'],
        ]);

        $response->assertInvalid(['delay_min_seconds', 'delay_max_seconds']);
    });

    test('delay_max_seconds must be greater than or equal to delay_min_seconds', function () {
        $lead = Lead::factory()->create(['phone' => '5581999999999']);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Campanha',
            'delay_min_seconds' => 90,
            'delay_max_seconds' => 30,
            'lead_ids' => [$lead->id],
            'messages' => ['Olá!'],
        ]);

        $response->assertInvalid(['delay_max_seconds']);
    });

    test('a lead without a valid WhatsApp phone cannot be selected', function () {
        $lead = Lead::factory()->create(['phone' => null]);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Campanha',
            'delay_min_seconds' => 30,
            'delay_max_seconds' => 60,
            'lead_ids' => [$lead->id],
            'messages' => ['Olá!'],
        ]);

        $response->assertInvalid(['lead_ids.0']);
    });

    test('a lead belonging to another user cannot be selected', function () {
        $otherLead = Lead::factory()->create([
            'user_id' => User::factory()->create()->id,
            'phone' => '5581999999999',
        ]);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Campanha',
            'delay_min_seconds' => 30,
            'delay_max_seconds' => 60,
            'lead_ids' => [$otherLead->id],
            'messages' => ['Olá!'],
        ]);

        $response->assertInvalid(['lead_ids.0']);
    });

    test('duplicate lead_ids are rejected instead of producing duplicate recipients', function () {
        $lead = Lead::factory()->create(['phone' => '5581999999999']);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Campanha',
            'delay_min_seconds' => 30,
            'delay_max_seconds' => 60,
            'lead_ids' => [$lead->id, $lead->id],
            'messages' => ['Olá!'],
        ]);

        $response->assertInvalid(['lead_ids.0', 'lead_ids.1']);
        expect(Campaign::firstWhere('name', 'Campanha'))->toBeNull();
    });
});
