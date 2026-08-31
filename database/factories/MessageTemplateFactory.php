<?php

namespace Database\Factories;

use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<MessageTemplate>
 */
class MessageTemplateFactory extends Factory
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
            'content' => $this->faker->paragraph(),
            'channel' => 'whatsapp',
        ];
    }
}
