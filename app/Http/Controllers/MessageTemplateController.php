<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageTemplateRequest;
use App\Http\Requests\UpdateMessageTemplateRequest;
use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MessageTemplateController extends Controller
{
    /**
     * Display a listing of the message templates.
     */
    public function index(): Response
    {
        return Inertia::render('message-templates/Index', [
            'messageTemplates' => MessageTemplate::query()
                ->latest()
                ->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new message template.
     */
    public function create(): Response
    {
        return Inertia::render('message-templates/Create');
    }

    /**
     * Store a newly created message template in storage.
     */
    public function store(StoreMessageTemplateRequest $request): RedirectResponse
    {
        MessageTemplate::create($request->validated());

        return to_route('message-templates.index');
    }

    /**
     * Show the form for editing the specified message template.
     */
    public function edit(MessageTemplate $messageTemplate): Response
    {
        return Inertia::render('message-templates/Edit', [
            'messageTemplate' => $messageTemplate,
        ]);
    }

    /**
     * Update the specified message template in storage.
     */
    public function update(UpdateMessageTemplateRequest $request, MessageTemplate $messageTemplate): RedirectResponse
    {
        $messageTemplate->update($request->validated());

        return to_route('message-templates.index');
    }

    /**
     * Remove the specified message template from storage.
     */
    public function destroy(MessageTemplate $messageTemplate): RedirectResponse
    {
        $messageTemplate->delete();

        return to_route('message-templates.index');
    }
}
