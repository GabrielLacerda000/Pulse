<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    /**
     * Display the pipeline Kanban board.
     */
    public function index(): Response
    {
        return Inertia::render('pipeline/Index', [
            'leads' => Lead::query()
                ->latest()
                ->get(),
        ]);
    }
}
