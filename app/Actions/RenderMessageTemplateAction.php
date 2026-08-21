<?php

namespace App\Actions;

use App\Models\Lead;
use App\Models\MessageTemplate;

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
        return preg_replace_callback(
            '/\{(\w+)\}/',
            fn (array $matches): string => $this->resolve($matches[1], $lead),
            $template->content,
        ) ?? $template->content;
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
