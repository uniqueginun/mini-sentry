<?php

use App\Models\Project;
use App\Models\ProjectKey;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();

    $response = $this->get(route('projects.index', $user->currentTeam));

    $response->assertRedirect(route('login'));
});

test('the projects page can be rendered', function () {
    $user = User::factory()->create();
    $project = Project::factory()->recycle($user)->recycle($user->currentTeam)->create(['name' => 'Acme API']);
    $key = ProjectKey::factory()->recycle($project)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('projects.index', $user->currentTeam));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/index')
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
            ->where('projects.0.name', 'Acme API')
            ->has('projects.0.keys', 1)
            ->where('projects.0.keys.0.id', $key->id)
            ->where('projects.0.keys.0.prefix', $key->prefix)
            ->where('projects.0.keys.0.is_active', true)
            ->missing('projects.0.keys.0.public_key'),
        );
});

test('the projects page does not include projects from another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Project::factory()->recycle($user)->recycle($user->currentTeam)->create(['name' => 'Mine']);
    Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create(['name' => 'Theirs']);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.index', $user->currentTeam));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/index')
            ->has('projects', 1)
            ->where('projects.0.name', 'Mine'),
        );
});

test('users who do not belong to the team cannot view its projects', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('projects.index', $otherUser->currentTeam));

    $response->assertForbidden();
});

test('projects can be created with an automatically generated key', function () {
    $user = User::factory()->create();
    $plaintext = 'abcdefghij123456abcdefghij123456';
    Str::createRandomStringsUsing(fn (int $length): string => $length === 32 ? $plaintext : str_repeat('x', $length));

    $response = $this
        ->actingAs($user)
        ->post(route('projects.store', $user->currentTeam), [
            'name' => 'Acme API',
        ]);

    $project = Project::query()->where('name', 'Acme API')->first();

    expect($project)->not->toBeNull();

    $response
        ->assertRedirect(route('projects.index', $user->currentTeam))
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('generatedKey.projectId', $project->id)
        ->assertInertiaFlash('generatedKey.publicKey', $plaintext);

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Acme API',
        'user_id' => $user->id,
        'team_id' => $user->currentTeam->id,
    ]);

    $this->assertDatabaseHas('project_keys', [
        'project_id' => $project->id,
        'public_key' => hash('sha256', $plaintext),
        'prefix' => 'abcdefgh',
        'is_active' => true,
    ]);

    $this->assertDatabaseMissing('project_keys', [
        'public_key' => $plaintext,
    ]);
});

test('a project name is required', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('projects.index', $user->currentTeam))
        ->post(route('projects.store', $user->currentTeam), [
            'name' => '',
        ]);

    $response->assertRedirect(route('projects.index', $user->currentTeam));
    $response->assertSessionHasErrors(['name' => 'The name field is required.']);
    $this->assertDatabaseCount('projects', 0);
});

test('a project name must be unique within the team', function () {
    $user = User::factory()->create();
    Project::factory()->recycle($user)->recycle($user->currentTeam)->create(['name' => 'Acme API']);

    $response = $this
        ->actingAs($user)
        ->from(route('projects.index', $user->currentTeam))
        ->post(route('projects.store', $user->currentTeam), [
            'name' => 'Acme API',
        ]);

    $response->assertRedirect(route('projects.index', $user->currentTeam));
    $response->assertSessionHasErrors(['name' => 'The name has already been taken.']);
    $this->assertDatabaseCount('projects', 1);
});

test('the same project name can be used on another team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Project::factory()->recycle($otherUser)->recycle($otherUser->currentTeam)->create(['name' => 'Acme API']);

    $response = $this
        ->actingAs($user)
        ->post(route('projects.store', $user->currentTeam), [
            'name' => 'Acme API',
        ]);

    $response->assertRedirect(route('projects.index', $user->currentTeam));
    $this->assertDatabaseCount('projects', 2);
});
