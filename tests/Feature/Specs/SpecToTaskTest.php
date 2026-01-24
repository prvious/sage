<?php

use App\Actions\Spec\GenerateTaskFromSpec;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Spec;
use App\Models\Task;
use App\Models\Worktree;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('GenerateTaskFromSpec Action', function () {
    it('generates a task title from spec title', function () {
        $spec = Spec::factory()->create(['title' => 'User Authentication Feature']);
        $action = new GenerateTaskFromSpec;

        $title = $action->generateTitle($spec);

        expect($title)->toBe('Implement: User Authentication Feature');
    });

    it('generates a task description from spec content', function () {
        $spec = Spec::factory()->create([
            'title' => 'User Authentication',
            'content' => 'This is the spec content.',
        ]);
        $action = new GenerateTaskFromSpec;

        $description = $action->generateDescription($spec);

        expect($description)
            ->toContain('## Feature Implementation Task')
            ->toContain("**Feature ID:** {$spec->id}")
            ->toContain('**Title:** User Authentication')
            ->toContain('This is the spec content.');
    });

    it('includes implementation instructions in generated description', function () {
        $spec = Spec::factory()->create();
        $action = new GenerateTaskFromSpec;

        $description = $action->generateDescription($spec);

        expect($description)
            ->toContain('## Instructions')
            ->toContain('explore the codebase')
            ->toContain('Plan your implementation approach')
            ->toContain('Write the necessary code changes')
            ->toContain('## Guidelines')
            ->toContain('Follow the existing code style');
    });

    it('creates a task from a spec', function () {
        $spec = Spec::factory()->create();
        $action = new GenerateTaskFromSpec;

        $task = $action->handle($spec);

        expect($task)->toBeInstanceOf(Task::class);
        expect($task->project_id)->toBe($spec->project_id);
        expect($task->spec_id)->toBe($spec->id);
        expect($task->title)->toBe("Implement: {$spec->title}");
        expect($task->status)->toBe(TaskStatus::Queued);
    });

    it('creates a task with custom overrides', function () {
        $spec = Spec::factory()->create();
        $worktree = Worktree::factory()->create(['project_id' => $spec->project_id]);
        $action = new GenerateTaskFromSpec;

        $task = $action->handle($spec, [
            'title' => 'Custom Title',
            'description' => 'Custom description',
            'worktree_id' => $worktree->id,
        ]);

        expect($task->title)->toBe('Custom Title');
        expect($task->description)->toBe('Custom description');
        expect($task->worktree_id)->toBe($worktree->id);
    });

    it('uses default values when overrides are partial', function () {
        $spec = Spec::factory()->create(['title' => 'My Feature']);
        $action = new GenerateTaskFromSpec;

        $task = $action->handle($spec, [
            'title' => 'Custom Title Only',
        ]);

        expect($task->title)->toBe('Custom Title Only');
        expect($task->description)->toContain('## Feature Implementation Task');
        expect($task->worktree_id)->toBeNull();
    });

    it('creates task with null worktree_id by default', function () {
        $spec = Spec::factory()->create();
        $action = new GenerateTaskFromSpec;

        $task = $action->handle($spec);

        expect($task->worktree_id)->toBeNull();
    });

    it('persists task to database', function () {
        $spec = Spec::factory()->create();
        $action = new GenerateTaskFromSpec;

        $task = $action->handle($spec);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'spec_id' => $spec->id,
            'project_id' => $spec->project_id,
        ]);
    });

    it('uses generated title when empty string is provided', function () {
        $spec = Spec::factory()->create(['title' => 'Feature Name']);
        $action = new GenerateTaskFromSpec;

        $task = $action->handle($spec, [
            'title' => '',
            'description' => 'Custom description',
        ]);

        expect($task->title)->toBe('Implement: Feature Name');
    });

    it('uses generated title when null is provided', function () {
        $spec = Spec::factory()->create(['title' => 'Feature Name']);
        $action = new GenerateTaskFromSpec;

        $task = $action->handle($spec, [
            'title' => null,
            'description' => 'Custom description',
        ]);

        expect($task->title)->toBe('Implement: Feature Name');
    });
});

describe('SpecController::previewTask', function () {
    it('returns pre-filled task data for a spec', function () {
        $spec = Spec::factory()->create();

        $response = $this->getJson("/projects/{$spec->project_id}/specs/{$spec->id}/preview-task");

        $response->assertOk()
            ->assertJson([
                'title' => "Implement: {$spec->title}",
            ])
            ->assertJsonStructure(['title', 'description']);

        expect($response->json('description'))
            ->toContain($spec->title)
            ->toContain($spec->content);
    });

    it('returns 404 for spec not belonging to project', function () {
        $spec = Spec::factory()->create();
        $otherProject = Project::factory()->create();

        $response = $this->getJson("/projects/{$otherProject->id}/specs/{$spec->id}/preview-task");

        $response->assertNotFound();
    });
});

