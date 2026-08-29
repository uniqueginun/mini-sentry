<?php

use App\Models\Project;
use App\Models\ProjectKey;
use App\Models\User;
use Illuminate\Support\Str;

test('a new key can be generated for a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    ProjectKey::factory()->recycle($project)->create();
    $plaintext = 'klmnopqrstuv123456abcdefghijklmn';
    Str::createRandomStringsUsing(fn (int $length): string => $length === 32 ? $plaintext : str_repeat('x', $length));

    $response = $this
        ->actingAs($user)
        ->post(route('projects.keys.store', [
            'current_team' => $user->currentTeam,
            'project' => $project,
        ]));

    $response
        ->assertRedirect(route('projects.index', $user->currentTeam))
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('generatedKey.projectId', $project->id)
        ->assertInertiaFlash('generatedKey.publicKey', $plaintext);

    expect($project->projectKeys()->count())->toBe(2);
    expect($project->projectKeys()->active()->count())->toBe(2);

    $this->assertDatabaseHas('project_keys', [
        'project_id' => $project->id,
        'public_key' => hash('sha256', $plaintext),
        'prefix' => 'klmnopqr',
    ]);

    $this->assertDatabaseMissing('project_keys', [
        'public_key' => $plaintext,
    ]);
});

test('an active key can be revoked', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $key = ProjectKey::factory()->recycle($project)->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('projects.keys.update', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'projectKey' => $key,
        ]));

    $response
        ->assertRedirect(route('projects.index', $user->currentTeam))
        ->assertInertiaFlash('toast.type', 'success');

    expect($key->fresh()->is_active)->toBeFalse();
});

test('an already revoked key cannot be revoked again', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $key = ProjectKey::factory()->inactive()->recycle($project)->create();

    $response = $this
        ->actingAs($user)
        ->from(route('projects.index', $user->currentTeam))
        ->patch(route('projects.keys.update', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'projectKey' => $key,
        ]));

    $response->assertRedirect(route('projects.index', $user->currentTeam));
    $response->assertSessionHasErrors(['projectKey' => 'This key has already been revoked.']);
    expect($key->fresh()->is_active)->toBeFalse();
});

test('keys cannot be generated for a project that belongs to another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();

    $response = $this
        ->actingAs($user)
        ->post(route('projects.keys.store', [
            'current_team' => $user->currentTeam,
            'project' => $project,
        ]));

    $response->assertNotFound();
    $this->assertDatabaseCount('project_keys', 0);
});

test('keys cannot be revoked for a project that belongs to another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create();
    $key = ProjectKey::factory()->recycle($project)->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('projects.keys.update', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'projectKey' => $key,
        ]));

    $response->assertNotFound();
    expect($key->fresh()->is_active)->toBeTrue();
});

test('a key cannot be revoked when it does not belong to the project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $otherProject = Project::factory()->recycle($user)->recycle($user->currentTeam)->create();
    $key = ProjectKey::factory()->recycle($otherProject)->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('projects.keys.update', [
            'current_team' => $user->currentTeam,
            'project' => $project,
            'projectKey' => $key,
        ]));

    $response->assertNotFound();
    expect($key->fresh()->is_active)->toBeTrue();
});
