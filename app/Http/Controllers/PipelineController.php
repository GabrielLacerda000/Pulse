<?php

namespace App\Http\Controllers;

use App\Actions\RenderMessageTemplateAction;
use App\Models\Lead;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    /**
     * Display the pipeline Kanban board.
     */
    public function index(Request $request, RenderMessageTemplateAction $action): Response
    {
        $filters = $request->only(['search', 'status', 'category', 'city']);
        $templates = MessageTemplate::all();

        return Inertia::render('pipeline/Index', [
            'leads' => Lead::query()
                ->search($filters['search'] ?? null)
                ->filterStatus($filters['status'] ?? null)
                ->filterCategory($filters['category'] ?? null)
                ->filterCity($filters['city'] ?? null)
                ->latest()
                ->get()
                ->each(fn (Lead $lead) => $lead->setAttribute('message_templates', $action->executeAllFor($lead, $templates))),
            'filters' => $filters,
            'filterOptions' => Lead::filterOptions(),
        ]);
    }
}
