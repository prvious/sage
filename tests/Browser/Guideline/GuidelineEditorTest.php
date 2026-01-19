<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);

    $this->project = Project::factory()->create([
        'path' => storage_path('testing/project-'.uniqid()),
    ]);

    // Create project directory
    File::makeDirectory($this->project->path, 0755, true, true);
});

afterEach(function () {
    // Clean up test directories
    if (File::exists($this->project->path)) {
        File::deleteDirectory($this->project->path);
    }
});

it('navigates to context page from sidebar', function () {
    $page = visit("/projects/{$this->project->id}/dashboard");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Guidelines');
});

it('displays empty state when no files exist', function () {
    $page = visit("/projects/{$this->project->id}/guidelines");

    $page->assertNoJavascriptErrors();
    $page->assertSee('No Guidelines Files');
});

it('displays list of guidelines', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);
    File::put($guidelinesDir.'/test-rules.md', '# Test Rules');
    File::put($guidelinesDir.'/custom-actions.md', '# Custom Actions');

    $page = visit("/projects/{$this->project->id}/guidelines");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Guidelines Files');
});

it('navigates to create page', function () {
    $page = visit("/projects/{$this->project->id}/guidelines");

    $page->assertNoJavascriptErrors();
});

it('creates new context file', function () {
    $page = visit("/projects/{$this->project->id}/guidelines/create");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Create Guidelines File');
});

it('navigates to edit page', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);
    File::put($guidelinesDir.'/test-rules.md', '# Test Rules');

    $page = visit("/projects/{$this->project->id}/guidelines/test-rules.md/edit");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Edit Guidelines File');
});

it('displays aggregate button', function () {
    $page = visit("/projects/{$this->project->id}/guidelines");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Aggregate Files');
});

it('displays context information alert', function () {
    $page = visit("/projects/{$this->project->id}/guidelines");

    $page->assertNoJavascriptErrors();
    $page->assertSee('CLAUDE.md');
});
