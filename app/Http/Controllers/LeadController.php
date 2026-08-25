<?php

namespace App\Http\Controllers;

use App\Actions\CreateLeadAction;
use App\Actions\RenderMessageTemplateAction;
use App\Actions\UpdateLeadStatusAction;
use App\Data\LeadData;
use App\Enums\LeadStatus;
use App\Http\Requests\BulkDestroyLeadRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadNotesRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Models\Lead;
use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    /**
     * Display a listing of the leads.
     */
    public function index(Request $request, RenderMessageTemplateAction $action): Response
    {
        $filters = $request->only(['search', 'status', 'category', 'city', 'phone', 'sort', 'direction']);
        $templates = MessageTemplate::all();

        return Inertia::render('leads/Index', [
            'leads' => Lead::query()
                ->search($filters['search'] ?? null)
                ->filterStatus($filters['status'] ?? null)
                ->filterCategory($filters['category'] ?? null)
                ->filterCity($filters['city'] ?? null)
                ->filterPhone($filters['phone'] ?? null)
                ->sortBy($filters['sort'] ?? null, $filters['direction'] ?? null)
                ->paginate(15)
                ->through(fn (Lead $lead): Lead => $this->withRenderedMessageTemplates($lead, $templates, $action)),
            'filters' => $filters,
            'filterOptions' => Lead::filterOptions(),
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
    public function show(Lead $lead, RenderMessageTemplateAction $action): Response
    {
        return Inertia::render('leads/Show', [
            'lead' => $lead,
            'messageTemplates' => $action->executeAllFor($lead, MessageTemplate::all()),
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

    /**
     * Remove the specified leads from storage.
     */
    public function bulkDestroy(BulkDestroyLeadRequest $request): RedirectResponse
    {
        $count = Lead::query()->whereIn('id', $request->validated('ids'))->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':count leads excluídos.', ['count' => $count])]);

        return back();
    }

    /**
     * Attach the given templates rendered for the lead as a `message_templates` attribute.
     *
     * @param  Collection<int, MessageTemplate>  $templates
     */
    private function withRenderedMessageTemplates(Lead $lead, Collection $templates, RenderMessageTemplateAction $action): Lead
    {
        $lead->setAttribute('message_templates', $action->executeAllFor($lead, $templates));

        return $lead;
    }
}
