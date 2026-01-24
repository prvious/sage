<?php

declare(strict_types=1);

use App\Actions\Brainstorm\GatherProjectContext;
use App\Models\Project;
use App\Models\Spec;
use Illuminate\Support\Facades\File;

uses()->group('brainstorm', 'unit');

beforeEach(function () {
    $this->project = Project::factory()->create([
        'path' => storage_path('testing/project-'.uniqid()),
    ]);

    File::makeDirectory($this->project->path, 0755, true);

    $this->action = new GatherProjectContext;
});

afterEach(function () {
    if (File::exists($this->project->path)) {
        File::deleteDirectory($this->project->path);
    }
});

it('gathers README.md content', function () {
    File::put($this->project->path.'/README.md', '# Test Project');

    $context = $this->action->handle($this->project);

    expect($context)->toHaveKey('readme');
    expect($context['readme'])->toContain('# Test Project');
});

it('gathers CLAUDE.md content', function () {
    File::put($this->project->path.'/CLAUDE.md', '# Agent Guidelines');

    $context = $this->action->handle($this->project);

    expect($context)->toHaveKey('agent_guidelines');
    expect($context['agent_guidelines'])->toContain('# Agent Guidelines');
});

it('prefers CLAUDE.md over AGENTS.md', function () {
    File::put($this->project->path.'/CLAUDE.md', 'Claude guidelines');
    File::put($this->project->path.'/AGENTS.md', 'Agents guidelines');

    $context = $this->action->handle($this->project);

    expect($context['agent_guidelines'])->toContain('Claude guidelines');
    expect($context['agent_guidelines'])->not->toContain('Agents guidelines');
});

it('gathers .ai/ directory files', function () {
    $aiDir = $this->project->path.'/.ai';
    File::makeDirectory($aiDir, 0755, true);
    File::put($aiDir.'/rules.md', 'Custom rules');
    File::put($aiDir.'/guidelines.md', 'Custom guidelines');

    $context = $this->action->handle($this->project);

    expect($context)->toHaveKey('ai_guidelines');
    expect($context['ai_guidelines'])->toHaveKey('rules.md');
    expect($context['ai_guidelines'])->toHaveKey('guidelines.md');
});

it('ignores non-markdown files in .ai/', function () {
    $aiDir = $this->project->path.'/.ai';
    File::makeDirectory($aiDir, 0755, true);
    File::put($aiDir.'/rules.md', 'Markdown file');
    File::put($aiDir.'/config.json', '{"key": "value"}');

    $context = $this->action->handle($this->project);

    expect($context['ai_guidelines'])->toHaveKey('rules.md');
    expect($context['ai_guidelines'])->not->toHaveKey('config.json');
});

it('gathers existing specs', function () {
    Spec::factory()->count(3)->create([
        'project_id' => $this->project->id,
    ]);

    $context = $this->action->handle($this->project);

    expect($context)->toHaveKey('existing_specs');
    expect($context['existing_specs'])->toHaveCount(3);
    expect($context['existing_specs'][0])->toHaveKeys(['name', 'description']);
});

it('gathers composer dependencies', function () {
    File::put($this->project->path.'/composer.json', json_encode([
        'require' => [
            'laravel/framework' => '^11.0',
            'inertiajs/inertia-laravel' => '^1.0',
        ],
        'require-dev' => [
            'pestphp/pest' => '^3.0',
        ],
    ]));

    $context = $this->action->handle($this->project);

    expect($context)->toHaveKey('composer_packages');
    expect($context['composer_packages'])->toContain('laravel/framework');
    expect($context['composer_packages'])->toContain('pestphp/pest');
});

it('gathers npm dependencies', function () {
    File::put($this->project->path.'/package.json', json_encode([
        'dependencies' => [
            'react' => '^18.0',
            '@inertiajs/react' => '^1.0',
        ],
        'devDependencies' => [
            'vite' => '^5.0',
        ],
    ]));

    $context = $this->action->handle($this->project);

    expect($context)->toHaveKey('npm_packages');
    expect($context['npm_packages'])->toContain('react');
    expect($context['npm_packages'])->toContain('vite');
});

it('handles missing files gracefully', function () {
    $context = $this->action->handle($this->project);

    expect($context)->not->toHaveKey('readme');
    expect($context)->not->toHaveKey('agent_guidelines');
});

it('truncates large files', function () {
    $largeContent = str_repeat('a', 20000);
    File::put($this->project->path.'/README.md', $largeContent);

    $context = $this->action->handle($this->project);

    expect(strlen($context['readme']))->toBeLessThan(strlen($largeContent));
    expect($context['readme'])->toContain('[truncated]');
});

it('skips large files in .ai/ directory', function () {
    $aiDir = $this->project->path.'/.ai';
    File::makeDirectory($aiDir, 0755, true);
    File::put($aiDir.'/small.md', 'Small file');
    File::put($aiDir.'/large.md', str_repeat('a', 20000));

    $context = $this->action->handle($this->project);

    expect($context['ai_guidelines'])->toHaveKey('small.md');
    expect($context['ai_guidelines'])->not->toHaveKey('large.md');
});
