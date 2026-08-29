<?php

use App\Models\Event;
use App\Models\EventTag;

test('an event tag belongs to an event and can be filtered by key and value', function () {
    $event = Event::factory()->create();
    EventTag::factory()->recycle($event)->create([
        'key' => 'environment',
        'value' => 'staging',
    ]);

    $tag = EventTag::factory()->recycle($event)->create([
        'key' => 'environment',
        'value' => 'production',
    ]);

    expect($tag->event->is($event))->toBeTrue();

    $match = EventTag::query()
        ->where('key', 'environment')
        ->where('value', 'production')
        ->first();

    expect($match?->is($tag))->toBeTrue();
});
