<?php

namespace App\Http\Controllers;

use App\Actions\CreateLeadAction;
use App\Data\LeadData;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadNotesRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    /**
     * Display a listing of the leads.
     */
    public function index(): Response
    {
        return Inertia::render('leads/Index', [
            'leads' => Lead::query()
                ->latest()
                ->get(),
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
     * Remove the specified lead from storage.
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return to_route('leads.index');
    }
}
