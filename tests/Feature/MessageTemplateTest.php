<?php

use App\Models\MessageTemplate;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('a message template created without an explicit user is owned by the acting user', function () {
    $template = MessageTemplate::factory()->create();

    expect($template->user_id)->toBe(auth()->id());
});

test('a user cannot see another user\'s message templates through normal queries', function () {
    MessageTemplate::factory()->count(2)->create(['user_id' => User::factory()->create()->id]);
    $own = MessageTemplate::factory()->create();

    $templates = MessageTemplate::all();

    expect($templates)->toHaveCount(1);
    expect($templates->first()->id)->toBe($own->id);
});
