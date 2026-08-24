<?php

namespace App\Http\Controllers;

use App\Actions\CreateCampaignAction;
use App\Data\CampaignData;
use App\Http\Requests\StoreCampaignRequest;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    /**
     * Display a listing of the campaigns.
     */
    public function index(): Response
    {
        return Inertia::render('campaigns/Index', [
            'campaigns' => Campaign::query()
                ->withCount('recipients')
                ->latest()
                ->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(): Response
    {
        $leads = Lead::selectableForCampaigns();

        return Inertia::render('campaigns/Create', [
            'leads' => $leads,
            'filterOptions' => Lead::filterOptionsFor($leads),
            'messageTemplates' => MessageTemplate::all(),
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
}
