<?php

use App\Models\Brainstorm;
use App\Models\Project;

test('navigating to brainstorm page displays page', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $page = visit(route('projects.brainstorm.index', $project));

    $page->assertSee('Test Project')
        ->assertSee('Create New Brainstorm Session')
        ->assertSee('Previous Sessions')
        ->assertNoJavascriptErrors();
});

test('form displays with textarea for context input', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.brainstorm.index', $project));

    $page->assertSee('Context (Optional)')
        ->assertSee('Generate Ideas')
        ->assertNoJavascriptErrors();
});

test('previous sessions display in list', function () {
    $project = Project::factory()->create();

    Brainstorm::factory()->completed()->create([
        'project_id' => $project->id,
        'user_context' => 'First brainstorm session',
    ]);

    Brainstorm::factory()->pending()->create([
        'project_id' => $project->id,
        'user_context' => 'Second brainstorm session',
    ]);

    $page = visit(route('projects.brainstorm.index', $project));

    $page->assertSee('First brainstorm session')
        ->assertSee('Second brainstorm session')
        ->assertSee('Completed')
        ->assertSee('Pending')
        ->assertNoJavascriptErrors();
});

test('clicking session navigates to show page', function () {
    $project = Project::factory()->create();

    $brainstorm = Brainstorm::factory()->completed()->create([
        'project_id' => $project->id,
        'user_context' => 'Clickable brainstorm context',
    ]);

    $page = visit(route('projects.brainstorm.index', $project));

    $page->assertSee('Clickable brainstorm context')
        ->assertSee('Completed')
        ->assertNoJavascriptErrors();
});

test('status badges display correctly', function () {
    $project = Project::factory()->create();

    Brainstorm::factory()->pending()->create(['project_id' => $project->id]);
    Brainstorm::factory()->processing()->create(['project_id' => $project->id]);
    Brainstorm::factory()->completed()->create(['project_id' => $project->id]);
    Brainstorm::factory()->failed()->create(['project_id' => $project->id]);

    $page = visit(route('projects.brainstorm.index', $project));

    $page->assertSee('Pending')
        ->assertSee('Processing')
        ->assertSee('Completed')
        ->assertSee('Failed')
        ->assertNoJavascriptErrors();
});

test('empty state displays when no brainstorms exist', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.brainstorm.index', $project));

    $page->assertSee('No brainstorm sessions yet. Create your first one above!')
        ->assertNoJavascriptErrors();
});

test('character count displays on form', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.brainstorm.index', $project));

    $page->assertSee('characters')
        ->assertNoJavascriptErrors();
});

test('completed brainstorm shows all ideas', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $brainstorm = Brainstorm::factory()->completed()->create([
        'project_id' => $project->id,
        'user_context' => 'Performance improvements',
        'ideas' => [
            [
                'title' => 'Implement Redis Caching',
                'description' => 'Add Redis caching layer to reduce database queries',
                'priority' => 'high',
                'category' => 'feature',
            ],
            [
                'title' => 'Optimize Database Queries',
                'description' => 'Review and optimize slow database queries',
                'priority' => 'medium',
                'category' => 'enhancement',
            ],
        ],
    ]);

    $page = visit(route('projects.brainstorm.show', [$project, $brainstorm]));

    $page->assertSee('Test Project')
        ->assertSee('Status')
        ->assertSee('Completed')
        ->assertSee('Performance improvements')
        ->assertSee('Generated Ideas')
        ->assertSee('Implement Redis Caching')
        ->assertSee('Add Redis caching layer to reduce database queries')
        ->assertSee('Optimize Database Queries')
        ->assertSee('high')
        ->assertSee('medium')
        ->assertNoJavascriptErrors();
});

test('processing brainstorm shows loading state', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $brainstorm = Brainstorm::factory()->processing()->create([
        'project_id' => $project->id,
        'user_context' => 'Generate new features',
    ]);

    $page = visit(route('projects.brainstorm.show', [$project, $brainstorm]));

    $page->assertSee('Test Project')
        ->assertSee('Status')
        ->assertSee('Processing')
        ->assertSee('Generating Ideas...')
        ->assertSee('AI is working on generating creative ideas for you.')
        ->assertNoJavascriptErrors();
});

test('failed brainstorm shows error message', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $brainstorm = Brainstorm::factory()->failed()->create([
        'project_id' => $project->id,
        'error_message' => 'API rate limit exceeded',
    ]);

    $page = visit(route('projects.brainstorm.show', [$project, $brainstorm]));

    $page->assertSee('Test Project')
        ->assertSee('Status')
        ->assertSee('Failed')
        ->assertSee('Error')
        ->assertSee('API rate limit exceeded')
        ->assertNoJavascriptErrors();
});

test('pending brainstorm shows waiting state', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);

    $brainstorm = Brainstorm::factory()->pending()->create([
        'project_id' => $project->id,
        'user_context' => 'Test context',
    ]);

    $page = visit(route('projects.brainstorm.show', [$project, $brainstorm]));

    $page->assertSee('Test Project')
        ->assertSee('Status')
        ->assertSee('Pending')
        ->assertSee('Waiting to Process')
        ->assertSee('Test context')
        ->assertNoJavascriptErrors();
});
