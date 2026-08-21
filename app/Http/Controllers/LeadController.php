<?php

namespace App\Http\Controllers;

use App\Actions\CreateLeadAction;
use App\Actions\UpdateLeadStatusAction;
use App\Data\LeadData;
use App\Enums\LeadStatus;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadNotesRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    /**
     * Display a listing of the leads.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'category', 'city', 'sort', 'direction']);

        return Inertia::render('leads/Index', [
            'leads' => Lead::query()
                ->search($filters['search'] ?? null)
                ->filterStatus($filters['status'] ?? null)
                ->filterCategory($filters['category'] ?? null)
                ->filterCity($filters['city'] ?? null)
                ->sortBy($filters['sort'] ?? null, $filters['direction'] ?? null)
                ->paginate(15),
            'filters' => $filters,
            'filterOptions' => [
                'statuses' => LeadStatus::cases(),
                'categories' => Lead::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
                'cities' => Lead::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new lead.
     */
    public function create(): Response
    {
        return Inertia::render('leads/Create');
    }

    /**
     * Store a newly created lead in storage.
     */
    public function store(StoreLeadRequest $request, CreateLeadAction $action): RedirectResponse
    {
        $lead = $action->execute(LeadData::from($request->validated()));

        return to_route('leads.show', $lead);
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead): Response
    {
        return Inertia::render('leads/Show', [
            'lead' => $lead,
        ]);
    }

    /**
     * Show the form for editing the specified lead.
     */
    public function edit(Lead $lead): Response
    {
        return Inertia::render('leads/Edit', [
            'lead' => $lead,
        ]);
    }

    /**
     * Update the specified lead in storage.
     */
    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return to_route('leads.show', $lead);
    }

    /**
     * Update the notes of the specified lead.
     */
    public function updateNotes(UpdateLeadNotesRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notes updated.')]);

        return to_route('leads.show', $lead);
    }

    /**
     * Update the status of the specified lead (Kanban drag & drop).
     */
    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead, UpdateLeadStatusAction $action): RedirectResponse
    {
        $action->execute($lead, LeadStatus::from($request->validated('status')));

        return back();
    }

    /**
     * Remove the specified lead from storage.
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return to_route('leads.index');
    }
}
