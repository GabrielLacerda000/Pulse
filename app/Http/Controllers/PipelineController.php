<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    /**
     * Display the pipeline Kanban board.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'category', 'city']);

        return Inertia::render('pipeline/Index', [
            'leads' => Lead::query()
                ->search($filters['search'] ?? null)
                ->filterStatus($filters['status'] ?? null)
                ->filterCategory($filters['category'] ?? null)
                ->filterCity($filters['city'] ?? null)
                ->latest()
                ->get(),
            'filters' => $filters,
            'filterOptions' => Lead::filterOptions(),
        ]);
    }
}
