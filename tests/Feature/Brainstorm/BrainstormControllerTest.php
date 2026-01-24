<?php

declare(strict_types=1);

use App\Jobs\GenerateBrainstormIdeas;
use App\Models\Brainstorm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

uses()->group('brainstorm', 'feature');

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create([
        'path' => storage_path('testing/project-'.uniqid()),
    ]);
});

it('displays brainstorm index page', function () {
    $response = $this->get(route('projects.brainstorm.index', $this->project));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/brainstorm')
        ->has('project')
        ->has('brainstorms')
    );
});

it('shows existing brainstorms on index page', function () {
    Brainstorm::factory()->count(3)->create([
        'project_id' => $this->project->id,
    ]);

    $response = $this->get(route('projects.brainstorm.index', $this->project));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('brainstorms', 3)
    );
});

it('creates brainstorm and dispatches job', function () {
    Queue::fake();

    $response = $this->post(route('projects.brainstorm.store', $this->project), [
        'user_context' => 'Generate ideas for improving user experience',
    ]);

    $response->assertRedirect(route('projects.brainstorm.index', $this->project));
    $response->assertSessionHas('success');

    expect(Brainstorm::count())->toBe(1);

    $brainstorm = Brainstorm::first();
    expect($brainstorm->project_id)->toBe($this->project->id);
    expect($brainstorm->user_id)->toBe($this->user->id);
    expect($brainstorm->user_context)->toBe('Generate ideas for improving user experience');
    expect($brainstorm->status)->toBe('pending');

    Queue::assertPushed(GenerateBrainstormIdeas::class, function ($job) use ($brainstorm) {
        return $job->brainstorm->id === $brainstorm->id;
    });
});

it('validates user context length', function () {
    $response = $this->post(route('projects.brainstorm.store', $this->project), [
        'user_context' => str_repeat('a', 5001),
    ]);

    $response->assertSessionHasErrors('user_context');
});

it('allows empty user context', function () {
    Queue::fake();

    $response = $this->post(route('projects.brainstorm.store', $this->project), [
        'user_context' => '',
    ]);

    $response->assertRedirect();
    expect(Brainstorm::count())->toBe(1);
});

it('displays specific brainstorm session', function () {
    $brainstorm = Brainstorm::factory()->create([
        'project_id' => $this->project->id,
        'status' => 'completed',
        'ideas' => [
            [
                'title' => 'Test Idea',
                'description' => 'Test description',
                'priority' => 'high',
                'category' => 'feature',
            ],
        ],
    ]);

    $response = $this->get(route('projects.brainstorm.show', [$this->project, $brainstorm]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/brainstorm-show')
        ->has('project')
        ->has('brainstorm')
        ->where('brainstorm.id', $brainstorm->id)
        ->where('brainstorm.status', 'completed')
    );
});

it('only shows brainstorms for the current project', function () {
    $otherProject = Project::factory()->create();

    Brainstorm::factory()->create(['project_id' => $this->project->id]);
    Brainstorm::factory()->create(['project_id' => $otherProject->id]);

    $response = $this->get(route('projects.brainstorm.index', $this->project));

    $response->assertInertia(fn ($page) => $page->has('brainstorms', 1));
});
