<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn (): int => (int) (Auth::id() ?? User::factory()->create()->id),
            'name' => $this->faker->words(3, true),
            'status' => CampaignStatus::DRAFT,
            'delay_min_seconds' => 30,
            'delay_max_seconds' => 90,
        ];
    }
}
