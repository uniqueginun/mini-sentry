<?php

use App\Models\Project;
use App\Models\Release;
use Illuminate\Database\UniqueConstraintViolationException;

test('a release belongs to a project', function () {
    $project = Project::factory()->create();

    $release = Release::factory()->recycle($project)->create([
        'version' => '1.2.3',
    ]);

    expect($release->project->is($project))->toBeTrue();
    $this->assertModelExists($release);
});

test('releases cannot share a version within the same project', function () {
    $project = Project::factory()->create();
    Release::factory()->recycle($project)->create(['version' => '1.0.0']);

    Release::factory()->recycle($project)->create(['version' => '1.0.0']);
})->throws(UniqueConstraintViolationException::class);

test('releases may share a version across projects', function () {
    Release::factory()->create(['version' => '1.0.0']);
    Release::factory()->create(['version' => '1.0.0']);

    expect(Release::query()->where('version', '1.0.0')->count())->toBe(2);
});
