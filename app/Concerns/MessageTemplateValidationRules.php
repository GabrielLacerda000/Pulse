<?php

namespace App\Concerns;

trait MessageTemplateValidationRules
{
    /**
     * Get the validation rules used to validate a message template's fields.
     *
     * @return array<string, array<int, string>>
     */
    protected function messageTemplateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }
}
