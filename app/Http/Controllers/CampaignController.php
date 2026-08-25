<?php

namespace App\Http\Controllers;

use App\Actions\CreateCampaignAction;
use App\Actions\StartCampaignAction;
use App\Actions\UpdateCampaignAction;
use App\Data\CampaignData;
use App\Enums\CampaignRecipientStatus;
use App\Exceptions\CampaignStartBlockedException;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    /**
     * Display a listing of the campaigns.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        return Inertia::render('campaigns/Index', [
            'campaigns' => Campaign::query()
                ->search($filters['search'] ?? null)
                ->filterStatus($filters['status'] ?? null)
                ->withCount([
                    'recipients',
                    'recipients as sent_count' => fn ($query) => $query->where('status', CampaignRecipientStatus::SENT),
                    'recipients as failed_count' => fn ($query) => $query->where('status', CampaignRecipientStatus::FAILED),
                ])
                ->latest()
                ->paginate(15),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(Request $request): Response
    {
        $leads = Lead::selectableForCampaigns();

        return Inertia::render('campaigns/Create', [
            'leads' => $leads,
            'filterOptions' => Lead::filterOptionsFor($leads),
            'messageTemplates' => MessageTemplate::all(),
            'preselectedLeadIds' => Lead::preselectedIdsFrom($leads, $request->query('lead_ids', [])),
        ]);
    }

    /**
     * Store a newly created campaign in storage.
     */
    public function store(StoreCampaignRequest $request, CreateCampaignAction $action): RedirectResponse
    {
        $campaign = $action->execute(CampaignData::from($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campanha ":name" criada.', ['name' => $campaign->name])]);

        return to_route('campaigns.index');
    }

    /**
     * Display a running, completed, or failed campaign's send progress.
     */
    public function show(Campaign $campaign): Response
    {
        return Inertia::render('campaigns/Show', [
            'campaign' => $campaign->toShowPayload(),
            'progress' => $campaign->progressCounts(),
            'failedRecipients' => $campaign->recipients()
                ->where('status', CampaignRecipientStatus::FAILED)
                ->get(['id', 'lead_id', 'company_name', 'phone', 'error_code']),
        ]);
    }

    /**
     * The recipient counters and status behind a campaign's progress bar,
     * polled by the show page while the campaign is running.
     */
    public function progress(Campaign $campaign): JsonResponse
    {
        return response()->json($campaign->progressCounts());
    }

    /**
     * Show the form for editing the specified campaign.
     */
    public function edit(Campaign $campaign): Response
    {
        abort_unless($campaign->isEditable(), 403);

        $leads = Lead::selectableForCampaigns();

        return Inertia::render('campaigns/Edit', [
            'campaign' => $campaign->toEditPayload(),
            'leads' => $leads,
            'filterOptions' => Lead::filterOptionsFor($leads),
            'messageTemplates' => MessageTemplate::all(),
        ]);
    }

    /**
     * Update the specified campaign in storage.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign, UpdateCampaignAction $action): RedirectResponse
    {
        $action->execute($campaign, CampaignData::from($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campanha ":name" atualizada.', ['name' => $campaign->name])]);

        return to_route('campaigns.index');
    }

    /**
     * Start the specified campaign's send.
     */
    public function start(Campaign $campaign, StartCampaignAction $action): RedirectResponse
    {
        abort_unless($campaign->isStartable(), 403);

        try {
            $action->execute($campaign);
        } catch (CampaignStartBlockedException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return to_route('campaigns.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campanha ":name" iniciada.', ['name' => $campaign->name])]);

        return to_route('campaigns.index');
    }

    /**
     * Remove the specified campaign from storage.
     */
    public function destroy(Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->isDeletable(), 403);

        $campaign->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campanha ":name" excluída.', ['name' => $campaign->name])]);

        return to_route('campaigns.index');
    }
}
