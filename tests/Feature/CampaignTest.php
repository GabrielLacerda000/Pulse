<?php

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Models\CampaignRecipient;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

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

    test('campaigns index filters by name search', function () {
        Campaign::factory()->create(['name' => 'Campanha de lançamento']);
        Campaign::factory()->create(['name' => 'Outra campanha']);

        $response = $this->get(route('campaigns.index', ['search' => 'lançamento']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Index')
            ->has('campaigns.data', 1)
            ->where('campaigns.data.0.name', 'Campanha de lançamento')
        );
    });

    test('campaigns index filters by status', function () {
        Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);
        Campaign::factory()->create(['status' => CampaignStatus::COMPLETED]);

        $response = $this->get(route('campaigns.index', ['status' => 'completed']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Index')
            ->has('campaigns.data', 1)
            ->where('campaigns.data.0.status', 'completed')
        );
    });

    test('campaigns index combines search and status filters', function () {
        Campaign::factory()->create(['name' => 'Campanha X', 'status' => CampaignStatus::DRAFT]);
        Campaign::factory()->create(['name' => 'Campanha X', 'status' => CampaignStatus::COMPLETED]);
        Campaign::factory()->create(['name' => 'Outra', 'status' => CampaignStatus::DRAFT]);

        $response = $this->get(route('campaigns.index', ['search' => 'Campanha X', 'status' => 'draft']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Index')
            ->has('campaigns.data', 1)
        );
    });

    test('campaigns index with no filters returns all the user\'s campaigns, most recent first', function () {
        Campaign::factory()->create(['name' => 'Primeira', 'created_at' => now()->subMinute()]);
        Campaign::factory()->create(['name' => 'Segunda', 'created_at' => now()]);

        $response = $this->get(route('campaigns.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Index')
            ->has('campaigns.data', 2)
            ->where('campaigns.data.0.name', 'Segunda')
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

    test('campaigns index reports final send/fail counts', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::COMPLETED]);
        CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::SENT]);
        CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::SENT]);
        CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::FAILED]);

        $response = $this->get(route('campaigns.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Index')
            ->where('campaigns.data.0.recipients_count', 3)
            ->where('campaigns.data.0.sent_count', 2)
            ->where('campaigns.data.0.failed_count', 1)
        );
    });

    test('a draft campaign can be viewed for editing', function () {
        $lead = Lead::factory()->create(['phone' => '5581999999999']);
        $campaign = Campaign::factory()->create(['name' => 'Original']);
        CampaignMessage::factory()->create(['campaign_id' => $campaign->id, 'content' => 'Olá!']);
        CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'lead_id' => $lead->id]);

        $response = $this->get(route('campaigns.edit', $campaign));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Edit')
            ->where('campaign.id', $campaign->id)
            ->where('campaign.name', 'Original')
            ->where('campaign.messages.0', 'Olá!')
            ->where('campaign.lead_ids.0', $lead->id)
        );
    });

    test('a draft campaign can be updated', function () {
        $oldLead = Lead::factory()->create(['phone' => '5581999999999']);
        $newLead = Lead::factory()->create(['phone' => '5581988888888']);
        $campaign = Campaign::factory()->create(['name' => 'Original']);
        CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
        CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'lead_id' => $oldLead->id]);

        $response = $this->put(route('campaigns.update', $campaign), [
            'name' => 'Atualizada',
            'delay_min_seconds' => 45,
            'delay_max_seconds' => 100,
            'lead_ids' => [$newLead->id],
            'messages' => ['Nova mensagem'],
        ]);

        $response->assertRedirect(route('campaigns.index'));

        $campaign->refresh();
        expect($campaign->name)->toBe('Atualizada');
        expect($campaign->delay_min_seconds)->toBe(45);
        expect($campaign->delay_max_seconds)->toBe(100);
        expect($campaign->messages)->toHaveCount(1);
        expect($campaign->messages->first()->content)->toBe('Nova mensagem');
        expect($campaign->recipients)->toHaveCount(1);
        expect($campaign->recipients->first()->lead_id)->toBe($newLead->id);
    });

    test('a draft campaign can be deleted', function () {
        $campaign = Campaign::factory()->create();

        $response = $this->delete(route('campaigns.destroy', $campaign));

        $response->assertRedirect(route('campaigns.index'));
        expect(Campaign::find($campaign->id))->toBeNull();
    });

    test('a completed campaign can be deleted', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::COMPLETED]);

        $response = $this->delete(route('campaigns.destroy', $campaign));

        $response->assertRedirect(route('campaigns.index'));
        expect(Campaign::find($campaign->id))->toBeNull();
    });

    test('a failed campaign can be deleted', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::FAILED]);

        $response = $this->delete(route('campaigns.destroy', $campaign));

        $response->assertRedirect(route('campaigns.index'));
        expect(Campaign::find($campaign->id))->toBeNull();
    });

    test('a running campaign cannot be edited', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);

        $response = $this->get(route('campaigns.edit', $campaign));

        $response->assertForbidden();
    });

    test('a running campaign cannot be updated', function () {
        $lead = Lead::factory()->create(['phone' => '5581999999999']);
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING, 'name' => 'Original']);

        $response = $this->put(route('campaigns.update', $campaign), [
            'name' => 'Tentativa',
            'delay_min_seconds' => 30,
            'delay_max_seconds' => 60,
            'lead_ids' => [$lead->id],
            'messages' => ['Olá!'],
        ]);

        $response->assertForbidden();
        expect($campaign->fresh()->name)->toBe('Original');
    });

    test('a running campaign cannot be deleted', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);

        $response = $this->delete(route('campaigns.destroy', $campaign));

        $response->assertForbidden();
        expect(Campaign::find($campaign->id))->not->toBeNull();
    });

    test('starting a draft campaign dispatches the sender job and marks it running', function () {
        Queue::fake();
        Http::fake([
            'pulse-wpp.test/whatsapp/status' => Http::response(['status' => 'connected', 'phone' => '5582999999999']),
        ]);
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT, 'name' => 'Lançamento']);

        $response = $this->post(route('campaigns.start', $campaign));

        $response->assertRedirect(route('campaigns.index'));
        $response->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Campanha "Lançamento" iniciada.']);
        expect($campaign->fresh()->status)->toBe(CampaignStatus::RUNNING);
        Queue::assertPushed(SendCampaignJob::class, fn ($job) => $job->campaign->is($campaign));
    });

    test('starting a campaign is blocked while another owned campaign is already running', function () {
        Queue::fake();
        Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
        $draft = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

        $response = $this->post(route('campaigns.start', $draft));

        $response->assertRedirect(route('campaigns.index'));
        $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Você já tem uma campanha em andamento. Aguarde ela terminar antes de iniciar outra.']);
        expect($draft->fresh()->status)->toBe(CampaignStatus::DRAFT);
        Queue::assertNotPushed(SendCampaignJob::class);
    });

    test('starting a campaign is blocked when whatsapp is not connected', function () {
        Queue::fake();
        Http::fake([
            'pulse-wpp.test/whatsapp/status' => Http::response(['status' => 'disconnected']),
        ]);
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

        $response = $this->post(route('campaigns.start', $campaign));

        $response->assertRedirect(route('campaigns.index'));
        $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Conecte o WhatsApp antes de iniciar a campanha.']);
        expect($campaign->fresh()->status)->toBe(CampaignStatus::DRAFT);
        Queue::assertNotPushed(SendCampaignJob::class);
    });

    test('starting a campaign is blocked when pulse-wpp is unreachable', function () {
        Queue::fake();
        Http::fake([
            'pulse-wpp.test/whatsapp/status' => Http::response([
                'success' => false,
                'error' => ['code' => 'PROVIDER_UNAVAILABLE', 'message' => 'error: PROVIDER_UNAVAILABLE'],
            ], 503),
        ]);
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

        $response = $this->post(route('campaigns.start', $campaign));

        $response->assertRedirect(route('campaigns.index'));
        $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Conecte o WhatsApp antes de iniciar a campanha.']);
        expect($campaign->fresh()->status)->toBe(CampaignStatus::DRAFT);
        Queue::assertNotPushed(SendCampaignJob::class);
    });

    test('the progress endpoint returns recipient counters and status for a running campaign', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
        CampaignRecipient::factory()->count(2)->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::SENT]);
        CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::FAILED]);
        CampaignRecipient::factory()->count(3)->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::PENDING]);

        $response = $this->getJson(route('campaigns.progress', $campaign));

        $response->assertOk();
        $response->assertJson([
            'sent' => 2,
            'failed' => 1,
            'pending' => 3,
            'total' => 6,
            'status' => 'running',
        ]);
    });

    test('the progress endpoint reflects a campaign with no recipients', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

        $response = $this->getJson(route('campaigns.progress', $campaign));

        $response->assertOk();
        $response->assertJson([
            'sent' => 0,
            'failed' => 0,
            'pending' => 0,
            'total' => 0,
            'status' => 'draft',
        ]);
    });

    test('a running campaign show page renders without the final summary', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING, 'name' => 'Lançamento']);
        CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::SENT]);
        CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::PENDING]);

        $response = $this->get(route('campaigns.show', $campaign));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Show')
            ->where('campaign.status', 'running')
            ->where('progress.sent', 1)
            ->where('progress.pending', 1)
            ->where('progress.total', 2)
            ->where('failedRecipients', [])
        );
    });

    test('a completed campaign show page renders the final summary with failed recipient error codes', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::COMPLETED, 'name' => 'Lançamento']);
        CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::SENT]);
        CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'status' => CampaignRecipientStatus::FAILED,
            'error_code' => 'MESSAGE_SEND_FAILED',
        ]);

        $response = $this->get(route('campaigns.show', $campaign));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Show')
            ->where('campaign.status', 'completed')
            ->where('progress.sent', 1)
            ->where('progress.failed', 1)
            ->has('failedRecipients', 1)
            ->where('failedRecipients.0.error_code', 'MESSAGE_SEND_FAILED')
        );
    });

    test('a failed campaign show page renders the final summary', function () {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::FAILED]);

        $response = $this->get(route('campaigns.show', $campaign));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('campaigns/Show')
            ->where('campaign.status', 'failed')
        );
    });

    test('a running campaign cannot be started again', function () {
        Queue::fake();
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);

        $response = $this->post(route('campaigns.start', $campaign));

        $response->assertForbidden();
        Queue::assertNotPushed(SendCampaignJob::class);
    });

    test('a completed campaign cannot be started again', function () {
        Queue::fake();
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::COMPLETED]);

        $response = $this->post(route('campaigns.start', $campaign));

        $response->assertForbidden();
        Queue::assertNotPushed(SendCampaignJob::class);
    });
});
