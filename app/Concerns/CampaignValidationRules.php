<?php

namespace App\Concerns;

use App\Models\Lead;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

trait CampaignValidationRules
{
    /**
     * The floor below which delay_min_seconds/delay_max_seconds cannot go,
     * to keep sends from firing off in an unrealistic, bot-like burst.
     */
    private const int MIN_DELAY_SECONDS = 30;

    /**
     * Get the validation rules used to validate a campaign's fields.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function campaignRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'delay_min_seconds' => ['required', 'integer', 'min:'.self::MIN_DELAY_SECONDS],
            'delay_max_seconds' => ['required', 'integer', 'min:'.self::MIN_DELAY_SECONDS, 'gte:delay_min_seconds'],
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => [
                'distinct',
                'integer',
                Rule::exists('leads', 'id')->where('user_id', Auth::id()),
                $this->leadHasValidPhone(),
            ],
            'messages' => ['required', 'array', 'min:1'],
            'messages.*' => ['required', 'string'],
        ];
    }

    /**
     * Reject a selected Lead whose phone number doesn't survive
     * NormalizePhoneAction, since it can't be reached over WhatsApp.
     */
    private function leadHasValidPhone(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $lead = Lead::find($value);

            if ($lead instanceof Lead && $lead->whatsapp_phone === null) {
                $fail('O lead selecionado não possui um telefone válido para WhatsApp.');
            }
        };
    }
}
