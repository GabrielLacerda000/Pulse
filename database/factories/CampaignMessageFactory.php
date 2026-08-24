<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignMessage>
 */
class CampaignMessageFactory extends Factory
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
            'content' => $this->faker->paragraph(),
        ];
    }
}
