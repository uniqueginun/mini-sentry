<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the home page renders the welcome landing page', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));
});
