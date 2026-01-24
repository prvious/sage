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

it('navigates to guidelines page from sidebar', function () {
    $page = visit("/projects/{$this->project->id}/dashboard");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Guidelines');
});

it('displays empty state when no files exist', function () {
    $page = visit("/projects/{$this->project->id}/guidelines");

    $page->assertNoJavascriptErrors();
    $page->assertSee('No Custom Guidelines');
});

it('displays list of guidelines', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);
    File::put($guidelinesDir.'/test-rules.md', '# Test Rules');
    File::put($guidelinesDir.'/custom-actions.md', '# Custom Actions');

    $page = visit("/projects/{$this->project->id}/guidelines");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Custom Guidelines');
});

it('navigates to create page', function () {
    $page = visit("/projects/{$this->project->id}/guidelines");

    $page->assertNoJavascriptErrors();
});

it('creates new guideline file', function () {
    $page = visit("/projects/{$this->project->id}/guidelines/create");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Create Custom Guideline');
});

it('navigates to edit page', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);
    File::put($guidelinesDir.'/test-rules.md', '# Test Rules');

    $page = visit("/projects/{$this->project->id}/guidelines/test-rules.md/edit");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Edit Custom Guideline');
});

it('displays aggregate button', function () {
    $page = visit("/projects/{$this->project->id}/guidelines");

    $page->assertNoJavascriptErrors();
    $page->assertSee('Aggregate Guidelines');
});

it('displays guidelines information alert', function () {
    $page = visit("/projects/{$this->project->id}/guidelines");

    $page->assertNoJavascriptErrors();
    $page->assertSee('CLAUDE.md');
});
