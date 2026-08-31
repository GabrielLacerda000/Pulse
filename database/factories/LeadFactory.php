<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
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
            'company_name' => $this->faker->company(),
            'category' => $this->faker->randomElement(['Clínica odontológica', 'Restaurante', 'Salão de beleza', 'Loja de roupas', 'Advocacia']),
            'phone' => $this->faker->numerify('55##9########'),
            'email' => $this->faker->safeEmail(),
            'website' => $this->faker->domainName(),
            'instagram' => '@'.$this->faker->userName(),
            'has_website' => $this->faker->boolean(),
            'has_instagram' => $this->faker->boolean(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'status' => LeadStatus::NEW,
            'type' => $this->faker->randomElement(['Sem presença', 'Com site', 'Com Instagram']),
            'score' => $this->faker->numberBetween(0, 100),
            'rating' => $this->faker->randomFloat(2, 1, 5),
            'reviews_count' => $this->faker->numberBetween(0, 500),
            'source' => 'google_maps',
            'google_maps_url' => $this->faker->url(),
            'source_created_at' => $this->faker->dateTimeBetween('-1 year'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
