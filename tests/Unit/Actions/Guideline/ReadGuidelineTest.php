<?php

declare(strict_types=1);

use App\Actions\Guideline\ReadGuideline;
use App\Models\Project;
use Illuminate\Support\Facades\File;

uses()->group('guideline', 'unit');

beforeEach(function () {
    $this->project = Project::factory()->create([
        'path' => storage_path('testing/project-'.uniqid()),
    ]);

    File::makeDirectory($this->project->path, 0755, true);

    $this->action = new ReadGuideline;
});

afterEach(function () {
    if (File::exists($this->project->path)) {
        File::deleteDirectory($this->project->path);
    }
});

it('reads .md file content', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    $content = '# Test Content'.PHP_EOL.'This is a test guideline.';
    File::put($guidelinesDir.'/test.md', $content);

    $result = $this->action->handle($this->project, 'test.md');

    expect($result)->toBe($content);
});

it('reads .blade.php file content', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    $content = '# Blade Template'.PHP_EOL.'{{ $variable }}';
    File::put($guidelinesDir.'/template.blade.php', $content);

    $result = $this->action->handle($this->project, 'template.blade.php');

    expect($result)->toBe($content);
});

it('throws exception when file does not exist', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    expect(fn () => $this->action->handle($this->project, 'nonexistent.md'))
        ->toThrow(InvalidArgumentException::class, 'File not found');
});

it('throws exception when .ai/guidelines directory does not exist', function () {
    expect(fn () => $this->action->handle($this->project, 'test.md'))
        ->toThrow(InvalidArgumentException::class, '.ai/guidelines directory does not exist');
});

it('prevents path traversal with ..', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    expect(fn () => $this->action->handle($this->project, '../../../etc/passwd'))
        ->toThrow(InvalidArgumentException::class, 'directory traversal detected');
});

it('prevents path traversal with forward slash', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    expect(fn () => $this->action->handle($this->project, 'subdir/file.md'))
        ->toThrow(InvalidArgumentException::class, 'directory traversal detected');
});

it('prevents path traversal with backslash', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    expect(fn () => $this->action->handle($this->project, 'subdir\file.md'))
        ->toThrow(InvalidArgumentException::class, 'directory traversal detected');
});

it('validates file extension must be .md or .blade.php', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);

    expect(fn () => $this->action->handle($this->project, 'test.txt'))
        ->toThrow(InvalidArgumentException::class, 'must end with .md or .blade.php');
});
