<?php

use App\Models\Brainstorm;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders brainstorm index page', function () {
    $project = Project::factory()->create();

    $response = $this->get(route('projects.brainstorm.index', $project));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('projects/brainstorm')
        ->has('project')
        ->has('brainstorms')
    );
});

it('displays brainstorms for specific project only', function () {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    $brainstorm1 = Brainstorm::factory()->create(['project_id' => $project1->id]);
    $brainstorm2 = Brainstorm::factory()->create(['project_id' => $project2->id]);

    $response = $this->get(route('projects.brainstorm.index', $project1));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('projects/brainstorm')
        ->has('brainstorms', 1)
        ->where('brainstorms.0.id', $brainstorm1->id)
    );
});

it('creates brainstorm with user context', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post(route('projects.brainstorm.store', $project), [
        'user_context' => 'Test brainstorm context',
    ]);

    $response->assertRedirect(route('projects.brainstorm.index', $project));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('brainstorms', [
        'project_id' => $project->id,
        'user_id' => $user->id,
        'user_context' => 'Test brainstorm context',
        'status' => 'pending',
    ]);
});

it('creates brainstorm without user context', function () {
    $project = Project::factory()->create();

    $response = $this->post(route('projects.brainstorm.store', $project), [
        'user_context' => null,
    ]);

    $response->assertRedirect(route('projects.brainstorm.index', $project));

    $this->assertDatabaseHas('brainstorms', [
        'project_id' => $project->id,
        'user_context' => null,
        'status' => 'pending',
    ]);
});

it('validates user context max length', function () {
    $project = Project::factory()->create();

    $response = $this->post(route('projects.brainstorm.store', $project), [
        'user_context' => str_repeat('a', 5001),
    ]);

    $response->assertSessionHasErrors('user_context');
});

it('brainstorm record has correct default status', function () {
    $project = Project::factory()->create();

    $this->post(route('projects.brainstorm.store', $project), [
        'user_context' => 'Test context',
    ]);

    $brainstorm = Brainstorm::latest()->first();

    expect($brainstorm->status)->toBe('pending');
    expect($brainstorm->ideas)->toBeNull();
    expect($brainstorm->completed_at)->toBeNull();
});

it('displays brainstorms ordered by created_at desc', function () {
    $project = Project::factory()->create();

    $brainstorm1 = Brainstorm::factory()->create([
        'project_id' => $project->id,
        'created_at' => now()->subHours(2),
    ]);

    $brainstorm2 = Brainstorm::factory()->create([
        'project_id' => $project->id,
        'created_at' => now()->subHour(),
    ]);

    $brainstorm3 = Brainstorm::factory()->create([
        'project_id' => $project->id,
        'created_at' => now(),
    ]);

    $response = $this->get(route('projects.brainstorm.index', $project));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('brainstorms', 3)
        ->where('brainstorms.0.id', $brainstorm3->id)
        ->where('brainstorms.1.id', $brainstorm2->id)
        ->where('brainstorms.2.id', $brainstorm1->id)
    );
});

it('renders brainstorm show page', function () {
    $project = Project::factory()->create();
    $brainstorm = Brainstorm::factory()->create(['project_id' => $project->id]);

    $response = $this->get(route('projects.brainstorm.show', [$project, $brainstorm]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('projects/brainstorm-show')
        ->has('project')
        ->has('brainstorm')
        ->where('brainstorm.id', $brainstorm->id)
    );
});
