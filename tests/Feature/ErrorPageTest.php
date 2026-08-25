<?php

test('a 404 renders the custom error page outside local/testing', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $response = $this->get('/route-that-does-not-exist');

    $response->assertNotFound();
    $response->assertInertia(fn ($page) => $page
        ->component('Error')
        ->where('status', 404));
});
