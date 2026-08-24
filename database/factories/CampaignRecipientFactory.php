<?php

namespace Database\Factories;

use App\Enums\CampaignRecipientStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignRecipient>
 */
class CampaignRecipientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'lead_id' => Lead::factory(),
            'company_name' => $this->faker->company(),
            'phone' => $this->faker->numerify('55##9########'),
            'status' => CampaignRecipientStatus::PENDING,
        ];
    }
}
