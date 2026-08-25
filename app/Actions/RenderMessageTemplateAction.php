<?php

namespace App\Actions;

use App\Models\Lead;
use App\Models\MessageTemplate;
use Illuminate\Support\Collection;

final class RenderMessageTemplateAction
{
    /**
     * Maps a template variable name to the Lead attribute it resolves to.
     *
     * @var array<string, string>
     */
    private const array VARIABLE_MAP = [
        'nome' => 'company_name',
        'cidade' => 'city',
    ];

    /**
     * Substitute a MessageTemplate's variables with the given Lead's data.
     *
     * A variable with no corresponding value on the Lead — unknown, or
     * known but null/blank — resolves to an empty string, never a literal
     * placeholder, and never blocks the flow.
     */
    public function execute(MessageTemplate $template, Lead $lead): string
    {
        return $this->renderContent($template->content, $lead);
    }

    /**
     * Substitute variables in a raw content string with the given Lead's
     * data. Shared by execute() and by callers rendering a CampaignMessage,
     * which is not a MessageTemplate.
     */
    public function renderContent(string $content, Lead $lead): string
    {
        return preg_replace_callback(
            '/\{(\w+)\}/',
            fn (array $matches): string => $this->resolve($matches[1], $lead),
            $content,
        ) ?? $content;
    }

    /**
     * Render each of the given templates for the given Lead, as the
     * {id, name, renderedContent} shape the frontend expects.
     *
     * @param  Collection<int, MessageTemplate>  $templates
     * @return Collection<int, array{id: int, name: string, renderedContent: string}>
     */
    public function executeAllFor(Lead $lead, Collection $templates): Collection
    {
        return $templates->map(fn (MessageTemplate $template): array => [
            'id' => $template->id,
            'name' => $template->name,
            'renderedContent' => $this->execute($template, $lead),
        ]);
    }

    private function resolve(string $variable, Lead $lead): string
    {
        $attribute = self::VARIABLE_MAP[$variable] ?? null;

        if ($attribute === null) {
            return '';
        }

        return (string) ($lead->{$attribute} ?? '');
    }
}
