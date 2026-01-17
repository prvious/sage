<?php

use App\Models\Brainstorm;
use App\Models\Project;

test('navigating to brainstorm page displays page', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.brainstorm.index', $project));

    $page->assertSee('Brainstorm')
        ->assertSee('Create New Brainstorm Session')
        ->assertSee('Previous Sessions')
        ->assertNoJavascriptErrors();
});

test('entering context in textarea', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.brainstorm.index', $project));

    $page->fill('user_context', 'This is a test brainstorm context')
        ->assertValue('user_context', 'This is a test brainstorm context')
        ->assertNoJavascriptErrors();
});

test('submitting form creates brainstorm', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.brainstorm.index', $project));

    $page->fill('user_context', 'Test brainstorm from browser')
        ->click('Create Brainstorm')
        ->wait(2)
        ->assertNoJavascriptErrors();

    expect(Brainstorm::where('project_id', $project->id)->count())->toBe(1);
    expect(Brainstorm::first()->user_context)->toBe('Test brainstorm from browser');
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
        'user_context' => 'Clickable brainstorm',
    ]);

    $page = visit(route('projects.brainstorm.index', $project));

    $page->click('Clickable brainstorm')
        ->wait(1)
        ->assertNoJavascriptErrors();

    expect($page->url())->toBe(route('projects.brainstorm.show', [$project, $brainstorm]));
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

    $page->assertSee('No brainstorm sessions yet')
        ->assertSee('Create your first one above!')
        ->assertNoJavascriptErrors();
});

test('character count displays correctly', function () {
    $project = Project::factory()->create();

    $page = visit(route('projects.brainstorm.index', $project));

    $page->assertSee('0 / 5000 characters')
        ->fill('user_context', 'Hello')
        ->assertSee('5 / 5000 characters')
        ->assertNoJavascriptErrors();
});
