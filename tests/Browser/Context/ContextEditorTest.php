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
    $page->assertSee('Context');
});

it('displays empty state when no files exist', function () {
    $page = visit("/projects/{$this->project->id}/context");

    $page->assertNoJavascriptErrors();
    $page->assertSee('No Context Files');
});

it('displays list of context files', function () {
    $aiDir = $this->project->path.'/.ai';
    File::makeDirectory($aiDir, 0755, true);
    File::put($aiDir.'/test-rules.md', '# Test Rules');
    File::put($aiDir.'/custom-actions.md', '# Custom Actions');

    $page = visit("/projects/{$this->project->id}/context");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Context Files');
});

it('navigates to create page', function () {
    $page = visit("/projects/{$this->project->id}/context");

    $page->assertNoJavascriptErrors();
});

it('creates new context file', function () {
    $page = visit("/projects/{$this->project->id}/context/create");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Create Context File');
});

it('navigates to edit page', function () {
    $aiDir = $this->project->path.'/.ai';
    File::makeDirectory($aiDir, 0755, true);
    File::put($aiDir.'/test-rules.md', '# Test Rules');

    $page = visit("/projects/{$this->project->id}/context/test-rules.md/edit");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Edit Context File');
});

it('displays aggregate button', function () {
    $page = visit("/projects/{$this->project->id}/context");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Aggregate Files');
});

it('displays context information alert', function () {
    $page = visit("/projects/{$this->project->id}/context");

    $page->assertNoJavascriptErrors();
    $page->assertSee('CLAUDE.md');
});
