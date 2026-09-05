<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the home page renders the welcome landing page', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));
});

test('the home page remains available after registration so users can read the install guide', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));
});
