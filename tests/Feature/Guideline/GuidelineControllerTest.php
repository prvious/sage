<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\File;

uses()->group('guideline');

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

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

it('displays guidelines index page', function () {
    $response = $this->get(route('projects.guidelines.index', $this->project));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/guidelines/index')
        ->has('files')
        ->has('project')
    );
});

it('lists all .ai/guidelines/ files', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    File::put($guidelinesDir.'/test-rules.md', '# Test Rules');
    File::put($guidelinesDir.'/custom-actions.md', '# Custom Actions');

    $response = $this->get(route('projects.guidelines.index', $this->project));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/guidelines/index')
        ->has('files', 2)
        ->where('files.0.name', 'custom-actions.md')
        ->where('files.1.name', 'test-rules.md')
    );
});

it('returns empty array when .ai/guidelines/ directory does not exist', function () {
    $response = $this->get(route('projects.guidelines.index', $this->project));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/guidelines/index')
        ->has('files', 0)
    );
});

it('displays create page', function () {
    $response = $this->get(route('projects.guidelines.create', $this->project));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/guidelines/create')
        ->has('project')
    );
});

it('stores new guideline', function () {
    $response = $this->post(route('projects.guidelines.store', $this->project), [
        'filename' => 'test-rules.md',
        'content' => '# Test Rules',
    ]);

    $response->assertRedirect(route('projects.guidelines.index', $this->project));
    $response->assertSessionHas('success');

    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    expect(File::exists($guidelinesDir.'/test-rules.md'))->toBeTrue();
    expect(File::get($guidelinesDir.'/test-rules.md'))->toBe('# Test Rules');
});

it('auto-appends .md extension when storing', function () {
    $response = $this->post(route('projects.guidelines.store', $this->project), [
        'filename' => 'test-rules',
        'content' => '# Test Rules',
    ]);

    $response->assertRedirect(route('projects.guidelines.index', $this->project));

    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    expect(File::exists($guidelinesDir.'/test-rules.md'))->toBeTrue();
});

it('validates filename when storing', function () {
    $response = $this->post(route('projects.guidelines.store', $this->project), [
        'filename' => '../../../etc/passwd',
        'content' => 'malicious content',
    ]);

    $response->assertSessionHasErrors('filename');
});

it('validates content is required when storing', function () {
    $response = $this->post(route('projects.guidelines.store', $this->project), [
        'filename' => 'test-rules.md',
        'content' => '',
    ]);

    $response->assertSessionHasErrors('content');
});

it('displays edit page', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);
    File::put($guidelinesDir.'/test-rules.md', '# Test Rules');

    $response = $this->get(route('projects.guidelines.edit', [$this->project, 'test-rules.md']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/guidelines/edit')
        ->has('project')
        ->where('filename', 'test-rules.md')
        ->where('content', '# Test Rules')
    );
});

it('updates guideline', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);
    File::put($guidelinesDir.'/test-rules.md', '# Test Rules');

    $response = $this->put(route('projects.guidelines.update', [$this->project, 'test-rules.md']), [
        'filename' => 'test-rules.md',
        'content' => '# Updated Test Rules',
    ]);

    $response->assertRedirect(route('projects.guidelines.index', $this->project));
    $response->assertSessionHas('success');

    expect(File::get($this->project->path.'/.ai/guidelines/test-rules.md'))->toBe('# Updated Test Rules');
});

it('deletes guideline', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);
    File::put($guidelinesDir.'/test-rules.md', '# Test Rules');

    expect(File::exists($guidelinesDir.'/test-rules.md'))->toBeTrue();

    $response = $this->delete(route('projects.guidelines.destroy', [$this->project, 'test-rules.md']));

    $response->assertRedirect(route('projects.guidelines.index', $this->project));
    $response->assertSessionHas('success');

    expect(File::exists($guidelinesDir.'/test-rules.md'))->toBeFalse();
});

it('prevents directory traversal when reading file', function () {
    $response = $this->get(route('projects.guidelines.edit', [$this->project, '../../../etc/passwd']));

    $response->assertNotFound();
});

it('prevents directory traversal when deleting file', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    // Laravel's router rejects path parameters with .. so we get 404
    $response = $this->delete(route('projects.guidelines.destroy', [$this->project, '../traversal.md']));

    $response->assertNotFound();
});

it('returns 404 when file does not exist on edit', function () {
    $response = $this->get(route('projects.guidelines.edit', [$this->project, 'nonexistent.md']));

    $response->assertNotFound();
});

it('returns 404 when file does not exist on show', function () {
    $response = $this->get(route('projects.guidelines.show', [$this->project, 'nonexistent.md']));

    $response->assertNotFound();
});
