<?php

declare(strict_types=1);

use App\Actions\Guideline\WriteGuideline;
use App\Models\Project;
use Illuminate\Support\Facades\File;

uses()->group('guideline', 'unit');

beforeEach(function () {
    $this->project = Project::factory()->create([
        'path' => storage_path('testing/project-'.uniqid()),
    ]);

    File::makeDirectory($this->project->path, 0755, true);

    $this->action = new WriteGuideline;
});

afterEach(function () {
    if (File::exists($this->project->path)) {
        File::deleteDirectory($this->project->path);
    }
});

it('creates .ai/guidelines/ directory if it does not exist', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';

    expect(File::exists($guidelinesDir))->toBeFalse();

    $this->action->handle($this->project, 'test.md', '# Test Content');

    expect(File::exists($guidelinesDir))->toBeTrue();
});

it('writes .md file correctly', function () {
    $content = '# Test Guideline'.PHP_EOL.'This is a test.';

    $this->action->handle($this->project, 'test-guideline.md', $content);

    $filePath = $this->project->path.'/.ai/guidelines/test-guideline.md';
    expect(File::exists($filePath))->toBeTrue();
    expect(File::get($filePath))->toBe($content);
});

it('writes .blade.php file correctly', function () {
    $content = '# Blade Template'.PHP_EOL.'{{ $variable }}';

    $this->action->handle($this->project, 'template.blade.php', $content);

    $filePath = $this->project->path.'/.ai/guidelines/template.blade.php';
    expect(File::exists($filePath))->toBeTrue();
    expect(File::get($filePath))->toBe($content);
});

it('overwrites existing file', function () {
    $guidelinesDir = $this->project->path.'/.ai/guidelines';
    File::makeDirectory($guidelinesDir, 0755, true);
    File::put($guidelinesDir.'/test.md', 'Old content');

    $this->action->handle($this->project, 'test.md', 'New content');

    expect(File::get($guidelinesDir.'/test.md'))->toBe('New content');
});

it('prevents path traversal with ..', function () {
    expect(fn () => $this->action->handle($this->project, '../../../etc/passwd', 'malicious'))
        ->toThrow(InvalidArgumentException::class, 'directory traversal detected');
});

it('prevents path traversal with forward slash', function () {
    expect(fn () => $this->action->handle($this->project, 'subdir/file.md', 'content'))
        ->toThrow(InvalidArgumentException::class, 'directory traversal detected');
});

it('prevents path traversal with backslash', function () {
    expect(fn () => $this->action->handle($this->project, 'subdir\file.md', 'content'))
        ->toThrow(InvalidArgumentException::class, 'directory traversal detected');
});

it('validates file extension must be .md', function () {
    expect(fn () => $this->action->handle($this->project, 'test.txt', 'content'))
        ->toThrow(InvalidArgumentException::class, 'must end with .md or .blade.php');
});

it('validates file extension must be .blade.php', function () {
    expect(fn () => $this->action->handle($this->project, 'test.php', 'content'))
        ->toThrow(InvalidArgumentException::class, 'must end with .md or .blade.php');
});

it('rejects filename with special characters', function () {
    expect(fn () => $this->action->handle($this->project, 'test file.md', 'content'))
        ->toThrow(InvalidArgumentException::class, 'only alphanumeric, dash, and underscore allowed');
});

it('allows alphanumeric characters in filename', function () {
    $this->action->handle($this->project, 'test123.md', 'content');

    expect(File::exists($this->project->path.'/.ai/guidelines/test123.md'))->toBeTrue();
});

it('allows dashes in filename', function () {
    $this->action->handle($this->project, 'test-file.md', 'content');

    expect(File::exists($this->project->path.'/.ai/guidelines/test-file.md'))->toBeTrue();
});

it('allows underscores in filename', function () {
    $this->action->handle($this->project, 'test_file.md', 'content');

    expect(File::exists($this->project->path.'/.ai/guidelines/test_file.md'))->toBeTrue();
});

it('rejects filename longer than 255 characters', function () {
    $longFilename = str_repeat('a', 253).'.md'; // 253 + 3 = 256 characters

    expect(fn () => $this->action->handle($this->project, $longFilename, 'content'))
        ->toThrow(InvalidArgumentException::class, 'Filename too long');
});

it('accepts blade.php extension with valid name', function () {
    $this->action->handle($this->project, 'valid-template.blade.php', 'content');

    expect(File::exists($this->project->path.'/.ai/guidelines/valid-template.blade.php'))->toBeTrue();
});