describe('SpecController::createTask', function () {
    it('creates a task from a spec', function () {
        $spec = Spec::factory()->create();

        $response = $this->post("/projects/{$spec->project_id}/specs/{$spec->id}/create-task", [
            'title' => 'Task Title',
            'description' => 'Task description for the agent',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'project_id' => $spec->project_id,
            'spec_id' => $spec->id,
            'title' => 'Task Title',
            'description' => 'Task description for the agent',
            'status' => TaskStatus::Queued->value,
        ]);
    });

    it('creates a task with worktree', function () {
        $spec = Spec::factory()->create();
        $worktree = Worktree::factory()->create(['project_id' => $spec->project_id]);

        $response = $this->post("/projects/{$spec->project_id}/specs/{$spec->id}/create-task", [
            'title' => 'Task Title',
            'description' => 'Task description',
            'worktree_id' => $worktree->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'spec_id' => $spec->id,
            'worktree_id' => $worktree->id,
        ]);
    });

    it('creates a task without title using generated title', function () {
        $spec = Spec::factory()->create(['title' => 'My Feature Spec']);

        $response = $this->post("/projects/{$spec->project_id}/specs/{$spec->id}/create-task", [
            'description' => 'Task description for the agent',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'spec_id' => $spec->id,
            'title' => 'Implement: My Feature Spec',
        ]);
    });

    it('validates required description', function () {
        $spec = Spec::factory()->create();

        $response = $this->post("/projects/{$spec->project_id}/specs/{$spec->id}/create-task", [
            'title' => 'Task Title',
        ]);

        $response->assertSessionHasErrors('description');
    });

    it('validates description max length', function () {
        $spec = Spec::factory()->create();

        $response = $this->post("/projects/{$spec->project_id}/specs/{$spec->id}/create-task", [
            'title' => 'Task Title',
            'description' => str_repeat('a', 10001),
        ]);

        $response->assertSessionHasErrors('description');
    });

    it('validates title max length', function () {
        $spec = Spec::factory()->create();

        $response = $this->post("/projects/{$spec->project_id}/specs/{$spec->id}/create-task", [
            'title' => str_repeat('a', 256),
            'description' => 'Valid description',
        ]);

        $response->assertSessionHasErrors('title');
    });

    it('validates worktree_id exists', function () {
        $spec = Spec::factory()->create();

        $response = $this->post("/projects/{$spec->project_id}/specs/{$spec->id}/create-task", [
            'title' => 'Task Title',
            'description' => 'Task description',
            'worktree_id' => 99999,
        ]);

        $response->assertSessionHasErrors('worktree_id');
    });

    it('returns 404 for spec not belonging to project', function () {
        $spec = Spec::factory()->create();
        $otherProject = Project::factory()->create();

        $response = $this->post("/projects/{$otherProject->id}/specs/{$spec->id}/create-task", [
            'title' => 'Task Title',
            'description' => 'Task description',
        ]);

        $response->assertNotFound();
    });

    it('redirects to task show page after creation', function () {
        $spec = Spec::factory()->create();

        $response = $this->post("/projects/{$spec->project_id}/specs/{$spec->id}/create-task", [
            'title' => 'Task Title',
            'description' => 'Task description',
        ]);

        $task = Task::latest()->first();
        $response->assertRedirect(route('tasks.show', $task));
    });

    it('sets flash message on successful creation', function () {
        $spec = Spec::factory()->create();

        $response = $this->post("/projects/{$spec->project_id}/specs/{$spec->id}/create-task", [
            'title' => 'Task Title',
            'description' => 'Task description',
        ]);

        $response->assertSessionHas('success', 'Task created from spec successfully.');
    });

    it('accepts empty string for title', function () {
        $spec = Spec::factory()->create(['title' => 'Feature Name']);

        $response = $this->post("/projects/{$spec->project_id}/specs/{$spec->id}/create-task", [
            'title' => '',
            'description' => 'Task description',
        ]);

        $response->assertRedirect();

        // Empty title should be treated as null and use the generated title
        $task = Task::where('spec_id', $spec->id)->first();
        expect($task->title)->toBe('Implement: Feature Name');
    });
});

describe('Task-Spec Relationship', function () {
    it('task belongs to spec', function () {
        $spec = Spec::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $spec->project_id,
            'spec_id' => $spec->id,
        ]);

        expect($task->spec)->toBeInstanceOf(Spec::class);
        expect($task->spec->id)->toBe($spec->id);
    });

    it('spec has many tasks', function () {
        $spec = Spec::factory()->create();
        $tasks = Task::factory()->count(3)->create([
            'project_id' => $spec->project_id,
            'spec_id' => $spec->id,
        ]);

        expect($spec->tasks)->toHaveCount(3);
        expect($spec->tasks->first())->toBeInstanceOf(Task::class);
    });

    it('task can exist without spec', function () {
        $task = Task::factory()->create(['spec_id' => null]);

        expect($task->spec)->toBeNull();
    });

    it('deleting spec sets task spec_id to null', function () {
        $spec = Spec::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $spec->project_id,
            'spec_id' => $spec->id,
        ]);

        $spec->delete();
        $task->refresh();

        expect($task->spec_id)->toBeNull();
    });
});
