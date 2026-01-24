<?php

declare(strict_types=1);

use App\Actions\Guideline\ListGuidelines;
use App\Models\Project;
use Illuminate\Support\Facades\File;

uses()->group('guideline', 'unit');

beforeEach(function () {
    $this->project = Project::factory()->create([
        'path' => storage_path('testing/project-'.uniqid()),
    ]);

    $this->action = new ListGuidelines;
});

afterEach(function () {
    if (File::exists($this->project->path)) {
        File::deleteDirectory($this->project->path);
    }
});

it('lists all .md files from .ai/guidelines/', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    File::put($guidelinesDir.'/api-conventions.md', '# API Conventions');
    File::put($guidelinesDir.'/architecture.md', '# Architecture');

    $files = $this->action->handle($this->project);

    expect($files)->toHaveCount(2);
    expect($files->pluck('name')->toArray())->toBe(['api-conventions.md', 'architecture.md']);
});

it('lists all .blade.php files from .ai/guidelines/', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    File::put($guidelinesDir.'/testing-standards.blade.php', '# Testing Standards');
    File::put($guidelinesDir.'/coding-style.blade.php', '# Coding Style');

    $files = $this->action->handle($this->project);

    expect($files)->toHaveCount(2);
    expect($files->pluck('name')->toArray())->toBe(['coding-style.blade.php', 'testing-standards.blade.php']);
});

it('lists both .md and .blade.php files', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    File::put($guidelinesDir.'/api.md', '# API');
    File::put($guidelinesDir.'/testing.blade.php', '# Testing');
    File::put($guidelinesDir.'/architecture.md', '# Architecture');

    $files = $this->action->handle($this->project);

    expect($files)->toHaveCount(3);
    expect($files->pluck('name')->toArray())->toBe(['api.md', 'architecture.md', 'testing.blade.php']);
});

it('returns empty collection when directory does not exist', function () {
    $files = $this->action->handle($this->project);

    expect($files)->toBeEmpty();
});

it('files are sorted alphabetically', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    File::put($guidelinesDir.'/zebra.md', '# Zebra');
    File::put($guidelinesDir.'/apple.md', '# Apple');
    File::put($guidelinesDir.'/mango.md', '# Mango');

    $files = $this->action->handle($this->project);

    expect($files->pluck('name')->toArray())->toBe(['apple.md', 'mango.md', 'zebra.md']);
});

it('ignores non-markdown and non-blade files', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    File::put($guidelinesDir.'/valid.md', '# Valid');
    File::put($guidelinesDir.'/invalid.txt', 'Invalid');
    File::put($guidelinesDir.'/also-invalid.json', '{}');
    File::put($guidelinesDir.'/valid-blade.blade.php', '# Valid Blade');

    $files = $this->action->handle($this->project);

    expect($files)->toHaveCount(2);
    expect($files->pluck('name')->toArray())->toBe(['valid-blade.blade.php', 'valid.md']);
});

it('includes file metadata', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    File::put($guidelinesDir.'/test.md', '# Test Content');

    $files = $this->action->handle($this->project);

    expect($files->first())->toHaveKeys(['name', 'path', 'size', 'modified_at']);
    expect($files->first()['name'])->toBe('test.md');
    expect($files->first()['size'])->toBeGreaterThan(0);
    expect($files->first()['modified_at'])->toBeInt();
});
