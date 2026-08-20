<?php

namespace App\Concerns;

trait LeadValidationRules
{
    /**
     * Get the validation rules used to validate a lead's manual fields.
     *
     * @return array<string, array<int, string>>
     */
    protected function leadRules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
